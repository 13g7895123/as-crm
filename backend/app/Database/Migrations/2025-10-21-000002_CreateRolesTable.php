<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateRolesTable Migration
 *
 * 建立角色表
 * 代表系統中的角色,可以是預設角色或自訂角色
 */
class CreateRolesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
                'comment'    => '角色名稱 (英文識別碼)',
            ],
            'display_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
                'comment'    => '顯示名稱 (繁體中文)',
            ],
            'description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => '角色描述',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => '1',
                'default'    => 1,
                'null'       => false,
                'comment'    => '啟用狀態 (1=啟用, 0=停用)',
            ],
            'is_system' => [
                'type'       => 'TINYINT',
                'constraint' => '1',
                'default'    => 0,
                'null'       => false,
                'comment'    => '是否為系統預設角色 (不可刪除)',
            ],
            'parent_role_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => '父角色 ID (用於繼承)',
            ],
            'created_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => '建立者 ID',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => false,
                'comment' => '建立時間',
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->addKey('is_active');
        $this->forge->addKey('parent_role_id');
        $this->forge->addKey('created_by');

        // 建立外鍵約束
        // 注意: 需要在 users 表建立後才能新增外鍵
        $this->forge->createTable('roles', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
            'COMMENT' => '角色表 - 定義系統中的角色',
        ]);

        // 新增外鍵約束
        $this->db->query('
            ALTER TABLE `roles`
            ADD CONSTRAINT `fk_roles_parent_role`
                FOREIGN KEY (`parent_role_id`) REFERENCES `roles` (`id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE,
            ADD CONSTRAINT `fk_roles_created_by`
                FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
                ON DELETE RESTRICT
                ON UPDATE CASCADE
        ');
    }

    public function down()
    {
        // 先移除外鍵約束
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('ALTER TABLE `roles` DROP FOREIGN KEY `fk_roles_parent_role`');
            $this->db->query('ALTER TABLE `roles` DROP FOREIGN KEY `fk_roles_created_by`');
        }

        $this->forge->dropTable('roles', true);
    }
}
