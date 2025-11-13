<?php

declare(strict_types=1);

namespace bmhm\WebtreesModules\jsonld\test\integration;

use PHPUnit\Framework\TestCase;
use Testcontainers\Container\GenericContainer;
use Testcontainers\Modules\MySQLContainer;
use Testcontainers\Wait\WaitForLog;
use Testcontainers\Wait\WaitForHttp;

/**
 * Integration test for JsonLD module using Testcontainers.
 * 
 * This test spins up a complete webtrees environment with MySQL and tests
 * the JSON-LD functionality including:
 * - JSON-LD embedded in HTML pages (script tag)
 * - JSON-LD via Accept header (content negotiation)
 * 
 * Note: This test uses webtrees version compatible with composer.json requirements (^2.2).
 * 
 * Requirements:
 * - Docker must be installed and running
 * - Sufficient disk space for pulling Docker images
 * - Network access to Docker Hub
 * 
 * The test will be skipped if Docker is not available.
 */
class WebtreesJsonLdIntegrationTest extends TestCase
{
    private static ?MySQLContainer $mysqlContainer = null;
    private static ?GenericContainer $phpFpmContainer = null;
    private static ?GenericContainer $nginxContainer = null;
    private static ?string $webtreesUrl = null;
    
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

    /**
     * Check if Docker is available before running tests.
     */
    public static function setUpBeforeClass(): void
    {
        // Check if Docker is available
        exec('docker info 2>&1', $output, $returnCode);
        if ($returnCode !== 0) {
            self::markTestSkipped('Docker is not available. Skipping integration tests.');
        }
    }

    /**
     * Start MySQL, PHP-FPM, and Nginx containers to create a webtrees environment.
     */
    protected function setUp(): void
    {
        $this->markTestIncomplete(
            'Integration test skeleton created. To complete this test:
            1. Build a custom PHP container with required extensions
            2. Download and extract webtrees from GitHub releases
            3. Mount the jsonld module into the modules_v4 directory
            4. Configure webtrees with MySQL credentials
            5. Import a test GEDCOM file
            6. Enable the JsonLD module
            7. Test JSON-LD output in HTML and via Accept header
            
            This test currently demonstrates the testcontainers setup pattern.
            See the buildWebtreesDockerfile() method for the custom image approach.'
        );

        // Start MySQL container
        self::$mysqlContainer = (new MySQLContainer('8.0'))
            ->withMySQLDatabase('webtrees')
            ->withMySQLUser('webtrees', 'webtrees')
            ->start();

        // Build paths to the jsonld module
        $modulePath = dirname(__DIR__, 2) . '/src/jsonld';
        $modulePhpPath = dirname(__DIR__, 2) . '/module.php';
        
        // Get webtrees version from composer.json
        $webtreesVersion = self::getWebtreesVersion();

        // Note: To make this test work, you would:
        // 1. Build a custom PHP image with webtrees (see buildWebtreesDockerfile())
        // 2. Use GenericContainer->withDockerfile() to build from the Dockerfile
        // 3. Configure and start the container
        // 4. Set up webtrees headlessly
        // 5. Import test GEDCOM data
        // 6. Enable the JsonLD module
        
        // Example setup (commented out until fully implemented):
        /*
        $dockerfile = self::buildWebtreesDockerfile($webtreesVersion);
        
        self::$phpFpmContainer = (new GenericContainer())
            ->withDockerfile($dockerfile)
            ->withNetwork(self::$mysqlContainer->getNetworkName())
            ->withNetworkAlias('app')
            ->withEnvironment([
                'DB_TYPE' => 'mysql',
                'DB_HOST' => self::$mysqlContainer->getNetworkAlias(),
                'DB_PORT' => '3306',
                'DB_USER' => 'webtrees',
                'DB_PASS' => 'webtrees',
                'DB_NAME' => 'webtrees',
                'DB_PREFIX' => 'wt_',
            ])
            ->withMount($modulePath, '/var/www/html/modules_v4/jsonld')
            ->withMount($modulePhpPath, '/var/www/html/modules_v4/jsonld/module.php')
            ->start();

        // Start Nginx container
        self::$nginxContainer = (new GenericContainer('nginx:alpine'))
            ->withNetwork(self::$mysqlContainer->getNetworkName())
            ->withNetworkAlias('web')
            ->withExposedPorts(80)
            ->withCopyFileToContainer(
                dirname(__DIR__) . '/integration/nginx-webtrees/nginx-webtrees.conf',
                '/etc/nginx/conf.d/default.conf'
            )
            ->withMount(dirname(__DIR__, 2) . '/webtrees', '/var/www/html')
            ->withWait(new WaitForHttp(80, 'GET', '/'))
            ->start();

        self::$webtreesUrl = sprintf(
            'http://%s:%d',
            self::$nginxContainer->getHost(),
            self::$nginxContainer->getFirstMappedPort()
        );
        */
    }
    
