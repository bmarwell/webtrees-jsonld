<?php

/**
 * Bootstrap file for integration tests
 * 
 * This ensures the custom Docker image is built before running integration tests.
 * This allows tests to run from IDEs (like PhpStorm) without manual preparation.
 */

require __DIR__ . '/../vendor/autoload.php';

// Check if we're running integration tests
// Strategy: Check argv, composer script context, or test file patterns
$runningIntegrationTests = false;

// Check if phpunit is being run with integration test suite
if (isset($_SERVER['argv'])) {
    $argString = implode(' ', $_SERVER['argv']);
    
    // Check for explicit test suite
    if (strpos($argString, '--testsuite') !== false && strpos($argString, 'Integration') !== false) {
        $runningIntegrationTests = true;
    }
    
    // Check for integration test files or directories
    if (!$runningIntegrationTests) {
        foreach ($_SERVER['argv'] as $arg) {
            if (strpos($arg, 'tests/Integration') !== false || 
                strpos($arg, 'tests\\Integration') !== false ||
                strpos($arg, 'Integration') !== false && strpos($arg, 'Test.php') !== false) {
                $runningIntegrationTests = true;
                break;
            }
        }
    }
}

// Check composer script context via environment or cwd
if (!$runningIntegrationTests && getenv('COMPOSER_BINARY')) {
    $runningIntegrationTests = true; // Assume integration when run via composer test:integration
}

if ($runningIntegrationTests) {
    // Ensure Docker image is built
    $dockerfilePath = __DIR__ . '/Integration/php-apache';
    $imageName = 'webtrees-php:8-apache-mysqli';
    
    // Check if image exists
    exec('docker images ' . escapeshellarg($imageName) . ' --format "{{.Repository}}:{{.Tag}}" 2>&1', $output, $returnCode);
    $imageExists = false;
    
    foreach ($output as $line) {
        if (strpos($line, $imageName) !== false) {
            $imageExists = true;
            break;
        }
    }
    
    if (!$imageExists) {
        echo "Building custom Docker image for integration tests...\n";
        
        // Build the image
        $buildCommand = sprintf(
            'docker build -t %s -f %s %s 2>&1',
            escapeshellarg($imageName),
            escapeshellarg($dockerfilePath . '/Containerfile'),
            escapeshellarg($dockerfilePath)
        );
        
        exec($buildCommand, $buildOutput, $buildReturnCode);
        
        if ($buildReturnCode !== 0) {
            fwrite(STDERR, "ERROR: Failed to build Docker image for integration tests.\n");
            fwrite(STDERR, "Command: $buildCommand\n");
            fwrite(STDERR, "Output:\n" . implode("\n", $buildOutput) . "\n");
            exit(1);
        }
        
        echo "✓ Docker image built successfully\n";
    }
}
