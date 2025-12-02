<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Services\OrderService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Order Controller
 *
 * Handles REST API endpoints for order management
 * Endpoints: GET/POST /orders, GET/PUT/DELETE /orders/{id}
 */
class OrderController extends BaseController
{
    protected $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

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
            $filters = [
                'page'         => (int)($this->request->getGet('page') ?? 1),
                'per_page'     => (int)($this->request->getGet('per_page') ?? 20),
                'customer_id'  => $this->request->getGet('customer_id'),
                'status'       => $this->request->getGet('status'),
                'search'       => $this->request->getGet('search'),
            ];

            // Validate per_page
            if ($filters['per_page'] > 100) {
                $filters['per_page'] = 100;
            }

            $result = $this->orderService->getOrders($filters);

            return $this->respond($result, 200);
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
            if (empty($data['customer_id']) || empty($data['amount']) || empty($data['order_date'])) {
                return $this->fail([
                    'error' => 'Validation Error',
                    'errors' => [
                        'customer_id' => empty($data['customer_id']) ? ['客戶 ID 為必填'] : [],
                        'amount'      => empty($data['amount']) ? ['訂單金額為必填'] : [],
                        'order_date'  => empty($data['order_date']) ? ['訂單日期為必填'] : [],
                    ],
                ], 422);
            }

            // Get user ID from auth context (set by AuthFilter)
            $auth = $this->request->fetchGlobal('auth');
            $data['created_by'] = $auth['user_id'] ?? 1;
            
            $orderId = $this->orderService->createOrder($data);

            $order = $this->orderService->getOrder($orderId);

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
     * @param int|null $id
     * @return ResponseInterface
     */
    public function show($id = null): ResponseInterface
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('訂單 ID 為必填');
            }

            $order = $this->orderService->getOrder((int)$id);

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
     * @param int|null $id
     * @return ResponseInterface
     */
    public function update($id = null): ResponseInterface
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('訂單 ID 為必填');
            }

            $data = $this->request->getJSON(true);

            // Remove fields that shouldn't be updated
            unset($data['id'], $data['order_no'], $data['created_at'], $data['created_by']);

            // Get user ID from auth context (set by AuthFilter)
            $auth = $this->request->fetchGlobal('auth');
            $data['updated_by'] = $auth['user_id'] ?? 1;

            $this->orderService->updateOrder((int)$id, $data);
            $order = $this->orderService->getOrder((int)$id);

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
     * @param int|null $id
     * @return ResponseInterface
     */
    public function delete($id = null): ResponseInterface
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('訂單 ID 為必填');
            }

            $this->orderService->deleteOrder((int)$id);

            return $this->respond([
                'message' => '訂單刪除成功',
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('刪除訂單失敗: ' . $e->getMessage());
        }
    }
}
