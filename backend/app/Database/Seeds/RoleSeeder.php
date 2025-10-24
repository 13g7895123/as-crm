<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * RoleSeeder
 *
 * 建立預設角色資料
 * 包含: 系統管理員、業務主管、業務人員、客服人員
 */
class RoleSeeder extends Seeder
{
    public function run()
    {
        // 注意: created_by 需要一個系統使用者 ID
        // 假設系統使用者 ID 為 1 (需要先建立一個系統管理員使用者)

        $roles = [
            [
                'name'         => 'system_admin',
                'display_name' => '系統管理員',
                'description'  => '擁有所有權限的超級管理員,可以管理所有系統功能和使用者權限',
                'is_active'    => 1,
                'is_system'    => 1,
                'parent_role_id' => null,
                'created_by'   => 1,
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'name'         => 'sales_manager',
                'display_name' => '業務主管',
                'description'  => '管理業務團隊和客戶,可以檢視報表和指派客戶給業務人員',
                'is_active'    => 1,
                'is_system'    => 1,
                'parent_role_id' => null,
                'created_by'   => 1,
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'name'         => 'sales_staff',
                'display_name' => '業務人員',
                'description'  => '處理客戶和訂單,可以檢視和編輯被指派的客戶資料',
                'is_active'    => 1,
                'is_system'    => 1,
                'parent_role_id' => null,
                'created_by'   => 1,
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'name'         => 'customer_service',
                'display_name' => '客服人員',
                'description'  => '處理客戶諮詢和支援,可以檢視客戶和訂單資料',
                'is_active'    => 1,
                'is_system'    => 1,
                'parent_role_id' => null,
                'created_by'   => 1,
                'created_at'   => date('Y-m-d H:i:s'),
            ],
        ];

        // 檢查現有角色，只插入不存在的
        $existingRoles = $this->db->table('roles')
            ->select('name')
            ->whereIn('name', ['system_admin', 'sales_manager', 'sales_staff', 'customer_service'])
            ->get()
            ->getResultArray();

        $existingRoleNames = array_column($existingRoles, 'name');

        // 過濾出需要插入的角色
        $insertedCount = 0;
        foreach ($roles as $role) {
            if (!in_array($role['name'], $existingRoleNames)) {
                $this->db->table('roles')->insert($role);
                $insertedCount++;
            }
        }

        if ($insertedCount > 0) {
            echo "✅ 已建立 {$insertedCount} 個預設角色\n";
        } else {
            echo "⚠️  所有角色已存在，跳過建立\n";
        }

        $skippedCount = count($roles) - $insertedCount;
        if ($skippedCount > 0) {
            echo "⚠️  跳過 {$skippedCount} 個已存在的角色\n";
        }

        // 建立角色階層關係 (Closure Table)
        // 每個角色至少需要一筆指向自己的記錄 (depth=0)
        $roleIds = $this->db->table('roles')
            ->select('id, name')
            ->whereIn('name', ['system_admin', 'sales_manager', 'sales_staff', 'customer_service'])
            ->get()
            ->getResultArray();

        // 檢查現有的階層關係
        $existingHierarchy = $this->db->table('role_hierarchy')
            ->select('ancestor_id, descendant_id')
            ->get()
            ->getResultArray();

        $existingPairs = [];
        foreach ($existingHierarchy as $h) {
            $existingPairs[$h['ancestor_id'] . '-' . $h['descendant_id']] = true;
        }

        // 過濾出需要插入的階層關係
        $hierarchy = [];
        foreach ($roleIds as $role) {
            $key = $role['id'] . '-' . $role['id'];
            if (!isset($existingPairs[$key])) {
                $hierarchy[] = [
                    'ancestor_id'   => $role['id'],
                    'descendant_id' => $role['id'],
                    'depth'         => 0,
                ];
            }
        }

        // 插入角色階層資料
        if (!empty($hierarchy)) {
            $this->db->table('role_hierarchy')->insertBatch($hierarchy);
            echo "✅ 已建立 " . count($hierarchy) . " 筆角色階層關係 (自我參照)\n";
        } else {
            echo "⚠️  所有角色階層關係已存在，跳過建立\n";
        }
    }
}
