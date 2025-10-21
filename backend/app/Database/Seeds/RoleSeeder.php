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

        // 使用 Query Builder 插入資料
        foreach ($roles as $role) {
            $this->db->table('roles')->insert($role);
        }

        echo "✅ 已建立 " . count($roles) . " 個預設角色\n";

        // 建立角色階層關係 (Closure Table)
        // 每個角色至少需要一筆指向自己的記錄 (depth=0)
        $roleIds = $this->db->table('roles')
            ->select('id, name')
            ->whereIn('name', ['system_admin', 'sales_manager', 'sales_staff', 'customer_service'])
            ->get()
            ->getResultArray();

        $hierarchy = [];
        foreach ($roleIds as $role) {
            $hierarchy[] = [
                'ancestor_id'   => $role['id'],
                'descendant_id' => $role['id'],
                'depth'         => 0,
            ];
        }

        // 插入角色階層資料
        if (!empty($hierarchy)) {
            $this->db->table('role_hierarchy')->insertBatch($hierarchy);
            echo "✅ 已建立 " . count($hierarchy) . " 筆角色階層關係 (自我參照)\n";
        }
    }
}
