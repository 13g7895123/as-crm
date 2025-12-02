<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateRolePermissionsTable Migration
 *
 * 建立角色-權限關聯表
 * 定義角色擁有哪些權限 (Many-to-Many 關聯)
 */
class CreateRolePermissionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'role_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => '角色 ID',
            ],
            'permission_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => '權限 ID',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null'    => false,
                
                'comment' => '建立時間',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['role_id', 'permission_id'], 'unique_role_permission');
        $this->forge->addKey('role_id');
        $this->forge->addKey('permission_id');

        $this->forge->createTable('role_permissions', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
            'COMMENT' => '角色權限關聯表 - 定義角色擁有哪些權限',
        ]);

        // 新增外鍵約束
        $this->db->query('
            ALTER TABLE `role_permissions`
            ADD CONSTRAINT `fk_role_permissions_role`
                FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            ADD CONSTRAINT `fk_role_permissions_permission`
                FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');
    }

    public function down()
    {
        // 安全地移除表格
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->simpleQuery('SET FOREIGN_KEY_CHECKS=0');
        }

        $this->forge->dropTable('role_permissions', true);
        
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->simpleQuery('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
