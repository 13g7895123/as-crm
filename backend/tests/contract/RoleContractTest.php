<?php

namespace Tests\Contract;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Role API Contract Test
 *
 * Tests that the Roles API endpoints conform to the OpenAPI specification
 * defined in contracts/roles-api.yaml
 *
 * @group contract
 */
class RoleContractTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';
    protected $namespace   = 'App';

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        // Get JWT token for authentication
        $this->token = $this->getAuthToken();
    }

    /**
     * Test GET /api/v1/roles endpoint response structure
     */
    public function testGetRolesReturnsCorrectStructure(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles');

        $response->assertStatus(200);
        $response->assertJSONFragment([
            'data' => [],
            'meta' => []
        ]);

        $json = $response->getJSON();
        $this->assertIsArray($json->data);
        $this->assertObjectHasProperty('current_page', $json->meta);
        $this->assertObjectHasProperty('per_page', $json->meta);
        $this->assertObjectHasProperty('total', $json->meta);
        $this->assertObjectHasProperty('total_pages', $json->meta);
    }

    /**
     * Test GET /api/v1/roles with query parameters
     */
    public function testGetRolesAcceptsQueryParameters(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles?page=1&per_page=10&sort=name&order=asc');

        $response->assertStatus(200);
    }

    /**
     * Test POST /api/v1/roles creates new role
     */
    public function testPostRolesCreatesNewRole(): void
    {
        $roleData = [
            'name' => 'regional_sales_manager',
            'display_name' => '區域業務主管',
            'description' => '負責特定區域的業務管理',
            'is_active' => true,
            'permissions' => [1, 2, 5, 8],
            'condition_rules' => [
                [
                    'condition_type' => 'region',
                    'operator' => 'in',
                    'condition_value' => ['華東', '華南']
                ]
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $roleData);

        $response->assertStatus(201);
        $response->assertJSONFragment(['message']);

        $json = $response->getJSON();
        $this->assertObjectHasProperty('data', $json);
        $this->assertObjectHasProperty('id', $json->data);
        $this->assertEquals('regional_sales_manager', $json->data->name);
        $this->assertEquals('區域業務主管', $json->data->display_name);
    }

    /**
     * Test POST /api/v1/roles validates required fields
     */
    public function testPostRolesValidatesRequiredFields(): void
    {
        $invalidData = [
            'description' => 'Missing required fields'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $invalidData);

        $response->assertStatus(422);
        $response->assertJSONFragment(['error' => '驗證失敗']);
    }

    /**
     * Test POST /api/v1/roles validates name pattern
     */
    public function testPostRolesValidatesNamePattern(): void
    {
        $invalidData = [
            'name' => 'Invalid Name With Spaces',
            'display_name' => '無效角色名'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $invalidData);

        $response->assertStatus(422);
    }

    /**
     * Test GET /api/v1/roles/{id} returns role detail
     */
    public function testGetRoleByIdReturnsDetail(): void
    {
        // Assume role with ID 1 exists from seeder
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles/1');

        $response->assertStatus(200);

        $json = $response->getJSON();
        $this->assertObjectHasProperty('data', $json);
        $this->assertObjectHasProperty('id', $json->data);
        $this->assertObjectHasProperty('name', $json->data);
        $this->assertObjectHasProperty('display_name', $json->data);
        $this->assertObjectHasProperty('permissions', $json->data);
        $this->assertObjectHasProperty('condition_rules', $json->data);
    }

    /**
     * Test GET /api/v1/roles/{id} returns 404 for non-existent role
     */
    public function testGetRoleByIdReturns404WhenNotFound(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles/99999');

        $response->assertStatus(404);
        $response->assertJSONFragment(['code' => 'ROLE_NOT_FOUND']);
    }

    /**
     * Test PUT /api/v1/roles/{id} updates role
     */
    public function testPutRoleUpdatesExistingRole(): void
    {
        $updateData = [
            'display_name' => '更新後的角色名',
            'is_active' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->put('/api/v1/roles/1', $updateData);

        $response->assertStatus(200);
        $response->assertJSONFragment(['message' => '角色更新成功']);

        $json = $response->getJSON();
        $this->assertEquals('更新後的角色名', $json->data->display_name);
    }

    /**
     * Test DELETE /api/v1/roles/{id} deletes custom role
     */
    public function testDeleteRoleRemovesCustomRole(): void
    {
        // Create a custom role first
        $roleData = [
            'name' => 'temp_role',
            'display_name' => '臨時角色'
        ];

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $roleData);

        $roleId = $createResponse->getJSON()->data->id;

        // Delete the role
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->delete('/api/v1/roles/' . $roleId);

        $response->assertStatus(200);
        $response->assertJSONFragment(['message' => '角色刪除成功']);
    }

    /**
     * Test DELETE /api/v1/roles/{id} prevents deleting system roles
     */
    public function testDeleteRolePreventsSystemRoleDeletion(): void
    {
        // Assume role ID 1 is a system role
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->delete('/api/v1/roles/1');

        $response->assertStatus(400);
    }

    /**
     * Test GET /api/v1/roles/{id}/permissions returns role permissions
     */
    public function testGetRolePermissionsReturnsCorrectStructure(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles/1/permissions?include_inherited=true');

        $response->assertStatus(200);

        $json = $response->getJSON();
        $this->assertObjectHasProperty('data', $json);
        $this->assertObjectHasProperty('direct_permissions', $json->data);
        $this->assertObjectHasProperty('inherited_permissions', $json->data);
    }

    /**
     * Test authentication is required for all endpoints
     */
    public function testEndpointsRequireAuthentication(): void
    {
        $response = $this->get('/api/v1/roles');
        $response->assertStatus(401);

        $response = $this->post('/api/v1/roles', []);
        $response->assertStatus(401);

        $response = $this->get('/api/v1/roles/1');
        $response->assertStatus(401);

        $response = $this->put('/api/v1/roles/1', []);
        $response->assertStatus(401);

        $response = $this->delete('/api/v1/roles/1');
        $response->assertStatus(401);
    }

    /**
     * Helper method to get authentication token
     */
    protected function getAuthToken(): string
    {
        // This would normally call the /api/v1/auth/login endpoint
        // For now, return a mock token or generate one
        // TODO: Implement proper JWT token generation for testing
        return 'mock-jwt-token-for-testing';
    }
}
