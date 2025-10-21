<?php

namespace Config;

/**
 * 路徑配置
 *
 * 定義 CodeIgniter 應用程式的關鍵路徑
 */
class Paths
{
    /**
     * 系統目錄路徑
     */
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * 應用程式目錄路徑
     */
    public string $appDirectory = __DIR__ . '/..';

    /**
     * 可寫入目錄路徑
     */
    public string $writableDirectory = __DIR__ . '/../../writable';

    /**
     * 測試目錄路徑
     */
    public string $testsDirectory = __DIR__ . '/../../tests';

    /**
     * 視圖目錄路徑
     */
    public string $viewDirectory = __DIR__ . '/../Views';
}
