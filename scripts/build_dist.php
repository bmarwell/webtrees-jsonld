<?php

/**
 * Build distribution package for the JsonLD module
 * 
 * This script assembles the module distribution in the build directory.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Filesystem\Filesystem;

$fs = new Filesystem();

$buildDir = __DIR__ . '/../build';
$distDir = $buildDir . '/jsonld';

// Clean and recreate distribution directory
if ($fs->exists($distDir)) {
    echo "Cleaning existing distribution directory...\n";
    $fs->remove($distDir);
}

$fs->mkdir($distDir);
echo "Created distribution directory: $distDir\n";

// Copy source files
$sourceDir = __DIR__ . '/../src/jsonld';
if ($fs->exists($sourceDir)) {
    $fs->mirror($sourceDir, $distDir);
    echo "Copied src/jsonld → build/jsonld\n";
} else {
    echo "Warning: Source directory not found: $sourceDir\n";
}

// Copy module.php to distribution
$moduleFile = __DIR__ . '/../module.php';
if ($fs->exists($moduleFile)) {
    $fs->copy($moduleFile, $distDir . '/module.php');
    echo "Copied module.php → build/jsonld/module.php\n";
} else {
    echo "Warning: module.php not found: $moduleFile\n";
}

// Copy README and LICENSE files
$readmeFile = __DIR__ . '/../README.md';
if ($fs->exists($readmeFile)) {
    $fs->copy($readmeFile, $distDir . '/README.md');
    echo "Copied README.md → build/jsonld/README.md\n";
}

$licenseFiles = [
    'LICENSE',
    'LICENSE-Apache-2.0',
    'LICENSE-EUPL-1.2'
];

foreach ($licenseFiles as $licenseFile) {
    $sourceLicense = __DIR__ . '/../' . $licenseFile;
    if ($fs->exists($sourceLicense)) {
        $fs->copy($sourceLicense, $distDir . '/' . $licenseFile);
        echo "Copied $licenseFile → build/jsonld/$licenseFile\n";
    }
}

echo "\nDistribution assembled successfully in: $distDir\n";
echo "Module is ready for deployment to webtrees modules_v4 directory.\n";
