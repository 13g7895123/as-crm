<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Base API Test Case
 *
 * Base class for all API controller tests with common utilities
 */
abstract class ApiTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';
    protected $namespace   = 'App';

    protected ?string $token = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Helper method to get JSON response
     *
     * @param mixed $result Test result
     * @return object
     */
    protected function getJsonResponse($result): object
    {
        $json = $result->getJSON();
        if (is_string($json)) {
            return json_decode($json);
        }
        return $json;
    }

    /**
     * Helper method to get authentication token
     *
     * @return string
     */
    protected function getAuthToken(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $result = $this->withBodyFormat('json')->post('/api/v1/auth/login', [
            'username' => 'test_admin',
            'password' => 'password',
        ]);

        $json = $this->getJsonResponse($result);
        
        if (isset($json->data->access_token)) {
            $this->token = $json->data->access_token;
            return $this->token;
        }

        return 'mock-jwt-token-for-testing';
    }

    /**
     * Make authenticated GET request
     *
     * @param string $uri
     * @return mixed
     */
    protected function authGet(string $uri)
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getAuthToken(),
        ])->get($uri);
    }

    /**
     * Make authenticated POST request
     *
     * @param string $uri
     * @param array $data
     * @return mixed
     */
    protected function authPost(string $uri, array $data = [])
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getAuthToken(),
        ])->withBodyFormat('json')->post($uri, $data);
    }

    /**
     * Make authenticated PUT request
     *
     * @param string $uri
     * @param array $data
     * @return mixed
     */
    protected function authPut(string $uri, array $data = [])
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getAuthToken(),
        ])->withBodyFormat('json')->put($uri, $data);
    }

    /**
     * Make authenticated DELETE request
     *
     * @param string $uri
     * @return mixed
     */
    protected function authDelete(string $uri)
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getAuthToken(),
        ])->delete($uri);
    }
}