    /**
     * Build a Dockerfile for a custom PHP image with webtrees.
     * 
     * @param string $webtreesVersion The webtrees version to download
     * @return string The Dockerfile content
     */
    private static function buildWebtreesDockerfile(string $webtreesVersion): string
    {
        // Note: This uses the webtrees version from composer.json
        // For webtrees 2.2+, we need PHP 8.1+ and specific extensions
        
        return <<<DOCKERFILE
FROM php:8-fpm-alpine

# Install required system dependencies for Alpine
RUN apk add --no-cache \\
    freetype-dev \\
    libjpeg-turbo-dev \\
    libpng-dev \\
    libzip-dev \\
    zlib-dev \\
    icu-dev \\
    unzip \\
    curl

# Configure and install PHP extensions required by webtrees
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \\
    && docker-php-ext-install -j\$(nproc) \\
        gd \\
        zip \\
        pdo_mysql \\
        mysqli \\
        intl \\
        exif

# Download and extract webtrees
WORKDIR /tmp
RUN curl -L https://github.com/fisharebest/webtrees/releases/download/${webtreesVersion}/webtrees-${webtreesVersion}.zip -o webtrees.zip \\
    && unzip webtrees.zip -d /var/www/html \\
    && rm webtrees.zip \\
    && chown -R www-data:www-data /var/www/html \\
    && chmod -R 755 /var/www/html

# Create modules_v4 directory if it doesn't exist
RUN mkdir -p /var/www/html/modules_v4 \\
    && chown -R www-data:www-data /var/www/html/modules_v4

# Create data directory for GEDCOM files
RUN mkdir -p /var/www/html/data \\
    && chown -R www-data:www-data /var/www/html/data \\
    && chmod -R 777 /var/www/html/data

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
DOCKERFILE;
    }
    
    /**
     * Copy the JsonLD module to the webtrees container.
     * 
     * This should be called after the PHP-FPM container is started.
     * The module files will be copied from the repository to the container's modules_v4 directory.
     * 
     * @param GenericContainer $container The PHP-FPM container
     */
    private static function copyModuleToContainer(GenericContainer $container): void
    {
        $modulePath = dirname(__DIR__, 2) . '/src/jsonld';
        $modulePhpPath = dirname(__DIR__, 2) . '/module.php';
        
        // Copy the module directory
        // Note: In a real implementation, you would use container->exec() to copy files
        // or mount the module directory as a volume when starting the container
        
        // Example commands to copy module (would need to be executed via container->exec()):
        // docker cp $modulePath container:/var/www/html/modules_v4/jsonld
        // docker cp $modulePhpPath container:/var/www/html/modules_v4/jsonld/module.php
    }
    
    /**
     * Import a GEDCOM file into webtrees.
     * 
     * This creates a minimal GEDCOM file with test data and imports it into webtrees.
     * The import can be done via webtrees CLI or by placing the file in the data directory
     * and triggering import through the web interface.
     * 
     * @param GenericContainer $container The PHP-FPM container
     * @param string $webtreesUrl The URL to access webtrees
     */
    private static function importGedcomFile(GenericContainer $container, string $webtreesUrl): void
    {
        // Create a minimal test GEDCOM file
        $gedcomContent = self::createTestGedcom();
        
        // Save GEDCOM to temporary file
        $tempGedcom = tempnam(sys_get_temp_dir(), 'test_') . '.ged';
        file_put_contents($tempGedcom, $gedcomContent);
        
        // Copy GEDCOM to container's data directory
        // Note: In a real implementation, you would use:
        // $container->copyFileToContainer($tempGedcom, '/var/www/html/data/test.ged');
        
        // Then trigger import via webtrees CLI or API
        // Example: $container->exec(['php', '/var/www/html/index.php', '--import', '/var/www/html/data/test.ged']);
        // Or via HTTP API call to $webtreesUrl
        
        unlink($tempGedcom);
    }
    
    /**
     * Create a minimal test GEDCOM file with sample individuals.
     * 
     * @return string The GEDCOM file content
     */
    private static function createTestGedcom(): string
    {
        return <<<GEDCOM
0 HEAD
1 SOUR webtrees
2 VERS 2.2
2 NAME webtrees
1 DEST DISKETTE
1 DATE 13 NOV 2025
1 SUBM @SUBM1@
1 FILE test.ged
1 GEDC
2 VERS 5.5.1
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @SUBM1@ SUBM
1 NAME Test Submitter
0 @I1@ INDI
1 NAME John /Doe/
2 GIVN John
2 SURN Doe
1 SEX M
1 BIRT
2 DATE 1 JAN 1950
2 PLAC New York, USA
1 DEAT
2 DATE 15 MAR 2020
2 PLAC Boston, USA
1 OCCU Software Developer
0 @I2@ INDI
1 NAME Jane /Smith/
2 GIVN Jane
2 SURN Smith
1 SEX F
1 BIRT
2 DATE 5 JUN 1952
2 PLAC London, England
1 OCCU Teacher
1 FAMS @F1@
0 @I3@ INDI
1 NAME Robert /Doe/
2 GIVN Robert
2 SURN Doe
1 SEX M
1 BIRT
2 DATE 10 OCT 1975
2 PLAC Chicago, USA
1 OCCU Engineer
1 FAMC @F1@
0 @F1@ FAM
1 HUSB @I1@
1 WIFE @I2@
1 CHIL @I3@
1 MARR
2 DATE 20 JUL 1974
2 PLAC New York, USA
0 TRLR
GEDCOM;
    }

