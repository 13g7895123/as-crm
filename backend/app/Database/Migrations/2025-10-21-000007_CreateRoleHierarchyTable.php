<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateRoleHierarchyTable Migration
 *
 * 建立角色階層表 (Closure Table 模式)
 * 使用 Closure Table 模式儲存角色之間的階層關係,支援多層繼承
 */
class CreateRoleHierarchyTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ancestor_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => '祖先角色 ID',
            ],
            'descendant_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => '後代角色 ID',
            ],
            'depth' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
                'comment' => '層級深度 (0=自己, 1=直接子角色, 2+=間接子角色)',
            ],
        ]);

        $this->forge->addKey(['ancestor_id', 'descendant_id'], true);
        $this->forge->addKey('descendant_id');
        $this->forge->addKey('depth');

        $this->forge->createTable('role_hierarchy', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
            'COMMENT' => '角色階層表 - 使用 Closure Table 儲存角色之間的階層關係',
        ]);

        // 新增外鍵約束
        $this->db->query('
            ALTER TABLE `role_hierarchy`
            ADD CONSTRAINT `fk_role_hierarchy_ancestor`
                FOREIGN KEY (`ancestor_id`) REFERENCES `roles` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            ADD CONSTRAINT `fk_role_hierarchy_descendant`
                FOREIGN KEY (`descendant_id`) REFERENCES `roles` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');

        // 新增檢查約束: depth >= 0
        $this->db->query('
            ALTER TABLE `role_hierarchy`
            ADD CONSTRAINT `chk_depth_non_negative`
                CHECK (`depth` >= 0)
        ');
    }

    public function down()
    {
        // 先移除外鍵約束 (IF EXISTS)
        if ($this->db->DBDriver === 'MySQLi') {
            // Check if table exists before trying to drop constraints
            if ($this->db->tableExists('role_hierarchy')) {
                // Use silent queries to ignore errors when FK doesn't exist
                $this->db->simpleQuery('SET FOREIGN_KEY_CHECKS=0');
            }
        }

        $this->forge->dropTable('role_hierarchy', true);
        
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->simpleQuery('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
