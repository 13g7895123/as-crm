<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * CorsFilter
 *
 * CORS (Cross-Origin Resource Sharing) Filter
 * 處理跨域請求,允許前端網域存取 API
 */
class CorsFilter implements FilterInterface
{
    /**
     * 在請求處理前執行
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // 取得 CORS 配置
        $corsConfig = config('Cors');

        // 取得請求的來源
        $origin = $request->getHeaderLine('Origin');

        // 處理 OPTIONS preflight 請求
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = Services::response();

            // 設定 CORS headers
            foreach ($corsConfig->getHeaders($origin) as $header => $value) {
                $response->setHeader($header, $value);
            }

            // 回傳 204 No Content
            return $response->setStatusCode(204);
        }

        // 非 OPTIONS 請求,繼續處理
        return $request;
    }

    /**
     * 在回應返回後執行
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // 取得 CORS 配置
        $corsConfig = config('Cors');

        // 取得請求的來源
        $origin = $request->getHeaderLine('Origin');

        // 設定 CORS headers 到回應
        foreach ($corsConfig->getHeaders($origin) as $header => $value) {
            $response->setHeader($header, $value);
        }

        // Must return the response
        return $response;
    }
}
