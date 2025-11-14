<?php

namespace Tests\Integration;

use Testcontainers\Container\GenericContainer;
use Testcontainers\Container\StartedGenericContainer;
use ZipArchive;

class WebtreesContainer extends GenericContainer
{
    private static ?StartedGenericContainer $started = null;
    
    // Hardcoded webtrees version - update this when updating composer.json
    // Must be compatible with the version constraint in composer.json require-dev
    private const WEBTREES_VERSION = '2.2.0';

    public function __construct()
    {
        parent::__construct('php:8-apache');

        // expose HTTP port
        $this->withExposedPorts(80);

        // Validate that hardcoded version matches composer.json expectation
        self::validateWebtreesVersion();

        $wtVersion = self::WEBTREES_VERSION;
        
        // Use project-relative cache directory to avoid re-downloading
        $cacheDir = dirname(__DIR__, 2) . '/.cache/webtrees';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $zipFile = $cacheDir . "/webtrees-{$wtVersion}.zip";
        $extractDir = $cacheDir . "/webtrees-{$wtVersion}";
        
        // Download only if not cached
        if (!file_exists($zipFile)) {
            // Direct download URL - no GitHub API calls
            $downloadUrl = "https://github.com/fisharebest/webtrees/releases/download/{$wtVersion}/webtrees-{$wtVersion}.zip";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $downloadUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PHP'); // Required for GitHub

            $zipData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($zipData === false || $httpCode !== 200) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \RuntimeException("Failed to download webtrees {$wtVersion} (HTTP $httpCode): $error");
            }
            curl_close($ch);

            if (file_put_contents($zipFile, $zipData) === false) {
                throw new \RuntimeException("Failed to write ZIP file to $zipFile");
            }
        }

        // Extract only if not already extracted
        if (!is_dir($extractDir . '/webtrees')) {
            if (is_dir($extractDir)) {
                // Clean up partial extraction
                self::removeDirectory($extractDir);
            }
            mkdir($extractDir, 0755, true);

            $zip = new ZipArchive;
            if ($zip->open($zipFile) === TRUE) {
                if (!$zip->extractTo($extractDir)) {
                    $zip->close();
                    throw new \RuntimeException("Failed to extract ZIP file to $extractDir");
                }
                $zip->close();
            } else {
                throw new \RuntimeException("Failed to open zip file: $zipFile");
            }
        }

        // copy webtrees core into /var/www/html
        $this->withCopyDirectoriesToContainer([[
            'source' => $extractDir . '/webtrees',
            'target' => '/var/www/html',
            'mode'   => 0777,
        ]]);
    }

    public function start(): StartedGenericContainer
    {
        self::$started = parent::start();

        return self::$started;
    }

    public function stop(): void
    {
        if (self::$started !== null) {
            try {
                self::$started->stop();
            } catch (\Throwable $e) {
                // ignore
            } finally {
                self::$started = null;
            }
        }
    }

    /** @noinspection HttpUrlsUsage */
    public function getBaseUrl(): string
    {
        return sprintf(
            'http://%s:%d',
            self::$started->getHost(),
            self::$started->getFirstMappedPort()
        );
    }

    /**
     * Validate that the hardcoded WEBTREES_VERSION is compatible with composer.json.
     * Throws an exception if they don't match to remind us to update the constant.
     */
    private static function validateWebtreesVersion(): void
    {
        $composerFile = dirname(__DIR__, 2) . '/composer.json';
        if (!file_exists($composerFile)) {
            return; // Skip validation if composer.json not found
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        $versionConstraint = $composer['require-dev']['fisharebest/webtrees'] ?? '^2.2';

        // Parse version constraint like "^2.2" to get major.minor
        $version = preg_replace('/^[\^~><=]+/', '', $versionConstraint);
        $parts = explode('.', $version);
        $expectedMajorMinor = (count($parts) >= 2) ? $parts[0] . '.' . $parts[1] : $version;

        // Check if hardcoded version is compatible
        $hardcodedParts = explode('.', self::WEBTREES_VERSION);
        $hardcodedMajorMinor = (count($hardcodedParts) >= 2) ? $hardcodedParts[0] . '.' . $hardcodedParts[1] : self::WEBTREES_VERSION;

        if ($hardcodedMajorMinor !== $expectedMajorMinor) {
            throw new \RuntimeException(
                "Webtrees version mismatch: hardcoded version " . self::WEBTREES_VERSION . 
                " doesn't match composer.json constraint {$versionConstraint}. " .
                "Please update WebtreesContainer::WEBTREES_VERSION to match."
            );
        }
    }
    
    /**
     * Recursively remove a directory and its contents.
     */
    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

}