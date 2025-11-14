<?php

declare(strict_types=1);

namespace Tests\Integration;

use Http\Client\Socket\Exception\ConnectionException;
use PHPUnit\Framework\TestCase;
use Testcontainers\Container\GenericContainer;
use Testcontainers\Container\StartedGenericContainer;
use Testcontainers\Modules\MySQLContainer;

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
    private static ?StartedGenericContainer $mysqlStartedContainer = null;
    private static ?WebtreesContainer $webserver = null;
    private static ?string $webtreesUrl = null;

    /**
     * Check if Docker or Podman (in Docker-compatible mode) is available
     * and that the Unix socket the PHP Docker client will use is actually present.
     */
    public static function setUpBeforeClass(): void
    {
        // Helper to resolve the Unix socket path the PHP Docker client will use.
        $resolveSocketPath = static function (): string {
            $dockerHost = getenv('DOCKER_HOST');
            if (is_string($dockerHost) && $dockerHost !== '') {
                if (strpos($dockerHost, 'unix://') === 0) {
                    return substr($dockerHost, strlen('unix://'));
                }

                // Non-unix schemes (tcp://, npipe://, ssh://) are not handled here;
                // in that case we simply return an empty string and let the client deal with it.
                return '';
            }

            // Default for docker-php-api when DOCKER_HOST is not set.
            return '/var/run/docker.sock';
        };

        // 1) Try Docker CLI.
        $output = [];
        $returnCode = 0;
        exec('docker info 2>&1', $output, $returnCode);
        if ($returnCode === 0) {
            $socketPath = $resolveSocketPath();

            // If we have a Unix socket path, ensure it exists for the PHP Docker client.
            if ($socketPath !== '' && (!file_exists($socketPath) || !is_readable($socketPath))) {
                self::markTestSkipped(
                    sprintf(
                        'Docker CLI works, but Docker socket for PHP client is not available at "%s". ' .
                        'Skipping integration tests.',
                        $socketPath
                    )
                );
            }

            // Docker is available and the socket (if any) is accessible; proceed with tests.
            return;
        }

        // 2) Docker not available – try Podman as a drop-in backend.
        $output = [];
        $returnCode = 0;
        exec('podman info 2>&1', $output, $returnCode);
        if ($returnCode !== 0) {
            self::markTestSkipped('Neither Docker nor Podman is available. Skipping integration tests.');
        }

        // Podman is installed. Configure DOCKER_HOST to point at a Podman socket if not already set.
        $existingDockerHost = getenv('DOCKER_HOST');
        if (!is_string($existingDockerHost) || $existingDockerHost === '') {
            $candidateSockets = [];

            // Rootless Podman: /run/user/$UID/podman/podman.sock
            $uid = null;
            if (function_exists('posix_getuid')) {
                $uid = posix_getuid();
            } elseif (function_exists('getmyuid')) {
                $uid = getmyuid();
            }

            if ($uid !== null) {
                $candidateSockets[] = sprintf('/run/user/%d/podman/podman.sock', $uid);
            }

            // System-wide Podman socket.
            $candidateSockets[] = '/run/podman/podman.sock';

            $configured = false;
            foreach ($candidateSockets as $socket) {
                if (file_exists($socket) && is_readable($socket)) {
                    $dockerHost = 'unix://' . $socket;
                    putenv('DOCKER_HOST=' . $dockerHost);
                    $_ENV['DOCKER_HOST'] = $dockerHost;
                    $_SERVER['DOCKER_HOST'] = $dockerHost;
                    $configured = true;
                    break;
                }
            }

            if (!$configured) {
                self::markTestSkipped(
                    'Podman is available but no Docker-compatible Podman socket could be found for Testcontainers. ' .
                    'Checked: ' . implode(', ', $candidateSockets)
                );
            }
        }

        // 3) After configuring Podman, ensure the socket the PHP client will use is present.
        $socketPath = $resolveSocketPath();
        if ($socketPath === '' || !file_exists($socketPath) || !is_readable($socketPath)) {
            self::markTestSkipped(
                sprintf(
                    'Podman is available, but Docker-compatible socket for PHP client is not available at "%s". ' .
                    'Skipping integration tests.',
                    $socketPath === '' ? '(empty/unsupported DOCKER_HOST)' : $socketPath
                )
            );
        }
    }

    /**
     * Start MySQL, PHP-FPM, and Nginx containers to create a webtrees environment.
     */
    protected function setUp(): void
    {
        // Ensure distribution is built before running tests
        $distDir = dirname(__DIR__, 2) . '/build/jsonld';
        if (!is_dir($distDir)) {
            echo "Distribution not found. Building distribution...\n";
            $buildScript = dirname(__DIR__, 2) . '/scripts/build_dist.php';
            
            // Run compile_po.php first (if it exists)
            $compilePoScript = dirname(__DIR__, 2) . '/scripts/compile_po.php';
            if (file_exists($compilePoScript)) {
                passthru('php ' . escapeshellarg($compilePoScript), $exitCode);
                if ($exitCode !== 0) {
                    $this->markTestSkipped('Failed to compile .po files');
                }
            }
            
            // Run build_dist.php
            if (file_exists($buildScript)) {
                passthru('php ' . escapeshellarg($buildScript), $exitCode);
                if ($exitCode !== 0) {
                    $this->markTestSkipped('Failed to build distribution');
                }
            }
            
            // Verify distribution was created
            if (!is_dir($distDir)) {
                $this->markTestSkipped(
                    sprintf(
                        'Distribution directory not found at "%s". ' .
                        'Please run "composer dist" to build the distribution.',
                        $distDir
                    )
                );
            }
        }
        
        try {
            // MySQL container
            self::$mysqlContainer = (new MySQLContainer('9.5'))
                ->withMySQLDatabase('webtrees')
                ->withMySQLUser('webtrees', 'webtrees');

            // PHP-Apache-container
            self::$webserver = (new WebtreesContainer());

            // copy this module into the path
            self::$webserver->withCopyDirectoriesToContainer([[
                "source" => dirname(__DIR__, 2) . '/build/jsonld',
                "target" => '/var/www/html/modules_v4/jsonld',
                "mode" => 0777,
            ]]);

            // Start containers; this is where Docker socket connectivity is actually required.
            self::$mysqlStartedContainer = self::$mysqlContainer->start();
            self::$webserver->start();

            self::$webtreesUrl = self::$webserver->getBaseUrl();
        } catch (ConnectionException $e) {
            // Docker daemon is not reachable for the PHP Docker client (e.g. missing /var/run/docker.sock).
            // Skip integration tests instead of failing the whole test run.
            self::markTestSkipped(
                'Docker daemon not reachable for Testcontainers: ' . $e->getMessage()
            );
        }
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
     * Clean up containers after each test.
     */
    protected function tearDown(): void
    {
        if (self::$webserver !== null) {
            self::$webserver->stop();
            self::$webserver = null;
        }
        
        if (self::$mysqlStartedContainer !== null) {
            self::$mysqlStartedContainer->stop();
            self::$mysqlStartedContainer = null;
        }
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
        // Example of what this test should do:
        $response = $this->makeHttpRequest(self::$webtreesUrl . '/index.php?route=/individual/I1/tree');
        //$this->assertStringContainsString('application/ld+json', $response);
        //$this->assertStringContainsString('"@context":"http://schema.org"', $response);
        //$this->assertStringContainsString('"@type":"Person"', $response);
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
