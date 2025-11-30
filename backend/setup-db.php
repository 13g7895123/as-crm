#!/usr/bin/env php
<?php

/**
 * Database Setup Script
 * This script runs all migrations and seeders to set up the database
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

// Establish the path to the project base so we can use addons, modules, etc
define('ROOTPATH', realpath(FCPATH . '../') . DIRECTORY_SEPARATOR);
define('APPPATH', realpath(FCPATH . '../app') . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath(FCPATH . '../vendor/codeigniter4/framework/system') . DIRECTORY_SEPARATOR);

require COMPOSER_PATH;

// Load environment settings from .env files into $_SERVER and $_ENV
$dotenv = new \Dotenv\Dotenv(ROOTPATH, '.env', false);
$dotenv->load();
$dotenv->required(['database.default.hostname', 'database.default.database', 'database.default.username']);

// Now load the framework
$app = require realpath(ROOTPATH . 'app/Config/Paths.php') === false
    ? rtrim(realpath(FCPATH . '../vendor/codeigniter4/framework/'), '\\/')
    : ROOTPATH;

$app = \Config\Services::autoloader()->addNamespace('Config', APPPATH . 'Config')->getLoader();

require_once APPPATH . 'Config/Constants.php';

// Grab our CodeIgniter instance
$_SERVER['argv'] = array_merge(['cli'], $_SERVER['argv'] ?? []);
$_SERVER['argc'] = count($_SERVER['argv']);

try {
    $app = \Config\Services::app();

    $migrate = \Config\Services::migrations();

    echo "======================================\n";
    echo "Database Setup Started\n";
    echo "======================================\n\n";

    // Run migrations
    echo "Running Migrations...\n";
    try {
        if ($migrate->latest()) {
            echo "✓ Migrations completed successfully\n\n";
        }
    } catch (\Exception $e) {
        echo "✗ Migration Error: " . $e->getMessage() . "\n\n";
        exit(1);
    }

    // Run seeders
    echo "Running Seeders...\n";
    $seeder = \Config\Database::seeder();

    try {
        // Run in order
        $seeder->call('UserSeeder');
        echo "✓ UserSeeder completed\n";

        $seeder->call('RoleSeeder');
        echo "✓ RoleSeeder completed\n";

        $seeder->call('PermissionSeeder');
        echo "✓ PermissionSeeder completed\n";

        $seeder->call('CustomerSeeder');
        echo "✓ CustomerSeeder completed\n";

        $seeder->call('OrderSeeder');
        echo "✓ OrderSeeder completed\n";

        echo "\n✓ All Seeders completed successfully\n\n";
    } catch (\Exception $e) {
        echo "✗ Seeder Error: " . $e->getMessage() . "\n";
        exit(1);
    }

    echo "======================================\n";
    echo "Database Setup Completed Successfully!\n";
    echo "======================================\n";

} catch (\Throwable $e) {
    $migrateFile = APPPATH . 'Database/Migrations';

    echo "\n";
    echo "An error was encountered while running:\n";
    echo "- {$migrateFile}\n";
    echo "\n" . $e->getMessage() . "\n";

    exit(1);
}
