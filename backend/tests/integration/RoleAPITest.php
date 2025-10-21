<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Role API Integration Test
 *
 * Tests the complete workflow of creating, updating, and deleting roles
 * including permission assignments and condition rules
 *
 * @group integration
 */
class RoleAPITest extends CIUnitTestCase
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
        $this->token = $this->getAuthToken();
    }

    /**
     * Test complete role creation workflow
     *
     * Creates a new "Regional Sales Manager" role with:
     * - Specific permissions (view and edit customers)
     * - Condition restrictions (limited to East region)
     */
    public function testCompleteRoleCreationWorkflow(): void
    {
        // Step 1: Create the role
        $roleData = [
            'name' => 'regional_sales_manager',
            'display_name' => '區域業務主管',
            'description' => '負責華東區域的業務管理',
            'is_active' => true,
            'permissions' => [1, 2], // customer.view, customer.edit
            'condition_rules' => [
                [
                    'permission_id' => null, // Applies to all permissions
                    'condition_type' => 'region',
                    'operator' => 'equals',
                    'condition_value' => ['value' => '華東']
                ]
            ]
        ];

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $roleData);

        $createResponse->assertStatus(201);
        $roleId = $createResponse->getJSON()->data->id;
        $this->assertIsInt($roleId);

        // Step 2: Verify the role was created correctly
        $getResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles/' . $roleId);

        $getResponse->assertStatus(200);
        $role = $getResponse->getJSON()->data;

        $this->assertEquals('regional_sales_manager', $role->name);
        $this->assertEquals('區域業務主管', $role->display_name);
        $this->assertEquals('負責華東區域的業務管理', $role->description);
        $this->assertTrue($role->is_active);
        $this->assertFalse($role->is_system);

        // Step 3: Verify permissions were assigned
        $this->assertIsArray($role->permissions);
        $this->assertCount(2, $role->permissions);

        $permissionIds = array_map(fn($p) => $p->id, $role->permissions);
        $this->assertContains(1, $permissionIds);
        $this->assertContains(2, $permissionIds);

        // Step 4: Verify condition rules were created
        $this->assertIsArray($role->condition_rules);
        $this->assertCount(1, $role->condition_rules);
        $this->assertEquals('region', $role->condition_rules[0]->condition_type);
        $this->assertEquals('equals', $role->condition_rules[0]->operator);

        // Step 5: Verify role appears in list
        $listResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles');

        $listResponse->assertStatus(200);
        $roles = $listResponse->getJSON()->data;

        $foundRole = false;
        foreach ($roles as $r) {
            if ($r->id === $roleId) {
                $foundRole = true;
                break;
            }
        }
        $this->assertTrue($foundRole, 'Created role should appear in list');
    }

    /**
     * Test complete role update workflow
     */
    public function testCompleteRoleUpdateWorkflow(): void
    {
        // Create a role first
        $roleData = [
            'name' => 'test_role_for_update',
            'display_name' => '測試角色',
            'permissions' => [1, 2]
        ];

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $roleData);

        $roleId = $createResponse->getJSON()->data->id;

        // Update the role
        $updateData = [
            'display_name' => '更新後的測試角色',
            'description' => '這是更新後的描述',
            'is_active' => false,
            'permissions' => [1, 2, 3], // Add one more permission
            'condition_rules' => [
                [
                    'condition_type' => 'department',
                    'operator' => 'equals',
                    'condition_value' => ['value' => '業務部']
                ]
            ]
        ];

        $updateResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->put('/api/v1/roles/' . $roleId, $updateData);

        $updateResponse->assertStatus(200);

        // Verify updates
        $getResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles/' . $roleId);

        $role = $getResponse->getJSON()->data;

        $this->assertEquals('更新後的測試角色', $role->display_name);
        $this->assertEquals('這是更新後的描述', $role->description);
        $this->assertFalse($role->is_active);
        $this->assertCount(3, $role->permissions);
        $this->assertCount(1, $role->condition_rules);
    }

    /**
     * Test complete role deletion workflow
     */
    public function testCompleteRoleDeletionWorkflow(): void
    {
        // Create a custom role
        $roleData = [
            'name' => 'role_to_delete',
            'display_name' => '待刪除角色',
            'permissions' => [1]
        ];

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $roleData);

        $roleId = $createResponse->getJSON()->data->id;

        // Delete the role
        $deleteResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->delete('/api/v1/roles/' . $roleId);

        $deleteResponse->assertStatus(200);

        // Verify role is deleted
        $getResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles/' . $roleId);

        $getResponse->assertStatus(404);

        // Verify associated permissions are also removed
        $permissionsResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles/' . $roleId . '/permissions');

        $permissionsResponse->assertStatus(404);
    }

    /**
     * Test system role protection
     */
    public function testSystemRoleCannotBeDeleted(): void
    {
        // Get a system role (created by seeder)
        $listResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles');

        $roles = $listResponse->getJSON()->data;
        $systemRole = null;

        foreach ($roles as $role) {
            if ($role->is_system) {
                $systemRole = $role;
                break;
            }
        }

        $this->assertNotNull($systemRole, 'Should have at least one system role from seeder');

        // Try to delete system role
        $deleteResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->delete('/api/v1/roles/' . $systemRole->id);

        $deleteResponse->assertStatus(400);
    }

    /**
     * Test role name uniqueness
     */
    public function testRoleNameMustBeUnique(): void
    {
        $roleData = [
            'name' => 'unique_test_role',
            'display_name' => '唯一性測試角色'
        ];

        // Create first role
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $roleData);

        $response1->assertStatus(201);

        // Try to create second role with same name
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $roleData);

        $response2->assertStatus(422);
        $response2->assertJSONFragment(['code' => 'ROLE_NAME_DUPLICATE']);
    }

    /**
     * Test pagination and filtering
     */
    public function testRoleListPaginationAndFiltering(): void
    {
        // Create multiple roles
        for ($i = 1; $i <= 5; $i++) {
            $roleData = [
                'name' => "test_role_$i",
                'display_name' => "測試角色 $i",
                'is_active' => ($i % 2 === 0) // Even numbers are active
            ];

            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->withBodyFormat('json')->post('/api/v1/roles', $roleData);
        }

        // Test pagination
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles?page=1&per_page=3');

        $response->assertStatus(200);
        $json = $response->getJSON();
        $this->assertLessThanOrEqual(3, count($json->data));
        $this->assertEquals(1, $json->meta->current_page);

        // Test filtering by is_active
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles?is_active=true');

        $response->assertStatus(200);
        $roles = $response->getJSON()->data;

        foreach ($roles as $role) {
            $this->assertTrue($role->is_active, 'All returned roles should be active');
        }

        // Test search
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles?search=測試角色 1');

        $response->assertStatus(200);
    }

    /**
     * Test role permissions inheritance
     */
    public function testRolePermissionsInheritance(): void
    {
        // Create parent role
        $parentData = [
            'name' => 'parent_role',
            'display_name' => '父角色',
            'permissions' => [1, 2, 3]
        ];

        $parentResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $parentData);

        $parentId = $parentResponse->getJSON()->data->id;

        // Create child role
        $childData = [
            'name' => 'child_role',
            'display_name' => '子角色',
            'parent_role_id' => $parentId,
            'permissions' => [4, 5]
        ];

        $childResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/roles', $childData);

        $childId = $childResponse->getJSON()->data->id;

        // Get child role permissions with inheritance
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/roles/' . $childId . '/permissions?include_inherited=true');

        $response->assertStatus(200);
        $data = $response->getJSON()->data;

        // Should have 2 direct permissions
        $this->assertCount(2, $data->direct_permissions);

        // Should have 3 inherited permissions from parent
        $this->assertCount(3, $data->inherited_permissions);
    }

    /**
     * Helper method to get authentication token
     */
    protected function getAuthToken(): string
    {
        // TODO: Implement proper JWT token generation for testing
        return 'mock-jwt-token-for-testing';
    }
}
