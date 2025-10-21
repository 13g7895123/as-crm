<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * PermissionSeeder
 *
 * 建立預設權限資料
 * 包含客戶/訂單/報表/角色/使用者權限管理模組的各項權限
 */
class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // 客戶管理模組
            [
                'module'       => 'customer',
                'action'       => 'view',
                'display_name' => '檢視客戶',
                'description'  => '檢視客戶清單和詳細資料',
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'module'       => 'customer',
                'action'       => 'edit',
                'display_name' => '編輯客戶',
                'description'  => '建立、修改、刪除客戶資料',
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'module'       => 'customer',
                'action'       => 'export',
                'display_name' => '匯出客戶',
                'description'  => '匯出客戶清單為 CSV/Excel',
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'module'       => 'customer',
                'action'       => 'assign',
                'display_name' => '指派客戶',
                'description'  => '將客戶指派給業務人員',
                'created_at'   => date('Y-m-d H:i:s'),
            ],

            // 訂單管理模組
            [
                'module'       => 'order',
                'action'       => 'view',
                'display_name' => '檢視訂單',
                'description'  => '檢視訂單清單和詳細資料',
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'module'       => 'order',
                'action'       => 'edit',
                'display_name' => '編輯訂單',
                'description'  => '建立、修改訂單',
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'module'       => 'order',
                'action'       => 'export',
                'display_name' => '匯出訂單',
                'description'  => '匯出訂單清單',
                'created_at'   => date('Y-m-d H:i:s'),
            ],

            // 報表中心模組
            [
                'module'       => 'report',
                'action'       => 'view',
                'display_name' => '檢視報表',
                'description'  => '檢視業績報表和統計',
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'module'       => 'report',
                'action'       => 'export',
                'display_name' => '匯出報表',
                'description'  => '匯出報表資料',
                'created_at'   => date('Y-m-d H:i:s'),
            ],

            // 角色管理模組
            [
                'module'       => 'role',
                'action'       => 'view',
                'display_name' => '檢視角色',
                'description'  => '檢視角色清單和權限',
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'module'       => 'role',
                'action'       => 'edit',
                'display_name' => '編輯角色',
                'description'  => '建立、修改、刪除角色',
                'created_at'   => date('Y-m-d H:i:s'),
            ],

            // 使用者權限管理模組
            [
                'module'       => 'user_permission',
                'action'       => 'view',
                'display_name' => '檢視使用者權限',
                'description'  => '檢視使用者的角色和權限',
                'created_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'module'       => 'user_permission',
                'action'       => 'edit',
                'display_name' => '編輯使用者權限',
                'description'  => '指派或撤銷使用者的角色',
                'created_at'   => date('Y-m-d H:i:s'),
            ],
        ];

        // 使用 insertBatch 批次插入資料以提升效能
        $this->db->table('permissions')->insertBatch($permissions);

        echo "✅ 已建立 " . count($permissions) . " 個預設權限\n";

        // 顯示各模組的權限統計
        $moduleStats = [];
        foreach ($permissions as $perm) {
            $module = $perm['module'];
            if (!isset($moduleStats[$module])) {
                $moduleStats[$module] = 0;
            }
            $moduleStats[$module]++;
        }

        echo "\n各模組權限統計:\n";
        foreach ($moduleStats as $module => $count) {
            echo "  - {$module}: {$count} 個權限\n";
        }
    }
}
