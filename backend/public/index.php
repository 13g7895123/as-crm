<?php

/**
 * CodeIgniter 4 Entry Point
 *
 * CRM RBAC 權限管理系統後端入口檔案
 */

// 路徑設定
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// 載入 Composer autoloader
require FCPATH . '../vendor/autoload.php';

// 載入 CodeIgniter 核心
require_once FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();

// 啟動框架
require FCPATH . '../vendor/codeigniter4/framework/system/bootstrap.php';

// 執行應用程式
$app = Config\Services::codeigniter();
$app->initialize();
$context = is_cli() ? 'php-cli' : 'web';
$app->setContext($context);

$app->run();
