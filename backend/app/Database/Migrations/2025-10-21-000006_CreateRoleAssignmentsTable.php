<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateRoleAssignmentsTable Migration
 *
 * 建立角色指派表
 * 代表將角色指派給使用者,支援時間性授權
 */
class CreateRoleAssignmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => '使用者 ID',
            ],
            'role_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => '角色 ID',
            ],
            'valid_from' => [
                'type'    => 'DATETIME',
                'null'    => false,
                
                'comment' => '有效開始時間',
            ],
            'valid_until' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '有效結束時間 (NULL=永久有效)',
            ],
            'assigned_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => '指派者 ID',
            ],
            'assigned_at' => [
                'type' => 'DATETIME',
                'null'    => false,
                
                'comment' => '指派時間',
            ],
            'revoked_at' => [
                'type' => 'DATETIME',
                'null'    => true,
                'comment' => '撤銷時間',
            ],
            'revoked_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => '撤銷者 ID',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'valid_from', 'valid_until'], false, 'idx_user_valid_period');
        $this->forge->addKey('role_id');
        $this->forge->addKey('assigned_by');
        $this->forge->addKey('valid_until', false, 'idx_valid_until');

        $this->forge->createTable('role_assignments', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
            'COMMENT' => '角色指派表 - 將角色指派給使用者,支援時間性授權',
        ]);

        // 新增外鍵約束
        $this->db->query('
            ALTER TABLE `role_assignments`
            ADD CONSTRAINT `fk_role_assignments_user`
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            ADD CONSTRAINT `fk_role_assignments_role`
                FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            ADD CONSTRAINT `fk_role_assignments_assigned_by`
                FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`)
                ON DELETE RESTRICT
                ON UPDATE CASCADE,
            ADD CONSTRAINT `fk_role_assignments_revoked_by`
                FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`)
                ON DELETE RESTRICT
                ON UPDATE CASCADE
        ');

        // 新增檢查約束: valid_from < valid_until (if valid_until is not NULL)
        $this->db->query('
            ALTER TABLE `role_assignments`
            ADD CONSTRAINT `chk_valid_period`
                CHECK (`valid_until` IS NULL OR `valid_from` < `valid_until`)
        ');
    }

    public function down()
    {
        // 先移除外鍵約束
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('ALTER TABLE `role_assignments` DROP FOREIGN KEY `fk_role_assignments_user`');
            $this->db->query('ALTER TABLE `role_assignments` DROP FOREIGN KEY `fk_role_assignments_role`');
            $this->db->query('ALTER TABLE `role_assignments` DROP FOREIGN KEY `fk_role_assignments_assigned_by`');
            $this->db->query('ALTER TABLE `role_assignments` DROP FOREIGN KEY `fk_role_assignments_revoked_by`');
            $this->db->query('ALTER TABLE `role_assignments` DROP CHECK `chk_valid_period`');
        }

        $this->forge->dropTable('role_assignments', true);
    }
}
