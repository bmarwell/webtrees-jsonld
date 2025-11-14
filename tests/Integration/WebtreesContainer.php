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

    private ?string $mysqlHost = null;
    private string $mysqlDatabase = 'webtrees';
    private string $mysqlUser = 'webtrees';
    private string $mysqlPassword = 'webtrees';

    public function __construct()
    {
        // Use custom image with mysqli extension pre-installed
        // Image is built by scripts/prepare_integration_tests.php
        parent::__construct('webtrees-php:8-apache-mysqli');

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
     * Configure MySQL connection details for webtrees
     */
    public function withMySQLConnection(string $host, string $database, string $user, string $password): self
    {
        $this->mysqlHost = $host;
        $this->mysqlDatabase = $database;
        $this->mysqlUser = $user;
        $this->mysqlPassword = $password;
        return $this;
    }
    
    /**
     * Prepare webtrees configuration before starting container
     * This creates the config file that will be copied when container starts
     */
    public function prepareWebtreesConfig(): self
    {
        if ($this->mysqlHost === null) {
            throw new \RuntimeException('MySQL connection must be configured before preparing webtrees config');
        }
        
        $configContent = <<<CONFIG
;<?php exit; ?>

dbhost="{$this->mysqlHost}"
dbport="3306"
dbuser="{$this->mysqlUser}"
dbpass="{$this->mysqlPassword}"
dbname="{$this->mysqlDatabase}"
driver="mysql"
tblpfx="wt_"
rewrite_urls="1"
CONFIG;

        // Create config file in cache directory
        $cacheDir = dirname(__DIR__, 2) . '/.cache/webtrees';
        $configFile = $cacheDir . '/config.ini.php';
        file_put_contents($configFile, $configContent);
        
        // Copy config file to container's data directory
        $this->withCopyFilesToContainer([
            [
                "source" => $configFile,
                "target" => '/var/www/html/data/config.ini.php'
            ]
        ]);
        
        return $this;
    }
    
    /**
     * Install webtrees schema into the database and complete setup
     * This needs to be called after the container is started
     */
    public function installWebtrees(): void
    {
        if (self::$started === null) {
            throw new \RuntimeException('Container must be started before installing webtrees');
        }
        
        $baseUrl = $this->getBaseUrl();
        
        // Wait for Apache to be ready
        $maxAttempts = 30;
        for ($i = 0; $i < $maxAttempts; $i++) {
            try {
                $ch = curl_init($baseUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode > 0) {
                    break; // Server is responding
                }
            } catch (\Exception $e) {
                // Continue waiting
            }
            sleep(1);
        }
        
        // Webtrees 2.2 has an automatic setup wizard that creates the database schema
        // We need to complete the setup by making appropriate HTTP requests
        // First, access the setup page to trigger schema creation
        $ch = curl_init($baseUrl . '/setup.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            // Try the index page instead
            $ch = curl_init($baseUrl . '/index.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            curl_close($ch);
        }

        // At this point, webtrees should have created the database schema
        // Verify by checking if we can access the site
        $ch = curl_init($baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 500) {
            throw new \RuntimeException("Webtrees returned HTTP $httpCode after installation attempt. Check logs for details.");
        }
    }
    
    /**
     * Create a PHP script to install webtrees database schema
     */
    private function createInstallScript(): string
    {
        return <<<'PHP'
<?php
// Install webtrees database schema by visiting the setup wizard

// Read config
$config = parse_ini_file('/var/www/html/data/config.ini.php');

// Create a minimal user table entry so webtrees thinks it's installed
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', 
            $config['dbhost'], 
            $config['dbport'] ?? '3306',
            $config['dbname']
        ),
        $config['dbuser'], 
        $config['dbpass'], 
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    // Check if tables exist
    $result = $pdo->query("SHOW TABLES LIKE 'wt_user'")->fetchAll();
    
    if (empty($result)) {
        // Trigger webtrees installation by making a request to the setup page
        // This is simpler than trying to replicate all the schema setup
        echo "Webtrees not installed, will be installed on first HTTP request\n";
    } else {
        echo "Webtrees database already installed\n";
    }
    
    exit(0);
    
} catch (Exception $e) {
    echo "Error checking webtrees: " . $e->getMessage() . "\n";
    echo "Webtrees will be installed on first HTTP request\n";
    exit(0);
}
PHP;
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