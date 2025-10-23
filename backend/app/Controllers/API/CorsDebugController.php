<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CORS Debug Controller
 *
 * 用於除錯和驗證 CORS 設定
 * 提供詳細的 CORS 設定資訊和診斷功能
 */
class CorsDebugController extends ResourceController
{
    protected $format = 'json';

    /**
     * 顯示當前 CORS 設定
     *
     * GET /api/v1/cors/debug
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        $corsConfig = config('Cors');
        $request = $this->request;

        // 取得請求來源
        $origin = $request->getHeaderLine('Origin');
        $referer = $request->getHeaderLine('Referer');

        // 檢查來源是否被允許
        $isAllowed = $corsConfig->isOriginAllowed($origin);

        // 收集所有請求標頭
        $requestHeaders = [];
        foreach ($request->headers() as $name => $value) {
            $requestHeaders[$name] = $value->getValue();
        }

        // 取得會套用的 CORS 標頭
        $appliedHeaders = $corsConfig->getHeaders($origin);

        return $this->respond([
            'status' => 'success',
            'message' => 'CORS 設定診斷資訊',
            'data' => [
                'request_info' => [
                    'origin' => $origin ?: '(未提供)',
                    'referer' => $referer ?: '(未提供)',
                    'method' => $request->getMethod(),
                    'uri' => (string) $request->getUri(),
                    'is_origin_allowed' => $isAllowed,
                ],
                'cors_config' => [
                    'allowed_origins' => $corsConfig->allowedOrigins,
                    'allowed_methods' => $corsConfig->allowedMethods,
                    'allowed_headers' => $corsConfig->allowedHeaders,
                    'exposed_headers' => $corsConfig->exposedHeaders,
                    'supports_credentials' => $corsConfig->supportsCredentials,
                    'max_age' => $corsConfig->maxAge,
                ],
                'applied_headers' => $appliedHeaders,
                'all_request_headers' => $requestHeaders,
            ],
            'recommendations' => $this->getRecommendations($origin, $isAllowed),
        ]);
    }

    /**
     * 測試特定來源的 CORS
     *
     * POST /api/v1/cors/test
     * Body: { "origin": "http://localhost:3003" }
     *
     * @return ResponseInterface
     */
    public function test(): ResponseInterface
    {
        $corsConfig = config('Cors');
        $testOrigin = $this->request->getJSON()->origin ?? null;

        if (!$testOrigin) {
            return $this->fail('請提供要測試的 origin', 400);
        }

        // 測試該來源
        $isAllowed = $corsConfig->isOriginAllowed($testOrigin);
        $headers = $corsConfig->getHeaders($testOrigin);

        return $this->respond([
            'status' => 'success',
            'message' => 'CORS 測試結果',
            'data' => [
                'test_origin' => $testOrigin,
                'is_allowed' => $isAllowed,
                'would_apply_headers' => $headers,
                'result' => $isAllowed ? 'PASS - 此來源會被允許' : 'FAIL - 此來源會被封鎖',
            ],
        ]);
    }

    /**
     * 健康檢查端點
     *
     * GET /api/v1/cors/health
     *
     * @return ResponseInterface
     */
    public function health(): ResponseInterface
    {
        $corsConfig = config('Cors');
        $filtersConfig = config('Filters');

        // 檢查 CORS filter 是否已註冊
        $corsFilterRegistered = isset($filtersConfig->aliases['corsFilter']);

        // 檢查 CORS filter 是否在 globals.after 中
        $corsFilterInGlobals = in_array('corsFilter', $filtersConfig->globals['after'] ?? []);

        // 檢查是否有允許的來源
        $hasAllowedOrigins = !empty($corsConfig->allowedOrigins);

        // 檢查是否允許所有來源
        $allowsAllOrigins = in_array('*', $corsConfig->allowedOrigins);

        $allChecks = [
            'cors_filter_registered' => $corsFilterRegistered,
            'cors_filter_in_globals_after' => $corsFilterInGlobals,
            'has_allowed_origins' => $hasAllowedOrigins,
            'allows_all_origins' => $allowsAllOrigins,
        ];

        $allPassed = $corsFilterRegistered && $corsFilterInGlobals && $hasAllowedOrigins;

        return $this->respond([
            'status' => $allPassed ? 'healthy' : 'unhealthy',
            'message' => $allPassed ? 'CORS 設定正常' : 'CORS 設定有問題',
            'checks' => $allChecks,
            'issues' => $this->getIssues($allChecks),
        ]);
    }

    /**
     * 取得建議
     *
     * @param string|null $origin
     * @param bool $isAllowed
     * @return array
     */
    private function getRecommendations(?string $origin, bool $isAllowed): array
    {
        $recommendations = [];

        if (!$origin) {
            $recommendations[] = '⚠️ 請求中沒有 Origin 標頭。確保前端發送請求時包含 Origin。';
        } elseif (!$isAllowed) {
            $recommendations[] = "❌ Origin '$origin' 未在允許清單中。";
            $recommendations[] = "💡 請在 backend/app/Config/Cors.php 的 allowedOrigins 陣列中加入 '$origin'。";

            // 檢查是否是 localhost vs 127.0.0.1 的問題
            if (strpos($origin, 'localhost') !== false) {
                $alt = str_replace('localhost', '127.0.0.1', $origin);
                $recommendations[] = "💡 同時建議加入 '$alt'（localhost 的替代位址）。";
            } elseif (strpos($origin, '127.0.0.1') !== false) {
                $alt = str_replace('127.0.0.1', 'localhost', $origin);
                $recommendations[] = "💡 同時建議加入 '$alt'（127.0.0.1 的替代位址）。";
            }

            $recommendations[] = "🔄 修改後請重啟後端容器：docker compose restart backend";
        } else {
            $recommendations[] = "✅ Origin '$origin' 已被允許，CORS 應該可以正常運作。";
            $recommendations[] = "💡 如果仍然有問題，請清除瀏覽器快取並硬重新整理（Ctrl+Shift+R）。";
        }

        return $recommendations;
    }

    /**
     * 取得問題清單
     *
     * @param array $checks
     * @return array
     */
    private function getIssues(array $checks): array
    {
        $issues = [];

        if (!$checks['cors_filter_registered']) {
            $issues[] = "CORS filter 未註冊在 Filters 設定中";
        }

        if (!$checks['cors_filter_in_globals_after']) {
            $issues[] = "CORS filter 未加入到 globals.after 中";
        }

        if (!$checks['has_allowed_origins']) {
            $issues[] = "沒有設定任何允許的來源";
        }

        return $issues;
    }
}