    /**
     * Clean up containers after each test.
     */
    protected function tearDown(): void
    {
        if (self::$nginxContainer !== null) {
            self::$nginxContainer->stop();
            self::$nginxContainer = null;
        }
        
        if (self::$phpFpmContainer !== null) {
            self::$phpFpmContainer->stop();
            self::$phpFpmContainer = null;
        }
        
        if (self::$mysqlContainer !== null) {
            self::$mysqlContainer->stop();
            self::$mysqlContainer = null;
        }
    }

    /**
     * Test that containers can be started (basic testcontainers functionality).
     */
    public function testDockerAvailable(): void
    {
        // This test verifies that Docker is available and we can start a MySQL container
        $this->assertNotNull(self::$mysqlContainer, 'MySQL container should be started');
        $this->assertIsString(self::$mysqlContainer->getHost(), 'Container should have a host');
        $this->assertIsInt(self::$mysqlContainer->getFirstMappedPort(), 'Container should have mapped ports');
    }

    /**
     * Test that JSON-LD is embedded in HTML pages.
     * 
     * This test demonstrates what should be tested once the webtrees environment is set up:
     * 1. Request an individual page
     * 2. Verify the response contains a <script type="application/ld+json"> tag
     * 3. Parse and validate the JSON-LD content
     * 4. Verify it contains expected schema.org properties like @context, @type: Person, etc.
     */
    public function testJsonLdEmbeddedInHtml(): void
    {
        $this->markTestIncomplete('Requires complete webtrees setup with GEDCOM data');
        
        // Example of what this test should do:
        // $response = $this->makeHttpRequest(self::$webtreesUrl . '/index.php?route=/individual/I1/tree');
        // $this->assertStringContainsString('application/ld+json', $response);
        // $this->assertStringContainsString('"@context":"http://schema.org"', $response);
        // $this->assertStringContainsString('"@type":"Person"', $response);
    }

    /**
     * Test JSON-LD via Accept header (content negotiation).
     * 
     * This test demonstrates what should be tested once the webtrees environment is set up:
     * 1. Request an individual page with Accept: application/ld+json header
     * 2. Verify Content-Type: application/ld+json; charset=utf-8
     * 3. Verify the response is pure JSON (not HTML)
     * 4. Parse and validate the JSON-LD structure
     */
    public function testJsonLdViaAcceptHeader(): void
    {
        $this->markTestIncomplete('Requires complete webtrees setup with GEDCOM data');
        
        // Example of what this test should do:
        // $ch = curl_init(self::$webtreesUrl . '/index.php?route=/individual/I1/tree');
        // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/ld+json']);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HEADER, true);
        // $response = curl_exec($ch);
        // curl_close($ch);
        // 
        // $this->assertStringContainsString('Content-Type: application/ld+json; charset=utf-8', $response);
        // $jsonStart = strpos($response, "\r\n\r\n");
        // $json = substr($response, $jsonStart + 4);
        // $data = json_decode($json, true);
        // $this->assertIsArray($data);
        // $this->assertEquals('http://schema.org', $data['@context']);
        // $this->assertEquals('Person', $data['@type']);
    }

    /**
     * Test that normal HTML requests include Link header advertising JSON-LD.
     * 
     * This test demonstrates what should be tested once the webtrees environment is set up:
     * 1. Request an individual page normally (without Accept header)
     * 2. Verify the response includes a Link header
     * 3. Verify the Link header specifies rel="alternate" and type="application/ld+json"
     */
    public function testLinkHeaderInHtmlResponse(): void
    {
        $this->markTestIncomplete('Requires complete webtrees setup with GEDCOM data');
        
        // Example of what this test should do:
        // $ch = curl_init(self::$webtreesUrl . '/index.php?route=/individual/I1/tree');
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HEADER, true);
        // $response = curl_exec($ch);
        // curl_close($ch);
        // 
        // $this->assertStringContainsString('Link:', $response);
        // $this->assertStringContainsString('rel="alternate"', $response);
        // $this->assertStringContainsString('type="application/ld+json"', $response);
    }

    /**
     * Helper method to make HTTP requests.
     */
    private function makeHttpRequest(string $url, array $headers = []): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return is_string($response) ? $response : '';
    }
}
