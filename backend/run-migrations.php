#!/usr/bin/env php
<?php

/**
 * Manual Migration Runner
 * This script bypasses the CLI routing system and runs migrations directly
 */

// Path to the front controller
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
chdir(FCPATH);

// Load our paths config file
$pathsConfig = FCPATH . '../app/Config/Paths.php';
require realpath($pathsConfig) ?: $pathsConfig;

$paths = new Config\Paths();

// Define the path to composer's autoload file.
if (! defined('COMPOSER_PATH')) {
    define('COMPOSER_PATH', realpath(FCPATH . '../vendor/autoload.php') ?: FCPATH . '../vendor/autoload.php');
}

// Define ENVIRONMENT early
if (! defined('ENVIRONMENT')) {
    define('ENVIRONMENT', $_SERVER['CI_ENVIRONMENT'] ?? 'development');
}

// Define CI_DEBUG for the logger
if (! defined('CI_DEBUG')) {
    define('CI_DEBUG', ENVIRONMENT !== 'production');
}

// Location of the framework bootstrap file.
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Load environment settings from .env files into $_SERVER and $_ENV
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

// Load up our current environment file
require APPPATH . 'Config/Boot/' . ENVIRONMENT . '.php';

// Now manually run migrations
echo "==========================================\n";
echo "Running Database Migrations\n";
echo "==========================================\n\n";

try {
    // Get the migration service
    $migrate = \Config\Services::migrations();

    // Run all migrations
    $result = $migrate->latest();

    if ($result === false) {
        echo "Error running migrations:\n";
        echo $migrate->getCliMessages() . "\n";
        exit(1);
    }

    echo "Migrations completed successfully!\n";
    echo $migrate->getCliMessages() . "\n";

} catch (\Throwable $e) {
    echo "Exception occurred: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n==========================================\n";
echo "Migration process completed\n";
echo "==========================================\n";
