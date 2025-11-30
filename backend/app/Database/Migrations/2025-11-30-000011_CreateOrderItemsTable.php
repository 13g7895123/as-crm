<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderItemsTable extends Migration
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
            'order_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => '訂單 ID',
            ],
            'product_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'comment'    => '產品名稱',
            ],
            'product_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'comment'    => '產品代碼',
            ],
            'quantity' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
                'comment'    => '數量',
            ],
            'unit_price' => [
                'type'      => 'DECIMAL',
                'constraint' => '12,2',
                'comment'   => '單價',
            ],
            'total_price' => [
                'type'      => 'DECIMAL',
                'constraint' => '15,2',
                'comment'   => '小計 (quantity * unit_price)',
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new \DateTime(),
                'comment' => '建立時間',
            ],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addKey('order_id');

        // 外鍵約束
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');

        $this->forge->createTable('order_items', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);
    }

    public function down()
    {
        $this->forge->dropTable('order_items', true);
    }
}
