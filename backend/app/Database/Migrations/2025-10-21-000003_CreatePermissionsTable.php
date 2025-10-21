<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreatePermissionsTable Migration
 *
 * 建立權限表
 * 代表系統中的權限,定義對特定功能模組的特定操作能力
 */
class CreatePermissionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'module' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
                'comment'    => '功能模組 (如 customer, order, report)',
            ],
            'action' => [
                'type'       => 'ENUM',
                'constraint' => ['view', 'edit', 'export', 'assign'],
                'null'       => false,
                'comment'    => '操作類型 (view=檢視, edit=編輯, export=匯出, assign=指派)',
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
                'comment' => '權限描述',
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
        $this->forge->addUniqueKey(['module', 'action'], 'unique_module_action');
        $this->forge->addKey('module');

        $this->forge->createTable('permissions', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
            'COMMENT' => '權限表 - 定義系統中的各項權限',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('permissions', true);
    }
}
