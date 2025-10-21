<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateUsersTable Migration
 *
 * 建立使用者表
 * 此表假設不存在,需要建立完整的使用者資料結構
 * 支援 RBAC 系統所需的欄位
 */
class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
                'comment'    => '使用者名稱',
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
                'comment'    => '電子郵件',
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
                'comment'    => '密碼雜湊值',
            ],
            'full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => '全名',
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'comment'    => '部門 (用於條件限制)',
            ],
            'region' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'comment'    => '區域 (用於條件限制)',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => '1',
                'default'    => 1,
                'null'       => false,
                'comment'    => '啟用狀態 (1=啟用, 0=停用)',
            ],
            'last_login_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '最後登入時間',
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => '建立時間',
            ],
            'updated_at' => [
                'type'       => 'TIMESTAMP',
                'null'       => true,
                'on_update'  => 'CURRENT_TIMESTAMP',
                'comment'    => '更新時間',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('is_active');
        $this->forge->addKey('department');
        $this->forge->addKey('region');

        $this->forge->createTable('users', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
            'COMMENT' => '使用者表 - 儲存系統使用者基本資料',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('users', true);
    }
}
