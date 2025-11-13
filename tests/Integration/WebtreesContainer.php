<?php

namespace Tests\Integration;

use Testcontainers\Container\GenericContainer;
use Testcontainers\Container\StartedGenericContainer;
use ZipArchive;

class WebtreesContainer extends GenericContainer
{
    private static ?StartedGenericContainer $started = null;

    public function __construct()
    {
        parent::__construct('php:8-apache');

        // expose HTTP port
        $this->withExposedPorts(80);

        $wtVersion = self::getWebtreesVersion();
        // GitHub API requires a User-Agent header
        $opts = [
            "http" => [
                "header" => "User-Agent: PHP\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $releases = file_get_contents("https://api.github.com/repos/fisharebest/webtrees/releases", false, $context);
        $releases = json_decode($releases, true);

        // find the first release starting with $wtVersion
        $foundRelease = null;
        foreach ($releases as $release) {
            if (str_starts_with($release['tag_name'], $wtVersion)) {
                $foundRelease = $release;
                break;
            }
        }

        if (!$foundRelease) {
            throw new \RuntimeException("No webtrees release found for version $wtVersion");
        }

        // ensure assets exist
        if (empty($foundRelease['assets']) || !isset($foundRelease['assets'][0]['browser_download_url'])) {
            throw new \RuntimeException("Release does not contain downloadable assets");
        }

        $downloadUrl = $foundRelease['assets'][0]['browser_download_url'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $downloadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $zipData = curl_exec($ch);
        if ($zipData === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("cURL failed: $error");
        }
        curl_close($ch);

        $zipFile = tempnam(sys_get_temp_dir(), 'webtrees-');
        if (file_put_contents($zipFile, $zipData) === false) {
            throw new \RuntimeException("Failed to write ZIP file to $zipFile");
        }

        $extractDir = tempnam(sys_get_temp_dir(), 'webtrees-');
        if (file_exists($extractDir)) { unlink($extractDir); }
        mkdir($extractDir);

        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            if (!$zip->extractTo($extractDir)) {
                $zip->close();
                throw new \RuntimeException("Failed to extract ZIP file to $extractDir");
            }
            $zip->close();
            var_dump(scandir($extractDir));
        } else {
            throw new \RuntimeException("Failed to extract zip file: $zipFile");
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
     * Get the webtrees version from composer.json.
     * Parses the version constraint and returns a usable version string.
     */
    private static function getWebtreesVersion(): string
    {
        $composerFile = dirname(__DIR__, 2) . '/composer.json';
        if (!file_exists($composerFile)) {
            return '2.1'; // Fallback version
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        $versionConstraint = $composer['require-dev']['fisharebest/webtrees'] ?? '^2.2';

        // Parse version constraint like "^2.2" to get "2.2"
        // Remove constraint operators: ^, ~, >=, <=, >, <
        $version = preg_replace('/^[\^~><=]+/', '', $versionConstraint);

        // If we have something like "2.2.0", use "2.2"
        // If we have "2.2", use it as is
        $parts = explode('.', $version);
        if (count($parts) >= 2) {
            return $parts[0] . '.' . $parts[1];
        }

        return $version ?: '2.1'; // Fallback if parsing fails
    }

}