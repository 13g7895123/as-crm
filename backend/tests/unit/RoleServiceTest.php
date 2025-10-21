<?php

namespace Tests\Unit;

use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;
use App\Models\ConditionRuleModel;
use App\Services\RoleService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * RoleService Unit Test
 *
 * Tests the business logic of RoleService class
 * including role CRUD operations, permission management, and condition rules
 *
 * @group unit
 */
class RoleServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';
    protected $namespace   = 'App';

    protected RoleService $roleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roleService = new RoleService();
    }

    /**
     * Test creating a role with basic information
     */
    public function testCreateRoleWithBasicInfo(): void
    {
        $data = [
            'name' => 'test_role',
            'display_name' => '測試角色',
            'description' => '這是一個測試角色',
            'is_active' => true
        ];

        $role = $this->roleService->createRole($data);

        $this->assertIsArray($role);
        $this->assertEquals('test_role', $role['name']);
        $this->assertEquals('測試角色', $role['display_name']);
        $this->assertEquals('這是一個測試角色', $role['description']);
        $this->assertTrue($role['is_active']);
        $this->assertFalse($role['is_system']);
    }

    /**
     * Test creating a role with permissions
     */
    public function testCreateRoleWithPermissions(): void
    {
        $data = [
            'name' => 'role_with_permissions',
            'display_name' => '有權限的角色',
            'permissions' => [1, 2, 3]
        ];

        $role = $this->roleService->createRole($data);

        $this->assertArrayHasKey('permissions', $role);
        $this->assertIsArray($role['permissions']);
        $this->assertCount(3, $role['permissions']);

        $permissionIds = array_column($role['permissions'], 'id');
        $this->assertContains(1, $permissionIds);
        $this->assertContains(2, $permissionIds);
        $this->assertContains(3, $permissionIds);
    }

    /**
     * Test creating a role with condition rules
     */
    public function testCreateRoleWithConditionRules(): void
    {
        $data = [
            'name' => 'role_with_conditions',
            'display_name' => '有條件限制的角色',
            'permissions' => [1],
            'condition_rules' => [
                [
                    'permission_id' => 1,
                    'condition_type' => 'region',
                    'operator' => 'equals',
                    'condition_value' => ['value' => '華東']
                ],
                [
                    'permission_id' => null,
                    'condition_type' => 'department',
                    'operator' => 'in',
                    'condition_value' => ['values' => ['業務部', '銷售部']]
                ]
            ]
        ];

        $role = $this->roleService->createRole($data);

        $this->assertArrayHasKey('condition_rules', $role);
        $this->assertIsArray($role['condition_rules']);
        $this->assertCount(2, $role['condition_rules']);
    }

    /**
     * Test updating a role
     */
    public function testUpdateRole(): void
    {
        // Create a role first
        $createData = [
            'name' => 'update_test_role',
            'display_name' => '待更新角色',
            'is_active' => true
        ];

        $role = $this->roleService->createRole($createData);
        $roleId = $role['id'];

        // Update the role
        $updateData = [
            'display_name' => '已更新角色',
            'description' => '更新後的描述',
            'is_active' => false
        ];

        $updated = $this->roleService->updateRole($roleId, $updateData);

        $this->assertEquals('已更新角色', $updated['display_name']);
        $this->assertEquals('更新後的描述', $updated['description']);
        $this->assertFalse($updated['is_active']);
        $this->assertEquals('update_test_role', $updated['name']); // Name should not change
    }

    /**
     * Test updating role permissions
     */
    public function testUpdateRolePermissions(): void
    {
        // Create a role with permissions
        $createData = [
            'name' => 'permission_update_test',
            'display_name' => '權限更新測試',
            'permissions' => [1, 2]
        ];

        $role = $this->roleService->createRole($createData);
        $roleId = $role['id'];

        // Update permissions
        $updateData = [
            'permissions' => [2, 3, 4] // Remove 1, keep 2, add 3 and 4
        ];

        $updated = $this->roleService->updateRole($roleId, $updateData);

        $this->assertCount(3, $updated['permissions']);

        $permissionIds = array_column($updated['permissions'], 'id');
        $this->assertNotContains(1, $permissionIds);
        $this->assertContains(2, $permissionIds);
        $this->assertContains(3, $permissionIds);
        $this->assertContains(4, $permissionIds);
    }

    /**
     * Test deleting a custom role
     */
    public function testDeleteCustomRole(): void
    {
        $data = [
            'name' => 'deletable_role',
            'display_name' => '可刪除角色'
        ];

        $role = $this->roleService->createRole($data);
        $roleId = $role['id'];

        $result = $this->roleService->deleteRole($roleId);

        $this->assertTrue($result);

        // Verify role is deleted
        $this->expectException(\RuntimeException::class);
        $this->roleService->getRoleById($roleId);
    }

    /**
     * Test cannot delete system role
     */
    public function testCannotDeleteSystemRole(): void
    {
        // Get a system role from seeder
        $roles = $this->roleService->getRoles(['is_system' => true]);
        $this->assertNotEmpty($roles['data']);

        $systemRoleId = $roles['data'][0]['id'];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('無法刪除系統角色');

        $this->roleService->deleteRole($systemRoleId);
    }

    /**
     * Test role name validation
     */
    public function testRoleNameValidation(): void
    {
        $data = [
            'name' => 'Invalid Role Name', // Contains spaces and uppercase
            'display_name' => '無效角色名'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->roleService->createRole($data);
    }

    /**
     * Test role name uniqueness
     */
    public function testRoleNameUniqueness(): void
    {
        $data1 = [
            'name' => 'unique_role',
            'display_name' => '唯一角色1'
        ];

        $this->roleService->createRole($data1);

        $data2 = [
            'name' => 'unique_role', // Same name
            'display_name' => '唯一角色2'
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('角色名稱已存在');

        $this->roleService->createRole($data2);
    }

    /**
     * Test getting role by ID
     */
    public function testGetRoleById(): void
    {
        $data = [
            'name' => 'get_test_role',
            'display_name' => '查詢測試角色',
            'permissions' => [1, 2]
        ];

        $created = $this->roleService->createRole($data);
        $roleId = $created['id'];

        $role = $this->roleService->getRoleById($roleId);

        $this->assertEquals($roleId, $role['id']);
        $this->assertEquals('get_test_role', $role['name']);
        $this->assertEquals('查詢測試角色', $role['display_name']);
        $this->assertArrayHasKey('permissions', $role);
        $this->assertArrayHasKey('condition_rules', $role);
    }

    /**
     * Test getting roles with filters
     */
    public function testGetRolesWithFilters(): void
    {
        // Create test roles
        $this->roleService->createRole([
            'name' => 'active_role',
            'display_name' => '啟用角色',
            'is_active' => true
        ]);

        $this->roleService->createRole([
            'name' => 'inactive_role',
            'display_name' => '停用角色',
            'is_active' => false
        ]);

        // Filter by is_active
        $activeRoles = $this->roleService->getRoles(['is_active' => true]);
        foreach ($activeRoles['data'] as $role) {
            $this->assertTrue($role['is_active']);
        }

        $inactiveRoles = $this->roleService->getRoles(['is_active' => false]);
        foreach ($inactiveRoles['data'] as $role) {
            $this->assertFalse($role['is_active']);
        }
    }

    /**
     * Test getting roles with search
     */
    public function testGetRolesWithSearch(): void
    {
        $this->roleService->createRole([
            'name' => 'searchable_manager',
            'display_name' => '可搜尋的管理員'
        ]);

        $this->roleService->createRole([
            'name' => 'other_staff',
            'display_name' => '其他員工'
        ]);

        $results = $this->roleService->getRoles(['search' => '管理員']);

        $this->assertNotEmpty($results['data']);

        $found = false;
        foreach ($results['data'] as $role) {
            if (str_contains($role['display_name'], '管理員')) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Test pagination
     */
    public function testGetRolesWithPagination(): void
    {
        // Create multiple roles
        for ($i = 1; $i <= 10; $i++) {
            $this->roleService->createRole([
                'name' => "pagination_test_$i",
                'display_name' => "分頁測試 $i"
            ]);
        }

        $page1 = $this->roleService->getRoles(['page' => 1, 'per_page' => 5]);
        $this->assertLessThanOrEqual(5, count($page1['data']));
        $this->assertEquals(1, $page1['meta']['current_page']);
        $this->assertGreaterThanOrEqual(10, $page1['meta']['total']);

        $page2 = $this->roleService->getRoles(['page' => 2, 'per_page' => 5]);
        $this->assertEquals(2, $page2['meta']['current_page']);
    }

    /**
     * Test condition type validation
     */
    public function testConditionTypeValidation(): void
    {
        $data = [
            'name' => 'invalid_condition_role',
            'display_name' => '無效條件角色',
            'permissions' => [1],
            'condition_rules' => [
                [
                    'condition_type' => 'invalid_type',
                    'operator' => 'equals',
                    'condition_value' => ['value' => 'test']
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的條件類型');

        $this->roleService->createRole($data);
    }

    /**
     * Test operator validation
     */
    public function testOperatorValidation(): void
    {
        $data = [
            'name' => 'invalid_operator_role',
            'display_name' => '無效運算子角色',
            'permissions' => [1],
            'condition_rules' => [
                [
                    'condition_type' => 'region',
                    'operator' => 'invalid_operator',
                    'condition_value' => ['value' => 'test']
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的運算子');

        $this->roleService->createRole($data);
    }

    /**
     * Test getting role permissions
     */
    public function testGetRolePermissions(): void
    {
        // Create a role with permissions
        $role = $this->roleService->createRole([
            'name' => 'permissions_test_role',
            'display_name' => '權限測試角色',
            'permissions' => [1, 2, 3]
        ]);

        $permissions = $this->roleService->getRolePermissions($role['id'], false);

        $this->assertArrayHasKey('direct_permissions', $permissions);
        $this->assertCount(3, $permissions['direct_permissions']);
    }

    /**
     * Test getting role permissions with inheritance
     */
    public function testGetRolePermissionsWithInheritance(): void
    {
        // Create parent role
        $parent = $this->roleService->createRole([
            'name' => 'parent_permissions_test',
            'display_name' => '父角色權限測試',
            'permissions' => [1, 2, 3]
        ]);

        // Create child role
        $child = $this->roleService->createRole([
            'name' => 'child_permissions_test',
            'display_name' => '子角色權限測試',
            'parent_role_id' => $parent['id'],
            'permissions' => [4, 5]
        ]);

        $permissions = $this->roleService->getRolePermissions($child['id'], true);

        $this->assertArrayHasKey('direct_permissions', $permissions);
        $this->assertArrayHasKey('inherited_permissions', $permissions);
        $this->assertCount(2, $permissions['direct_permissions']);
        $this->assertCount(3, $permissions['inherited_permissions']);
    }
}
