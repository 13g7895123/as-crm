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
        // Temporarily disable foreign key checks
        $this->db->simpleQuery('SET FOREIGN_KEY_CHECKS=0');
        
        // Seed test user first (roles have FK to users.created_by)
        $this->db->table('users')->insert([
            'username' => 'test_admin',
            'email' => 'test@example.com',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Test Admin',
            'department' => 'IT',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $userId = $this->db->insertID();

        // Seed permissions (use 'module' instead of 'resource' and 'name')
        $this->db->table('permissions')->insertBatch([
            [
                'module' => 'customer',
                'action' => 'view',
                'display_name' => '檢視客戶',
                'description' => '檢視客戶清單和詳細資料',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'module' => 'customer',
                'action' => 'edit',
                'display_name' => '編輯客戶',
                'description' => '建立、修改、刪除客戶資料',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'module' => 'customer',
                'action' => 'export',
                'display_name' => '匯出客戶',
                'description' => '匯出客戶清單為 CSV/Excel',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'module' => 'customer',
                'action' => 'assign',
                'display_name' => '指派客戶',
                'description' => '將客戶指派給業務人員',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'module' => 'order',
                'action' => 'view',
                'display_name' => '檢視訂單',
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
                'is_system' => 1,
                'is_active' => 1,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'sales_manager',
                'display_name' => '業務主管',
                'description' => '管理業務團隊和客戶',
                'is_system' => 1,
                'is_active' => 1,
                'created_by' => $userId,
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
        
        // Re-enable foreign key checks
        $this->db->simpleQuery('SET FOREIGN_KEY_CHECKS=1');
    }
}
