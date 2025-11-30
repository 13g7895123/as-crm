<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'order_no' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
                'comment'    => '訂單編號',
            ],
            'customer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => '客戶 ID',
            ],
            'amount' => [
                'type'      => 'DECIMAL',
                'constraint' => '15,2',
                'comment'   => '訂單金額',
            ],
            'order_date' => [
                'type'    => 'DATE',
                'comment' => '訂單日期',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'processing', 'completed', 'cancelled'],
                'default'    => 'pending',
                'comment'    => '訂單狀態',
            ],
            'description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => '訂單說明',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => '建立者 ID',
            ],
            'updated_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => '更新者 ID',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '建立時間',
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '更新時間',
            ],
            'deleted_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '刪除時間 (軟刪除)',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('customer_id');
        $this->forge->addKey('status');
        $this->forge->addKey('order_date');
        $this->forge->addKey('created_at');
        $this->forge->addKey('deleted_at');

        // 外鍵約束
        $this->forge->addForeignKey('customer_id', 'customers', 'id', '', 'CASCADE');

        $this->forge->createTable('orders', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);
    }

    public function down()
    {
        $this->forge->dropTable('orders', true);
    }
}
