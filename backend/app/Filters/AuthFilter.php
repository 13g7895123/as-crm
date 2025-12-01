<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * AuthFilter
 *
 * JWT 身份驗證 Filter
 * 在請求進入 Controller 前驗證 JWT token 的有效性
 */
class AuthFilter implements FilterInterface
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
        // 取得 JWT library
        $jwt = Services::jwt();

        // 從 Authorization header 取得 token
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorizedResponse('缺少身份驗證 token');
        }

        // 驗證 Authorization header 格式: Bearer <token>
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('無效的 Authorization header 格式');
        }

        $token = $matches[1];

        // 驗證 JWT token
        try {
            $payload = $jwt->decode($token);

            if (!$payload) {
                return $this->unauthorizedResponse('無效的 token');
            }

            // 驗證 token 是否過期
            if (isset($payload->exp) && $payload->exp < time()) {
                return $this->unauthorizedResponse('Token 已過期');
            }

            // 將使用者資訊存入共享服務,供後續 controller 使用
            // 使用 config 類別作為簡單的資料容器，避免 PHP 8.2+ 動態屬性問題
            $authData = new \stdClass();
            $authData->user_id = $payload->user_id ?? null;
            $authData->username = $payload->username ?? null;
            $authData->email = $payload->email ?? null;
            
            // 將認證資料存入全域變數（CI4 推薦方式之一）
            Services::request()->setGlobal('auth', (array) $authData);

            // 驗證通過,繼續處理請求
            return;

        } catch (\Exception $e) {
            log_message('error', 'JWT 驗證失敗: ' . $e->getMessage());
            return $this->unauthorizedResponse('Token 驗證失敗: ' . $e->getMessage());
        }
    }

    /**
     * 在回應返回後執行
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // 不需要在回應後處理
    }

    /**
     * 回傳未授權錯誤回應
     *
     * @param string $message
     *
     * @return ResponseInterface
     */
    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = Services::response();

        // 設置 JSON 回應和狀態碼
        $response->setJSON([
            'status'  => 'error',
            'message' => $message,
            'code'    => 401,
        ])->setStatusCode(401);

        // 添加 CORS headers，避免前端 CORS 錯誤
        // 因為 AuthFilter 是 before filter，如果返回 response 會跳過 after filters (包括 CorsFilter)
        $corsConfig = config('Cors');
        $request = Services::request();
        $origin = $request->getHeaderLine('Origin');

        if ($origin && $corsConfig->isOriginAllowed($origin)) {
            $corsHeaders = $corsConfig->getHeaders($origin);
            foreach ($corsHeaders as $key => $value) {
                $response->setHeader($key, $value);
            }
        }

        return $response;
    }
}
