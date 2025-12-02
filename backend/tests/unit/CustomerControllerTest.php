<?php

namespace Tests\unit;

use Tests\Support\ApiTestCase;

/**
 * CustomerController Unit Test
 *
 * Tests the customer management API endpoints
 *
 * @group unit
 */
class CustomerControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestCustomers();
    }

    /**
     * Seed test customers
     */
    private function seedTestCustomers(): void
    {
        $db = \Config\Database::connect();
        
        $customers = [
            [
                'code' => 'CUST001',
                'name' => '測試客戶一',
                'contact' => '張三',
                'phone' => '0912345678',
                'email' => 'cust1@example.com',
                'status' => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'code' => 'CUST002',
                'name' => '測試客戶二',
                'contact' => '李四',
                'phone' => '0923456789',
                'email' => 'cust2@example.com',
                'status' => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($customers as $customer) {
            $db->table('customers')->insert($customer);
        }
    }

    /**
     * Test get customers list
     */
    public function testGetCustomersList(): void
    {
        $result = $this->authGet('/api/v1/customers');
        $result->assertStatus(200);
    }

    /**
     * Test get customers list with pagination
     */
    public function testGetCustomersListWithPagination(): void
    {
        $result = $this->authGet('/api/v1/customers?page=1&per_page=2');
        $result->assertStatus(200);
    }

    /**
     * Test get customer by ID
     */
    public function testGetCustomerById(): void
    {
        $db = \Config\Database::connect();
        $customer = $db->table('customers')->where('code', 'CUST001')->get()->getRowArray();

        $result = $this->authGet('/api/v1/customers/' . $customer['id']);
        $result->assertStatus(200);
    }

    /**
     * Test get customer by invalid ID
     */
    public function testGetCustomerByInvalidId(): void
    {
        $result = $this->authGet('/api/v1/customers/99999');
        $result->assertStatus(404);
    }

    /**
     * Test create customer
     */
    public function testCreateCustomer(): void
    {
        $result = $this->authPost('/api/v1/customers', [
            'code' => 'NEWCUST',
            'name' => '新客戶',
            'contact' => '新聯絡人',
            'phone' => '0911111111',
            'email' => 'new@example.com',
            'status' => 'active',
        ]);

        $result->assertStatus(201);
    }

    /**
     * Test customers endpoint requires authentication
     */
    public function testCustomersRequiresAuth(): void
    {
        $result = $this->get('/api/v1/customers');
        $result->assertStatus(401);
    }
}
