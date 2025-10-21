<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateConditionRulesTable Migration
 *
 * 建立條件限制規則表
 * 定義權限的條件式限制 (如部門、區域、客戶分群)
 */
class CreateConditionRulesTable extends Migration
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
                'comment'  => '所屬角色 ID',
            ],
            'permission_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => '限制的權限 ID (NULL=適用於角色所有權限)',
            ],
            'condition_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
                'comment'    => '條件類型 (department, region, customer_group, order_status, amount_range)',
            ],
            'operator' => [
                'type'       => 'ENUM',
                'constraint' => ['equals', 'not_equals', 'in', 'not_in', 'greater_than', 'less_than', 'between'],
                'null'       => false,
                'comment'    => '運算子 (equals=等於, not_equals=不等於, in=包含於, not_in=不包含於, greater_than=大於, less_than=小於, between=介於之間)',
            ],
            'condition_value' => [
                'type'    => 'JSON',
                'null'    => false,
                'comment' => '條件值 (JSON 格式,依 condition_type 而定)',
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => '建立時間',
            ],
            'updated_at' => [
                'type'      => 'TIMESTAMP',
                'null'      => true,
                'on_update' => 'CURRENT_TIMESTAMP',
                'comment'   => '更新時間',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['role_id', 'permission_id'], false, 'idx_role_permission');
        $this->forge->addKey('condition_type');

        $this->forge->createTable('condition_rules', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
            'COMMENT' => '條件限制規則表 - 定義權限的條件式限制',
        ]);

        // 新增外鍵約束
        $this->db->query('
            ALTER TABLE `condition_rules`
            ADD CONSTRAINT `fk_condition_rules_role`
                FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            ADD CONSTRAINT `fk_condition_rules_permission`
                FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ');
    }

    public function down()
    {
        // 先移除外鍵約束
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('ALTER TABLE `condition_rules` DROP FOREIGN KEY `fk_condition_rules_role`');
            $this->db->query('ALTER TABLE `condition_rules` DROP FOREIGN KEY `fk_condition_rules_permission`');
        }

        $this->forge->dropTable('condition_rules', true);
    }
}
