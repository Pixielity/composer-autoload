<?php

/**
 * Bootstrap file for ComposerAutoload module tests
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base paths
define('COMPOSER_AUTOLOAD_TEST_ROOT', dirname(__DIR__));
define('COMPOSER_AUTOLOAD_SRC_ROOT', COMPOSER_AUTOLOAD_TEST_ROOT.'/src');
define('COMPOSER_AUTOLOAD_TEST_TEMP', sys_get_temp_dir().'/composer-autoload-tests');

// Clean up temp directory
if (is_dir(COMPOSER_AUTOLOAD_TEST_TEMP)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(COMPOSER_AUTOLOAD_TEST_TEMP, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    rmdir(COMPOSER_AUTOLOAD_TEST_TEMP);
}

// Create temp directory
if (! is_dir(COMPOSER_AUTOLOAD_TEST_TEMP)) {
    mkdir(COMPOSER_AUTOLOAD_TEST_TEMP, 0755, true);
}

// Load Composer autoloader
require_once COMPOSER_AUTOLOAD_TEST_ROOT.'/vendor/autoload.php';

// Create test helper functions
if (! function_exists('createTempFile')) {
    function createTempFile(string $content = '', string $extension = '.php'): string
    {
        $tempFile = tempnam(COMPOSER_AUTOLOAD_TEST_TEMP, 'test').$extension;
        file_put_contents($tempFile, $content);

        return $tempFile;
    }
}

if (! function_exists('createTempDirectory')) {
    function createTempDirectory(string $prefix = 'test'): string
    {
        $tempDir = COMPOSER_AUTOLOAD_TEST_TEMP.'/'.$prefix.'_'.uniqid();
        mkdir($tempDir, 0755, true);

        return $tempDir;
    }
}

if (! function_exists('cleanupTempFiles')) {
    function cleanupTempFiles(): void
    {
        if (is_dir(COMPOSER_AUTOLOAD_TEST_TEMP)) {
            $files = glob(COMPOSER_AUTOLOAD_TEST_TEMP.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                } elseif (is_dir($file)) {
                    rmdir($file);
                }
            }
        }
    }
}

// Set up test environment variables
$_ENV['APP_ENV'] = 'testing';
$_ENV['COMPOSER_AUTOLOAD_DEBUG'] = 'true';
