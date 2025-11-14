<?php

/**
 * Run integration tests with testcontainers
 * 
 * This script runs the integration tests that use testcontainers to spin up
 * Docker containers for testing the JsonLD module.
 * 
 * Prerequisites:
 * - Docker must be installed and running
 * - Distribution must be built (run `composer dist` first)
 */

require __DIR__ . '/../vendor/autoload.php';

echo "=================================================\n";
echo "JsonLD Module - Integration Tests Runner\n";
echo "=================================================\n\n";

// Check if Docker is available
echo "Checking Docker availability...\n";
exec('docker info 2>&1', $output, $returnCode);
if ($returnCode !== 0) {
    echo "ERROR: Docker is not available or not running.\n";
    echo "Please make sure Docker is installed and running.\n";
    exit(1);
}
echo "✓ Docker is available\n\n";

// Check if distribution is built
$distDir = __DIR__ . '/../build/jsonld';
if (!is_dir($distDir)) {
    echo "ERROR: Distribution not found at: $distDir\n";
    echo "Please run 'composer dist' or 'make dist' first to build the distribution.\n";
    exit(1);
}
echo "✓ Distribution found at: $distDir\n\n";

// Run integration tests
echo "Running integration tests...\n";
echo "---------------------------------------------------\n";

$phpunitBin = __DIR__ . '/../vendor/bin/phpunit';
$command = sprintf(
    '%s --testsuite="Integration Tests" --testdox --coverage-clover=coverage/integration.xml',
    escapeshellarg($phpunitBin)
);

passthru($command, $exitCode);

echo "\n---------------------------------------------------\n";
if ($exitCode === 0) {
    echo "✓ All integration tests passed!\n";
} else {
    echo "✗ Some integration tests failed (exit code: $exitCode)\n";
}

exit($exitCode);
