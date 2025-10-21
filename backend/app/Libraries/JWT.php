<?php

namespace App\Libraries;

use Exception;

/**
 * JWT Library
 *
 * JWT token 生成和驗證 library
 * 使用 HS256 演算法(HMAC with SHA-256)
 *
 * 注意: 這是簡化版實作,生產環境建議使用 Firebase PHP-JWT
 */
class JWT
{
    /**
     * JWT Secret Key
     * 從環境變數取得,或使用預設值
     *
     * @var string
     */
    private string $secretKey;

    /**
     * JWT Algorithm
     *
     * @var string
     */
    private string $algorithm = 'HS256';

    /**
     * Access Token 有效期限(秒)
     *
     * @var int
     */
    private int $accessTokenExpiry = 3600; // 1 小時

    /**
     * Refresh Token 有效期限(秒)
     *
     * @var int
     */
    private int $refreshTokenExpiry = 604800; // 7 天

    /**
     * Constructor
     */
    public function __construct()
    {
        // 從環境變數取得 secret key
        $this->secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key-change-this-in-production';

        // 從環境變數取得 token 有效期限
        $this->accessTokenExpiry = (int) (getenv('JWT_ACCESS_TOKEN_EXPIRY') ?: 3600);
        $this->refreshTokenExpiry = (int) (getenv('JWT_REFRESH_TOKEN_EXPIRY') ?: 604800);
    }

    /**
     * 生成 JWT Token
     *
     * @param array $payload Token 資料
     * @param bool  $isRefreshToken 是否為 refresh token
     *
     * @return string
     */
    public function encode(array $payload, bool $isRefreshToken = false): string
    {
        // 設定 JWT Header
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm,
        ];

        // 設定 JWT Payload
        $now = time();
        $expiry = $isRefreshToken ? $this->refreshTokenExpiry : $this->accessTokenExpiry;

        $payload['iat'] = $now; // Issued At
        $payload['exp'] = $now + $expiry; // Expiration Time
        $payload['iss'] = 'crm-rbac-system'; // Issuer

        // Base64Url 編碼 Header 和 Payload
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        // 生成簽章
        $signature = $this->generateSignature($headerEncoded, $payloadEncoded);

        // 組合 JWT Token
        return "{$headerEncoded}.{$payloadEncoded}.{$signature}";
    }

    /**
     * 驗證並解碼 JWT Token
     *
     * @param string $token
     *
     * @return object|null
     * @throws Exception
     */
    public function decode(string $token): ?object
    {
        // 分割 token
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('無效的 token 格式');
        }

        [$headerEncoded, $payloadEncoded, $signatureProvided] = $parts;

        // 驗證簽章
        $signatureExpected = $this->generateSignature($headerEncoded, $payloadEncoded);

        if (!hash_equals($signatureExpected, $signatureProvided)) {
            throw new Exception('無效的 token 簽章');
        }

        // 解碼 Payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded));

        if (!$payload) {
            throw new Exception('無效的 token payload');
        }

        // 驗證 token 是否過期
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new Exception('Token 已過期');
        }

        return $payload;
    }

    /**
     * 生成 Access Token
     *
     * @param int    $userId
     * @param string $username
     * @param string $email
     * @param array  $additionalClaims 額外的聲明
     *
     * @return string
     */
    public function generateAccessToken(
        int $userId,
        string $username,
        string $email,
        array $additionalClaims = []
    ): string {
        $payload = [
            'user_id' => $userId,
            'username' => $username,
            'email' => $email,
            'type' => 'access',
        ];

        // 合併額外的聲明
        $payload = array_merge($payload, $additionalClaims);

        return $this->encode($payload, false);
    }

    /**
     * 生成 Refresh Token
     *
     * @param int    $userId
     * @param string $username
     *
     * @return string
     */
    public function generateRefreshToken(int $userId, string $username): string
    {
        $payload = [
            'user_id' => $userId,
            'username' => $username,
            'type' => 'refresh',
        ];

        return $this->encode($payload, true);
    }

    /**
     * 驗證 token 是否為 refresh token
     *
     * @param string $token
     *
     * @return bool
     */
    public function isRefreshToken(string $token): bool
    {
        try {
            $payload = $this->decode($token);
            return isset($payload->type) && $payload->type === 'refresh';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 生成簽章
     *
     * @param string $headerEncoded
     * @param string $payloadEncoded
     *
     * @return string
     */
    private function generateSignature(string $headerEncoded, string $payloadEncoded): string
    {
        $data = "{$headerEncoded}.{$payloadEncoded}";
        $signature = hash_hmac('sha256', $data, $this->secretKey, true);

        return $this->base64UrlEncode($signature);
    }

    /**
     * Base64Url 編碼
     *
     * @param string $data
     *
     * @return string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64Url 解碼
     *
     * @param string $data
     *
     * @return string
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * 取得 Token 過期時間
     *
     * @param bool $isRefreshToken
     *
     * @return int
     */
    public function getTokenExpiry(bool $isRefreshToken = false): int
    {
        return $isRefreshToken ? $this->refreshTokenExpiry : $this->accessTokenExpiry;
    }
}
