<?php

/**
 * Prepare environment for integration tests
 * 
 * This script builds the custom Docker image required for integration tests.
 * The custom image extends php:8-apache with mysqli and pdo_mysql extensions.
 * 
 * Prerequisites:
 * - Docker must be installed and running
 */

require __DIR__ . '/../vendor/autoload.php';

echo "=================================================\n";
echo "JsonLD Module - Integration Test Preparation\n";
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

// Build custom webtrees PHP image with mysqli and pdo_mysql support
echo "Building custom PHP image with mysqli and pdo_mysql extensions...\n";
$dockerfilePath = __DIR__ . '/../tests/Integration/php-apache';
$imageName = 'webtrees-php:8-apache-mysqli';

$buildCommand = sprintf(
    'docker build -t %s -f %s %s 2>&1',
    escapeshellarg($imageName),
    escapeshellarg($dockerfilePath . '/Containerfile'),
    escapeshellarg($dockerfilePath)
);

$buildOutput = [];
exec($buildCommand, $buildOutput, $buildReturnCode);

if ($buildReturnCode !== 0) {
    echo "ERROR: Failed to build Docker image.\n";
    echo "Command: $buildCommand\n";
    echo "Output:\n" . implode("\n", $buildOutput) . "\n";
    exit(1);
}

echo "✓ Custom PHP image built successfully: $imageName\n\n";
echo "Integration test environment is ready!\n";

exit(0);
