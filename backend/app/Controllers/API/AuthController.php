<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use Config\Services;

/**
 * AuthController
 *
 * 身份驗證 Controller
 * 提供 login/logout/refresh token/me endpoints
 */
class AuthController extends ResourceController
{
    /**
     * Response format
     *
     * @var string
     */
    protected $format = 'json';

    /**
     * 登入
     *
     * POST /api/v1/auth/login
     *
     * @return mixed
     */
    public function login()
    {
        // 取得 JSON 請求資料
        $data = $this->request->getJSON(true);

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        // 驗證必填欄位
        if (empty($username) || empty($password)) {
            return $this->failValidationErrors('使用者名稱和密碼為必填欄位');
        }

        // 從資料庫驗證使用者
        $userModel = model('App\Models\UserModel');
        $user = $userModel->where('username', $username)->first();

        if (!$user) {
            return $this->failUnauthorized('使用者名稱或密碼錯誤');
        }

        // 驗證密碼
        if (!password_verify($password, $user['password_hash'])) {
            return $this->failUnauthorized('使用者名稱或密碼錯誤');
        }

        // 檢查使用者是否啟用
        if ($user['is_active'] != 1) {
            return $this->failForbidden('此帳號已被停用,請聯絡管理員');
        }

        // 生成 JWT tokens
        $jwt = Services::jwt();

        $accessToken = $jwt->generateAccessToken(
            $user['id'],
            $user['username'],
            $user['email'],
            [
                'full_name' => $user['full_name'] ?? '',
                'department' => $user['department'] ?? '',
                'region' => $user['region'] ?? '',
            ]
        );

        $refreshToken = $jwt->generateRefreshToken(
            $user['id'],
            $user['username']
        );

        // 更新最後登入時間
        $userModel->update($user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        // 回傳認證資料
        return $this->respond([
            'status' => 'success',
            'message' => '登入成功',
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'] ?? '',
                    'department' => $user['department'] ?? '',
                    'region' => $user['region'] ?? '',
                    'is_active' => $user['is_active'],
                ],
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => $jwt->getTokenExpiry(false),
            ],
        ]);
    }

    /**
     * 登出
     *
     * POST /api/v1/auth/logout
     *
     * @return mixed
     */
    public function logout()
    {
        // 取得 user_id (由 AuthFilter 設定)
        $userId = $this->request->user_id ?? null;

        // 記錄登出事件 (可選)
        if ($userId) {
            log_message('info', "使用者 {$userId} 登出系統");
        }

        // 回傳成功訊息
        // 注意: JWT 是 stateless 的,無法真正「撤銷」token
        // 實際上是由前端刪除 token
        return $this->respond([
            'status' => 'success',
            'message' => '登出成功',
        ]);
    }

    /**
     * 刷新 Access Token
     *
     * POST /api/v1/auth/refresh
     *
     * @return mixed
     */
    public function refresh()
    {
        // 取得 JSON 請求資料
        $data = $this->request->getJSON(true);

        $refreshToken = $data['refresh_token'] ?? null;

        if (empty($refreshToken)) {
            return $this->failValidationErrors('缺少 refresh token');
        }

        // 驗證 refresh token
        $jwt = Services::jwt();

        try {
            $payload = $jwt->decode($refreshToken);

            // 檢查是否為 refresh token
            if (!$jwt->isRefreshToken($refreshToken)) {
                return $this->failUnauthorized('無效的 refresh token');
            }

            // 從資料庫取得使用者資料
            $userModel = model('App\Models\UserModel');
            $user = $userModel->find($payload->user_id);

            if (!$user) {
                return $this->failUnauthorized('使用者不存在');
            }

            // 檢查使用者是否仍然啟用
            if ($user['is_active'] != 1) {
                return $this->failForbidden('此帳號已被停用');
            }

            // 生成新的 access token
            $newAccessToken = $jwt->generateAccessToken(
                $user['id'],
                $user['username'],
                $user['email'],
                [
                    'full_name' => $user['full_name'] ?? '',
                    'department' => $user['department'] ?? '',
                    'region' => $user['region'] ?? '',
                ]
            );

            // 回傳新的 access token
            return $this->respond([
                'status' => 'success',
                'message' => 'Token 刷新成功',
                'data' => [
                    'access_token' => $newAccessToken,
                    'token_type' => 'Bearer',
                    'expires_in' => $jwt->getTokenExpiry(false),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->failUnauthorized('無效的 refresh token: ' . $e->getMessage());
        }
    }

    /**
     * 取得當前使用者資料
     *
     * GET /api/v1/auth/me
     *
     * @return mixed
     */
    public function me()
    {
        // 取得 user_id (由 AuthFilter 設定)
        $userId = $this->request->user_id ?? null;

        if (!$userId) {
            return $this->failUnauthorized('未授權的請求');
        }

        // 從資料庫取得使用者資料
        $userModel = model('App\Models\UserModel');
        $user = $userModel->find($userId);

        if (!$user) {
            return $this->failNotFound('使用者不存在');
        }

        // 回傳使用者資料
        return $this->respond([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'] ?? '',
                    'department' => $user['department'] ?? '',
                    'region' => $user['region'] ?? '',
                    'is_active' => $user['is_active'],
                    'last_login_at' => $user['last_login_at'] ?? null,
                ],
            ],
        ]);
    }
}
