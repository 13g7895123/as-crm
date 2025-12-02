<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateAuditLogsTable Migration
 *
 * 建立審計記錄表
 * 記錄所有使用者操作,支援完整的回溯和稽核
 * 包含按月份分割策略 (Partitioning)
 */
class CreateAuditLogsTable extends Migration
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
                'comment'  => '操作者 ID',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
                'comment'    => '操作類型 (view, create, update, delete, export, assign, permission_check_failed)',
            ],
            'target_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
                'comment'    => '目標資料類型 (customer, order, role, user_permission)',
            ],
            'target_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => '目標資料 ID',
            ],
            'old_values' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '修改前的欄位值 (僅 update)',
            ],
            'new_values' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '修改後的欄位值 (create/update)',
            ],
            'result' => [
                'type'       => 'ENUM',
                'constraint' => ['success', 'failed', 'denied'],
                'null'       => false,
                'default'    => 'success',
                'comment'    => '操作結果 (success=成功, failed=失敗, denied=拒絕)',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
                'null'       => false,
                'comment'    => 'IP 位址 (支援 IPv6)',
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'User Agent',
            ],
            'request_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '36',
                'null'       => true,
                'comment'    => '請求 ID (correlation ID for tracing)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null'    => false,
                
                'comment' => '操作時間',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'created_at'], false, 'idx_user_created');
        $this->forge->addKey(['target_type', 'target_id', 'created_at'], false, 'idx_target_created');
        $this->forge->addKey(['action', 'created_at'], false, 'idx_action_created');
        $this->forge->addKey(['result', 'created_at'], false, 'idx_result_created');
        $this->forge->addKey('request_id');

        $this->forge->createTable('audit_logs', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
            'COMMENT' => '審計記錄表 - 記錄所有使用者操作',
        ]);

        // 新增外鍵約束
        // 注意: audit_logs 不使用 CASCADE DELETE,保留審計記錄即使使用者被刪除
        $this->db->query('
            ALTER TABLE `audit_logs`
            ADD CONSTRAINT `fk_audit_logs_user`
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                ON DELETE RESTRICT
                ON UPDATE CASCADE
        ');

        // 分割策略: 按月份分割
        // 注意: MariaDB/MySQL 的分割功能需要在表建立後手動設定
        // 以下為範例,實際執行時需要根據當前月份和未來月份建立分割區

        // 建立審計記錄歸檔表 (用於 90 天後的資料)
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
                'comment'  => '操作者 ID',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
                'comment'    => '操作類型',
            ],
            'target_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
                'comment'    => '目標資料類型',
            ],
            'target_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => '目標資料 ID',
            ],
            'old_values' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '修改前的欄位值',
            ],
            'new_values' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => '修改後的欄位值',
            ],
            'result' => [
                'type'       => 'ENUM',
                'constraint' => ['success', 'failed', 'denied'],
                'null'       => false,
                'default'    => 'success',
                'comment'    => '操作結果',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
                'null'       => false,
                'comment'    => 'IP 位址',
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'User Agent',
            ],
            'request_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '36',
                'null'       => true,
                'comment'    => '請求 ID',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null'    => false,
                'comment' => '操作時間',
            ],
            'archived_at' => [
                'type' => 'DATETIME',
                'null'    => false,
                
                'comment' => '歸檔時間',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'created_at'], false, 'idx_user_created_archive');
        $this->forge->addKey(['target_type', 'target_id', 'created_at'], false, 'idx_target_created_archive');
        $this->forge->addKey('created_at');

        $this->forge->createTable('audit_logs_archive', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
            'COMMENT' => '審計記錄歸檔表 - 儲存 90 天前的審計記錄',
        ]);
    }

    public function down()
    {
        // 安全地移除表格
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->simpleQuery('SET FOREIGN_KEY_CHECKS=0');
        }

        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('audit_logs_archive', true);
        
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->simpleQuery('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
