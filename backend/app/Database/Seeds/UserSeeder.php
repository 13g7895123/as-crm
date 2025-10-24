<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * UserSeeder
 *
 * 建立預設系統使用者
 * 包含系統管理員帳號，用於初始化系統和建立其他資料
 */
class UserSeeder extends Seeder
{
    public function run()
    {
        // 檢查是否已有系統管理員
        $existingUser = $this->db->table('users')
            ->where('username', 'admin')
            ->get()
            ->getRow();

        if ($existingUser) {
            echo "⚠️  系統管理員已存在，跳過建立\n";
            return;
        }

        // 建立系統管理員
        // 預設密碼: admin123 (生產環境請務必修改)
        $admin = [
            'username'      => 'admin',
            'email'         => 'admin@crm.local',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'full_name'     => '系統管理員',
            'department'    => 'IT',
            'region'        => 'TW',
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $this->db->table('users')->insert($admin);

        // 取得插入的 ID
        $adminId = $this->db->insertID();

        echo "✅ 已建立系統管理員 (ID: {$adminId})\n";
        echo "   帳號: admin\n";
        echo "   密碼: admin123\n";
        echo "   ⚠️  請在生產環境中立即修改預設密碼！\n";
    }
}
