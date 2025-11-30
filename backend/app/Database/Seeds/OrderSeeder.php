<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run()
    {
        $orders = [
            [
                'order_no'   => 'ORD-20231001-001',
                'customer_id' => 1,
                'amount'     => 1500000.00,
                'order_date' => '2023-10-01',
                'status'     => 'completed',
                'description' => 'First order from TSMC',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'order_no'   => 'ORD-20231002-002',
                'customer_id' => 2,
                'amount'     => 800000.00,
                'order_date' => '2023-10-02',
                'status'     => 'processing',
                'description' => 'MediaTek order',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'order_no'   => 'ORD-20231003-003',
                'customer_id' => 3,
                'amount'     => 2200000.00,
                'order_date' => '2023-10-03',
                'status'     => 'pending',
                'description' => 'Large order from Foxconn',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'order_no'   => 'ORD-20231004-004',
                'customer_id' => 1,
                'amount'     => 950000.00,
                'order_date' => '2023-10-04',
                'status'     => 'completed',
                'description' => 'TSMC second order',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'order_no'   => 'ORD-20231005-005',
                'customer_id' => 4,
                'amount'     => 550000.00,
                'order_date' => '2023-10-05',
                'status'     => 'processing',
                'description' => 'ACER order',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'order_no'   => 'ORD-20231006-006',
                'customer_id' => 5,
                'amount'     => 1800000.00,
                'order_date' => '2023-10-06',
                'status'     => 'pending',
                'description' => 'Nvidia GPU order',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('orders')->insertBatch($orders);

        // Insert order items
        $orderItems = [
            // Order 1 items
            [
                'order_id'    => 1,
                'product_name' => 'Microchip A100',
                'product_code' => 'PROD001',
                'quantity'    => 1000,
                'unit_price'  => 1500.00,
                'total_price' => 1500000.00,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            // Order 2 items
            [
                'order_id'    => 2,
                'product_name' => 'Processor MT6789',
                'product_code' => 'PROD002',
                'quantity'    => 500,
                'unit_price'  => 1600.00,
                'total_price' => 800000.00,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            // Order 3 items
            [
                'order_id'    => 3,
                'product_name' => 'Logic Board Assembly',
                'product_code' => 'PROD003',
                'quantity'    => 2000,
                'unit_price'  => 1100.00,
                'total_price' => 2200000.00,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            // Order 4 items
            [
                'order_id'    => 4,
                'product_name' => 'Memory Module DDR5',
                'product_code' => 'PROD004',
                'quantity'    => 950,
                'unit_price'  => 1000.00,
                'total_price' => 950000.00,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            // Order 5 items
            [
                'order_id'    => 5,
                'product_name' => 'Display Panel 15.6"',
                'product_code' => 'PROD005',
                'quantity'    => 500,
                'unit_price'  => 1100.00,
                'total_price' => 550000.00,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            // Order 6 items
            [
                'order_id'    => 6,
                'product_name' => 'GPU RTX 4090',
                'product_code' => 'PROD006',
                'quantity'    => 300,
                'unit_price'  => 6000.00,
                'total_price' => 1800000.00,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('order_items')->insertBatch($orderItems);
    }
}
