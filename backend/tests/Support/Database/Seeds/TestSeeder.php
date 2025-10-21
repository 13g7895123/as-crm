<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Test Seeder
 *
 * Seeds test data for running tests
 */
class TestSeeder extends Seeder
{
    public function run()
    {
        // Seed permissions
        $this->db->table('permissions')->insertBatch([
            [
                'name' => 'customer.view',
                'resource' => 'customer',
                'action' => 'view',
                'description' => '檢視客戶清單和詳細資料',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'customer.edit',
                'resource' => 'customer',
                'action' => 'edit',
                'description' => '建立、修改、刪除客戶資料',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'customer.export',
                'resource' => 'customer',
                'action' => 'export',
                'description' => '匯出客戶清單為 CSV/Excel',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'customer.assign',
                'resource' => 'customer',
                'action' => 'assign',
                'description' => '將客戶指派給業務人員',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'order.view',
                'resource' => 'order',
                'action' => 'view',
                'description' => '檢視訂單清單和詳細資料',
                'created_at' => date('Y-m-d H:i:s')
            ],
        ]);

        // Seed system roles
        $this->db->table('roles')->insertBatch([
            [
                'name' => 'system_admin',
                'display_name' => '系統管理員',
                'description' => '擁有所有權限的超級管理員',
                'level' => 100,
                'is_system' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'sales_manager',
                'display_name' => '業務主管',
                'description' => '管理業務團隊和客戶',
                'level' => 50,
                'is_system' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ],
        ]);

        // Get role and permission IDs
        $systemAdminRole = $this->db->table('roles')->where('name', 'system_admin')->get()->getRow();
        $permissions = $this->db->table('permissions')->get()->getResult();

        // Assign all permissions to system admin
        $rolePermissions = [];
        foreach ($permissions as $permission) {
            $rolePermissions[] = [
                'role_id' => $systemAdminRole->id,
                'permission_id' => $permission->id,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        $this->db->table('role_permissions')->insertBatch($rolePermissions);

        // Seed test user
        $this->db->table('users')->insert([
            'username' => 'test_admin',
            'email' => 'test@example.com',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Test Admin',
            'department' => 'IT',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
