<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cors Configuration
 *
 * CORS 設定
 * 允許前端網域跨域存取 API
 */
class Cors extends BaseConfig
{
    /**
     * 允許的來源網域
     *
     * @var array
     */
    public array $allowedOrigins = [
        'http://localhost:3003',      // Nuxt 開發環境
        'http://localhost:3000',      // Nuxt 開發環境
        'http://127.0.0.1:3003',      // Nuxt 開發環境 (alternative)
        'http://127.0.0.1:3000',      // Nuxt 開發環境 (alternative)
        'http://localhost:8080',      // 前端生產環境
        'http://localhost:9330',      // Nuxt 開發環境 (custom port)
        'http://127.0.0.1:9330',      // Nuxt 開發環境 (custom port, alternative)
        'http://localhost:9230',      // 後端開發環境 (backend dev port)
        'http://127.0.0.1:9230',      // 後端開發環境 (backend dev port, alternative)
        // 生產環境需要加入實際的網域名稱
        // 'https://crm.example.com',
    ];

    /**
     * 允許的 HTTP 方法
     *
     * @var array
     */
    public array $allowedMethods = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'OPTIONS',
    ];

    /**
     * 允許的 HTTP Headers
     *
     * @var array
     */
    public array $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-CSRF-Token',
    ];

    /**
     * 允許暴露的 Headers
     *
     * @var array
     */
    public array $exposedHeaders = [
        'Content-Length',
        'X-Total-Count',
        'X-Page-Count',
    ];

    /**
     * 是否允許 Credentials (Cookies)
     *
     * @var bool
     */
    public bool $supportsCredentials = true;

    /**
     * Preflight 請求快取時間 (秒)
     *
     * @var int
     */
    public int $maxAge = 86400; // 24 小時

    /**
     * 檢查來源是否被允許
     *
     * @param string $origin
     *
     * @return bool
     */
    public function isOriginAllowed(string $origin): bool
    {
        // 如果 allowedOrigins 包含 '*',則允許所有來源
        if (in_array('*', $this->allowedOrigins)) {
            return true;
        }

        // 檢查來源是否在允許清單中
        return in_array($origin, $this->allowedOrigins);
    }

    /**
     * 取得 CORS Headers
     *
     * @param string|null $origin
     *
     * @return array
     */
    public function getHeaders(?string $origin = null): array
    {
        $headers = [];

        // 設定允許的來源
        if ($origin && $this->isOriginAllowed($origin)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        } elseif (in_array('*', $this->allowedOrigins)) {
            $headers['Access-Control-Allow-Origin'] = '*';
        }

        // 設定允許的方法
        $headers['Access-Control-Allow-Methods'] = implode(', ', $this->allowedMethods);

        // 設定允許的 Headers
        $headers['Access-Control-Allow-Headers'] = implode(', ', $this->allowedHeaders);

        // 設定暴露的 Headers
        if (!empty($this->exposedHeaders)) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $this->exposedHeaders);
        }

        // 設定是否允許 Credentials
        if ($this->supportsCredentials) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        // 設定 Preflight 快取時間
        $headers['Access-Control-Max-Age'] = (string) $this->maxAge;

        return $headers;
    }
}
