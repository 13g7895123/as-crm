<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'code'       => 'CUST001',
                'name'       => 'TSMC',
                'contact'    => 'Mr. Chang',
                'phone'      => '0912-345-678',
                'email'      => 'contact@tsmc.com',
                'status'     => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'code'       => 'CUST002',
                'name'       => 'MediaTek',
                'contact'    => 'Ms. Li',
                'phone'      => '0923-456-789',
                'email'      => 'contact@mediatek.com',
                'status'     => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'code'       => 'CUST003',
                'name'       => 'Foxconn',
                'contact'    => 'Mr. Wang',
                'phone'      => '0934-567-890',
                'email'      => 'contact@foxconn.com',
                'status'     => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'code'       => 'CUST004',
                'name'       => 'ACER',
                'contact'    => 'Ms. Chen',
                'phone'      => '0945-678-901',
                'email'      => 'contact@acer.com',
                'status'     => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'code'       => 'CUST005',
                'name'       => 'Nvidia',
                'contact'    => 'Dr. Liu',
                'phone'      => '0956-789-012',
                'email'      => 'contact@nvidia.com',
                'status'     => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        // Using Query Builder
        $this->db->table('customers')->insertBatch($data);
    }
}
