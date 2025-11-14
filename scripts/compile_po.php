<?php

/**
 * Compile .po files to .mo files using PHP Gettext library
 * 
 * This script scans the language directory for .po files and compiles them to .mo files.
 */

require __DIR__ . '/../vendor/autoload.php';

use Gettext\Translations;
use Gettext\Loader\PoLoader;
use Gettext\Generator\MoGenerator;

$languageDir = __DIR__ . '/../src/jsonld/language';

if (!is_dir($languageDir)) {
    echo "Language directory not found: $languageDir\n";
    exit(1);
}

// Scan for .po files in the language directory
$poFiles = glob($languageDir . '/*.po');

if (empty($poFiles)) {
    echo "No .po files found in $languageDir\n";
    echo "Skipping compilation.\n";
    exit(0);
}

$loader = new PoLoader();
$generator = new MoGenerator();

foreach ($poFiles as $poFile) {
    $moFile = preg_replace('/\.po$/', '.mo', $poFile);
    
    try {
        // Load translations from .po file
        $translations = $loader->loadFile($poFile);
        
        // Generate .mo file
        $generator->generateFile($translations, $moFile);
        
        echo "Compiled $poFile → $moFile\n";
    } catch (Exception $e) {
        echo "Error compiling $poFile: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "All .po files compiled successfully.\n";
