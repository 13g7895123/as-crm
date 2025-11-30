<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Customer Controller
 *
 * Handles REST API endpoints for customer management
 * Endpoints: GET/POST /customers, GET/PUT/DELETE /customers/{id}
 */
class CustomerController extends BaseController
{
    /**
     * GET /api/v1/customers
     *
     * Get all customers with pagination and filtering
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        try {
            $page = (int)($this->request->getGet('page') ?? 1);
            $perPage = (int)($this->request->getGet('per_page') ?? 20);
            $search = $this->request->getGet('search');

            // Validate per_page
            if ($perPage > 100) {
                $perPage = 100;
            }

            // For now, return mock data structure
            // TODO: Connect to database
            $customers = [
                [
                    'id' => 1,
                    'name' => 'TSMC',
                    'code' => 'CUST001',
                    'contact' => 'Mr. Chang',
                    'phone' => '0912-345-678',
                    'email' => 'contact@tsmc.com',
                    'status' => 'active',
                    'created_at' => '2023-10-01T00:00:00Z',
                ],
                [
                    'id' => 2,
                    'name' => 'MediaTek',
                    'code' => 'CUST002',
                    'contact' => 'Ms. Li',
                    'phone' => '0923-456-789',
                    'email' => 'contact@mediatek.com',
                    'status' => 'active',
                    'created_at' => '2023-10-02T00:00:00Z',
                ],
                [
                    'id' => 3,
                    'name' => 'Foxconn',
                    'code' => 'CUST003',
                    'contact' => 'Mr. Wang',
                    'phone' => '0934-567-890',
                    'email' => 'contact@foxconn.com',
                    'status' => 'active',
                    'created_at' => '2023-10-03T00:00:00Z',
                ],
            ];

            // Filter by search term
            if ($search) {
                $customers = array_filter($customers, function ($customer) use ($search) {
                    return stripos($customer['name'], $search) !== false ||
                           stripos($customer['code'], $search) !== false ||
                           stripos($customer['contact'], $search) !== false;
                });
            }

            // Calculate pagination
            $total = count($customers);
            $totalPages = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;
            $paginatedCustomers = array_slice($customers, $offset, $perPage);

            return $this->respond([
                'data' => array_values($paginatedCustomers),
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得客戶清單失敗: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/v1/customers
     *
     * Create a new customer
     *
     * @return ResponseInterface
     */
    public function create(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required = ['name', 'contact', 'email'];
            $errors = [];

            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = [ucfirst($field) . '為必填'];
                }
            }

            if (!empty($errors)) {
                return $this->fail([
                    'error' => 'Validation Error',
                    'errors' => $errors,
                ], 422);
            }

            // TODO: Save to database
            $customer = [
                'id' => rand(100, 999),
                'name' => $data['name'],
                'code' => $data['code'] ?? 'CUST' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
                'contact' => $data['contact'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'],
                'status' => 'active',
                'created_at' => date('c'),
            ];

            return $this->respond([
                'data' => $customer,
                'message' => '客戶建立成功',
            ], 201);
        } catch (\Exception $e) {
            return $this->failServerError('建立客戶失敗: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/v1/customers/{id}
     *
     * Get customer details
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function show(int $id): ResponseInterface
    {
        try {
            // TODO: Fetch from database
            $customer = [
                'id' => $id,
                'name' => 'TSMC',
                'code' => 'CUST001',
                'contact' => 'Mr. Chang',
                'phone' => '0912-345-678',
                'email' => 'contact@tsmc.com',
                'status' => 'active',
                'created_at' => '2023-10-01T00:00:00Z',
            ];

            if (!$customer) {
                return $this->failNotFound('客戶不存在');
            }

            return $this->respond([
                'data' => $customer,
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得客戶詳情失敗: ' . $e->getMessage());
        }
    }

    /**
     * PUT /api/v1/customers/{id}
     *
     * Update customer
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function update(int $id): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            // TODO: Update in database
            $customer = [
                'id' => $id,
                'name' => $data['name'] ?? 'TSMC',
                'code' => 'CUST001',
                'contact' => $data['contact'] ?? 'Mr. Chang',
                'phone' => $data['phone'] ?? '0912-345-678',
                'email' => $data['email'] ?? 'contact@tsmc.com',
                'status' => $data['status'] ?? 'active',
                'created_at' => '2023-10-01T00:00:00Z',
            ];

            return $this->respond([
                'data' => $customer,
                'message' => '客戶更新成功',
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('更新客戶失敗: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /api/v1/customers/{id}
     *
     * Delete customer
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function delete(int $id): ResponseInterface
    {
        try {
            // TODO: Delete from database
            return $this->respond([
                'message' => '客戶刪除成功',
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('刪除客戶失敗: ' . $e->getMessage());
        }
    }
}
