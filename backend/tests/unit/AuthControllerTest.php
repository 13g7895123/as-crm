<?php

namespace Tests\unit;

use Tests\Support\ApiTestCase;

/**
 * AuthController Unit Test
 *
 * Tests the authentication API endpoints
 *
 * @group unit
 */
class AuthControllerTest extends ApiTestCase
{
    /**
     * Test login with valid credentials
     */
    public function testLoginWithValidCredentials(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'username' => 'test_admin',
            'password' => 'password',
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $json = $this->getJsonResponse($result);
        $this->assertObjectHasProperty('data', $json);
        $this->assertObjectHasProperty('access_token', $json->data);
        $this->assertObjectHasProperty('refresh_token', $json->data);
        $this->assertEquals('Bearer', $json->data->token_type);
    }

    /**
     * Test login with invalid username
     */
    public function testLoginWithInvalidUsername(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'username' => 'nonexistent_user',
            'password' => 'password',
        ]);

        $result->assertStatus(401);
    }

    /**
     * Test login with invalid password
     */
    public function testLoginWithInvalidPassword(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'username' => 'test_admin',
            'password' => 'wrong_password',
        ]);

        $result->assertStatus(401);
    }

    /**
     * Test login with missing credentials
     */
    public function testLoginWithMissingCredentials(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', []);

        $result->assertStatus(400);
    }

    /**
     * Test login with missing username
     */
    public function testLoginWithMissingUsername(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'password' => 'password',
        ]);

        $result->assertStatus(400);
    }

    /**
     * Test login with missing password
     */
    public function testLoginWithMissingPassword(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'username' => 'test_admin',
        ]);

        $result->assertStatus(400);
    }

    /**
     * Test logout requires authentication
     */
    public function testLogoutRequiresAuth(): void
    {
        $result = $this->post('/api/v1/auth/logout');

        $result->assertStatus(401);
    }

    /**
     * Test me endpoint requires authentication
     */
    public function testMeRequiresAuth(): void
    {
        $result = $this->get('/api/v1/auth/me');

        $result->assertStatus(401);
    }

    /**
     * Test refresh token with valid token
     */
    public function testRefreshTokenWithValidToken(): void
    {
        // First login to get tokens
        $loginResult = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'username' => 'test_admin',
            'password' => 'password',
        ]);

        $tokens = $this->getJsonResponse($loginResult)->data;

        // Use refresh token to get new access token
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => $tokens->refresh_token,
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $json = $this->getJsonResponse($result);
        $this->assertObjectHasProperty('data', $json);
        $this->assertObjectHasProperty('access_token', $json->data);
    }

    /**
     * Test refresh token with invalid token
     */
    public function testRefreshTokenWithInvalidToken(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/refresh', [
            'refresh_token' => 'invalid_token',
        ]);

        $result->assertStatus(401);
    }

    /**
     * Test refresh token with missing token
     */
    public function testRefreshTokenWithMissingToken(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/refresh', []);

        $result->assertStatus(400);
    }

    /**
     * Test me endpoint with valid token
     */
    public function testMeWithValidToken(): void
    {
        // First login to get tokens
        $loginResult = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'username' => 'test_admin',
            'password' => 'password',
        ]);

        $tokens = $this->getJsonResponse($loginResult)->data;

        // Get user info
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokens->access_token,
        ])->get('/api/v1/auth/me');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $json = $this->getJsonResponse($result);
        $this->assertObjectHasProperty('data', $json);
        $this->assertObjectHasProperty('user', $json->data);
        $this->assertEquals('test_admin', $json->data->user->username);
    }

    /**
     * Test logout with valid token
     */
    public function testLogoutWithValidToken(): void
    {
        // First login to get tokens
        $loginResult = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'username' => 'test_admin',
            'password' => 'password',
        ]);

        $tokens = $this->getJsonResponse($loginResult)->data;

        // Logout
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokens->access_token,
        ])->post('/api/v1/auth/logout');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }

    /**
     * Test login updates last login time
     */
    public function testLoginUpdatesLastLoginTime(): void
    {
        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'username' => 'test_admin',
            'password' => 'password',
        ]);

        $result->assertStatus(200);

        // Check user's last_login_at in database
        $db = \Config\Database::connect();
        $user = $db->table('users')->where('username', 'test_admin')->get()->getRowArray();
        
        $this->assertNotNull($user['last_login_at']);
    }

    /**
     * Test inactive user cannot login
     */
    public function testInactiveUserCannotLogin(): void
    {
        // Create an inactive user
        $db = \Config\Database::connect();
        $db->table('users')->insert([
            'username' => 'inactive_user',
            'email' => 'inactive@example.com',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Inactive User',
            'is_active' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'username' => 'inactive_user',
            'password' => 'password',
        ]);

        $result->assertStatus(403);
    }
}
