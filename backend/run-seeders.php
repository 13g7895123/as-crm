#!/usr/bin/env php
<?php

/**
 * Manual Seeder Runner
 * This script bypasses the CLI routing system and runs seeders directly
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

// Now manually run seeders
echo "==========================================\n";
echo "Running Database Seeders\n";
echo "==========================================\n\n";

// List of seeders to run in order
// 注意: UserSeeder 必須在 RoleSeeder 之前執行，因為角色需要 created_by 欄位
$seeders = [
    'UserSeeder',        // 建立系統管理員（ID=1）
    'RoleSeeder',        // 建立角色（需要 created_by=1）
    'PermissionSeeder',  // 建立權限
];

$hasErrors = false;

foreach ($seeders as $seederName) {
    echo "Running {$seederName}...\n";

    try {
        // Get the seeder instance
        $seeder = \Config\Database::seeder();

        // Run the seeder
        $seeder->call($seederName);

        echo "✓ {$seederName} completed successfully!\n\n";

    } catch (\Throwable $e) {
        echo "✗ Error running {$seederName}:\n";
        echo "  Message: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";

        if (ENVIRONMENT === 'development') {
            echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
        }

        echo "\n";
        $hasErrors = true;
    }
}

echo "==========================================\n";

if ($hasErrors) {
    echo "Seeder process completed with errors\n";
    echo "==========================================\n";
    exit(1);
} else {
    echo "All seeders completed successfully!\n";
    echo "==========================================\n";
    exit(0);
}
