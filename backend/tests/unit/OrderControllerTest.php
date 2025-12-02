<?php

namespace Tests\unit;

use Tests\Support\ApiTestCase;

/**
 * OrderController Unit Test
 *
 * Tests the order management API endpoints
 *
 * @group unit
 */
class OrderControllerTest extends ApiTestCase
{
    protected int $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    /**
     * Seed test data for orders
     */
    private function seedTestData(): void
    {
        $db = \Config\Database::connect();
        
        // Create a customer for orders
        $db->table('customers')->insert([
            'code' => 'ORDCUST001',
            'name' => '訂單測試客戶',
            'contact' => '測試人',
            'phone' => '0912345678',
            'email' => 'ordcust@example.com',
            'status' => 'active',
            'created_by' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->customerId = $db->insertID();

        // Create test orders
        $orders = [
            [
                'order_no' => 'ORD-TEST-001',
                'customer_id' => $this->customerId,
                'amount' => 10000.00,
                'order_date' => date('Y-m-d'),
                'status' => 'pending',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'order_no' => 'ORD-TEST-002',
                'customer_id' => $this->customerId,
                'amount' => 25000.00,
                'order_date' => date('Y-m-d'),
                'status' => 'completed',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($orders as $order) {
            $db->table('orders')->insert($order);
        }
    }

    /**
     * Test get orders list
     */
    public function testGetOrdersList(): void
    {
        $result = $this->authGet('/api/v1/orders');
        $result->assertStatus(200);
    }

    /**
     * Test get orders list with pagination
     */
    public function testGetOrdersListWithPagination(): void
    {
        $result = $this->authGet('/api/v1/orders?page=1&per_page=2');
        $result->assertStatus(200);
    }

    /**
     * Test get order by ID
     */
    public function testGetOrderById(): void
    {
        $db = \Config\Database::connect();
        $order = $db->table('orders')->where('order_no', 'ORD-TEST-001')->get()->getRowArray();

        $result = $this->authGet('/api/v1/orders/' . $order['id']);
        $result->assertStatus(200);
    }

    /**
     * Test get order by invalid ID
     */
    public function testGetOrderByInvalidId(): void
    {
        $result = $this->authGet('/api/v1/orders/99999');
        $result->assertStatus(404);
    }

    /**
     * Test create order
     */
    public function testCreateOrder(): void
    {
        $result = $this->authPost('/api/v1/orders', [
            'customer_id' => $this->customerId,
            'amount' => 15000.00,
            'order_date' => date('Y-m-d'),
            'status' => 'pending',
            'description' => '測試訂單',
        ]);

        $result->assertStatus(201);
    }

    /**
     * Test orders endpoint requires authentication
     */
    public function testOrdersRequiresAuth(): void
    {
        $result = $this->get('/api/v1/orders');
        $result->assertStatus(401);
    }
}
