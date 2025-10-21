<?php

/**
 * Direct migration runner
 * This script runs migrations without using the spark CLI
 */

// Set up environment
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
define('CI_DEBUG', true);

// Load paths
require __DIR__ . '/app/Config/Paths.php';
$paths = new Config\Paths();

// Define constants
define('APPPATH', realpath($paths->appDirectory) . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath($paths->systemDirectory) . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath($paths->writableDirectory) . DIRECTORY_SEPARATOR);
define('COMPOSER_PATH', realpath(__DIR__ . '/vendor/autoload.php'));

// Load Composer autoloader
require COMPOSER_PATH;

// Load constants
require APPPATH . 'Config/Constants.php';

// Load Common
require SYSTEMPATH . 'Common.php';

// Load bootstrap to set up autoloader
require SYSTEMPATH . 'Config/AutoloadConfig.php';
require APPPATH . 'Config/Autoload.php';
require SYSTEMPATH . 'Modules/Modules.php';
require APPPATH . 'Config/Modules.php';
require SYSTEMPATH . 'Autoloader/Autoloader.php';
require SYSTEMPATH . 'Config/BaseService.php';
require SYSTEMPATH . 'Config/Services.php';
require APPPATH . 'Config/Services.php';

// Initialize autoloader
$loader = \CodeIgniter\Config\Services::autoloader();
$loader->initialize(new \Config\Autoload(), new \Config\Modules());
$loader->register();

echo "Running migrations...\n";

try {
    // Get migration service
    $migrate = \Config\Services::migrations();

    // Run all migrations
    if ($migrate->latest()) {
        echo "Migrations completed successfully!\n";

        // Show what was migrated
        $migrations = $migrate->findMigrations();
        foreach ($migrations as $version => $migration) {
            echo "  - {$migration['class']}\n";
        }
    } else {
        echo "Migration failed:\n";
        echo $migrate->getCliMessages();
    }
} catch (\Throwable $e) {
    echo "Error running migrations:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
