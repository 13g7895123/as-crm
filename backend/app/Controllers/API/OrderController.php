<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Order Controller
 *
 * Handles REST API endpoints for order management
 * Endpoints: GET/POST /orders, GET/PUT/DELETE /orders/{id}
 */
class OrderController extends BaseController
{
    /**
     * GET /api/v1/orders
     *
     * Get all orders with pagination and filtering
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        try {
            $page = (int)($this->request->getGet('page') ?? 1);
            $perPage = (int)($this->request->getGet('per_page') ?? 20);
            $customerId = $this->request->getGet('customer_id');
            $status = $this->request->getGet('status');

            // Validate per_page
            if ($perPage > 100) {
                $perPage = 100;
            }

            // Mock data
            $orders = [
                [
                    'id' => 1,
                    'order_no' => 'ORD-20231001-001',
                    'customer_id' => 1,
                    'customer_name' => 'TSMC',
                    'amount' => 1500000.00,
                    'order_date' => '2023-10-01',
                    'status' => 'completed',
                    'created_at' => '2023-10-01T00:00:00Z',
                ],
                [
                    'id' => 2,
                    'order_no' => 'ORD-20231002-002',
                    'customer_id' => 2,
                    'customer_name' => 'MediaTek',
                    'amount' => 800000.00,
                    'order_date' => '2023-10-02',
                    'status' => 'processing',
                    'created_at' => '2023-10-02T00:00:00Z',
                ],
                [
                    'id' => 3,
                    'order_no' => 'ORD-20231003-003',
                    'customer_id' => 3,
                    'customer_name' => 'Foxconn',
                    'amount' => 2200000.00,
                    'order_date' => '2023-10-03',
                    'status' => 'pending',
                    'created_at' => '2023-10-03T00:00:00Z',
                ],
            ];

            // Filter by customer_id
            if ($customerId) {
                $orders = array_filter($orders, function ($order) use ($customerId) {
                    return $order['customer_id'] == $customerId;
                });
            }

            // Filter by status
            if ($status) {
                $orders = array_filter($orders, function ($order) use ($status) {
                    return $order['status'] === $status;
                });
            }

            // Calculate pagination
            $total = count($orders);
            $totalPages = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;
            $paginatedOrders = array_slice($orders, $offset, $perPage);

            return $this->respond([
                'data' => array_values($paginatedOrders),
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得訂單清單失敗: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/v1/orders
     *
     * Create a new order
     *
     * @return ResponseInterface
     */
    public function create(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required = ['customer_id', 'amount', 'order_date'];
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
            $order = [
                'id' => rand(100, 999),
                'order_no' => 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'customer_id' => $data['customer_id'],
                'customer_name' => 'Customer Name', // TODO: Fetch from database
                'amount' => $data['amount'],
                'order_date' => $data['order_date'],
                'status' => 'pending',
                'created_at' => date('c'),
            ];

            return $this->respond([
                'data' => $order,
                'message' => '訂單建立成功',
            ], 201);
        } catch (\Exception $e) {
            return $this->failServerError('建立訂單失敗: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/v1/orders/{id}
     *
     * Get order details
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function show(int $id): ResponseInterface
    {
        try {
            // TODO: Fetch from database
            $order = [
                'id' => $id,
                'order_no' => 'ORD-20231001-001',
                'customer_id' => 1,
                'customer_name' => 'TSMC',
                'amount' => 1500000.00,
                'order_date' => '2023-10-01',
                'status' => 'completed',
                'created_at' => '2023-10-01T00:00:00Z',
            ];

            if (!$order) {
                return $this->failNotFound('訂單不存在');
            }

            return $this->respond([
                'data' => $order,
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得訂單詳情失敗: ' . $e->getMessage());
        }
    }

    /**
     * PUT /api/v1/orders/{id}
     *
     * Update order
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function update(int $id): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            // TODO: Update in database
            $order = [
                'id' => $id,
                'order_no' => 'ORD-20231001-001',
                'customer_id' => 1,
                'customer_name' => 'TSMC',
                'amount' => $data['amount'] ?? 1500000.00,
                'order_date' => '2023-10-01',
                'status' => $data['status'] ?? 'completed',
                'created_at' => '2023-10-01T00:00:00Z',
            ];

            return $this->respond([
                'data' => $order,
                'message' => '訂單更新成功',
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('更新訂單失敗: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /api/v1/orders/{id}
     *
     * Delete order
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function delete(int $id): ResponseInterface
    {
        try {
            // TODO: Delete from database
            return $this->respond([
                'message' => '訂單刪除成功',
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('刪除訂單失敗: ' . $e->getMessage());
        }
    }
}
