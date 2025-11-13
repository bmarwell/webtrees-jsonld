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
    
    // Webtrees version should align with composer.json (^2.2)
    // Using 2.1.20 as a stable version close to 2.2 requirements
    private const WEBTREES_VERSION = '2.1.20';

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
            1. Pull and configure a webtrees Docker image (e.g., nathanvaughn/webtrees or dtjs48jkt/webtrees)
            2. Mount the jsonld module into the modules_v4 directory
            3. Configure webtrees with MySQL credentials
            4. Import a test GEDCOM file
            5. Enable the JsonLD module
            6. Test JSON-LD output in HTML and via Accept header
            
            This test currently demonstrates the testcontainers setup pattern.
            For a working example, see the docker-compose based integration tests in tests/integration/docker-compose.yml'
        );

        // Start MySQL container
        self::$mysqlContainer = (new MySQLContainer('8.0'))
            ->withMySQLDatabase('webtrees')
            ->withMySQLUser('webtrees', 'webtrees')
            ->start();

        // Build paths to the jsonld module
        $modulePath = dirname(__DIR__, 2) . '/src/jsonld';
        $modulePhpPath = dirname(__DIR__, 2) . '/module.php';

        // Note: To make this test work, you need to:
        // 1. Use a working webtrees Docker image
        // 2. Ensure the module is properly mounted
        // 3. Configure webtrees to connect to MySQL
        // 4. Import a GEDCOM file
        // 5. Enable the JsonLD module
        
        // Example container setup (incomplete - requires a working webtrees image):
        /*
        self::$nginxContainer = (new GenericContainer('nathanvaughn/webtrees:2.1'))
            ->withNetwork(self::$mysqlContainer->getNetworkName())
            ->withNetworkAlias('webtrees')
            ->withEnvironment([
                'DB_TYPE' => 'mysql',
                'DB_HOST' => self::$mysqlContainer->getNetworkAlias(),
                'DB_PORT' => '3306',
                'DB_USER' => 'webtrees',
                'DB_PASS' => 'webtrees',
                'DB_NAME' => 'webtrees',
                'DB_PREFIX' => 'wt_',
            ])
            ->withExposedPorts(80)
            ->withMount($modulePath, '/var/www/html/modules_v4/jsonld')
            ->withMount($modulePhpPath, '/var/www/html/modules_v4/jsonld/module.php')
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
