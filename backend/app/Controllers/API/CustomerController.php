<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Services\CustomerService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Customer Controller
 *
 * Handles REST API endpoints for customer management
 * Endpoints: GET/POST /customers, GET/PUT/DELETE /customers/{id}
 */
class CustomerController extends BaseController
{
    protected $customerService;

    public function __construct()
    {
        $this->customerService = new CustomerService();
    }

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
            $filters = [
                'page'     => (int)($this->request->getGet('page') ?? 1),
                'per_page' => (int)($this->request->getGet('per_page') ?? 20),
                'search'   => $this->request->getGet('search'),
                'status'   => $this->request->getGet('status'),
            ];

            // Validate per_page
            if ($filters['per_page'] > 100) {
                $filters['per_page'] = 100;
            }

            $result = $this->customerService->getCustomers($filters);

            return $this->respond($result, 200);
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
            if (empty($data['name']) || empty($data['code'])) {
                return $this->fail([
                    'error' => 'Validation Error',
                    'errors' => [
                        'name' => empty($data['name']) ? ['客戶名稱為必填'] : [],
                        'code' => empty($data['code']) ? ['客戶代碼為必填'] : [],
                    ],
                ], 422);
            }

            $data['created_by'] = auth()->user()->id ?? 1;
            $customerId = $this->customerService->createCustomer($data);

            $customer = $this->customerService->getCustomer($customerId);

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
     * @param int|null $id
     * @return ResponseInterface
     */
    public function show($id = null): ResponseInterface
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('客戶 ID 為必填');
            }

            $customer = $this->customerService->getCustomer((int)$id);

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
     * @param int|null $id
     * @return ResponseInterface
     */
    public function update($id = null): ResponseInterface
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('客戶 ID 為必填');
            }

            $data = $this->request->getJSON(true);

            // Remove fields that shouldn't be updated
            unset($data['id'], $data['created_at'], $data['created_by']);

            $data['updated_by'] = auth()->user()->id ?? 1;

            $this->customerService->updateCustomer((int)$id, $data);
            $customer = $this->customerService->getCustomer((int)$id);

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
     * @param int|null $id
     * @return ResponseInterface
     */
    public function delete($id = null): ResponseInterface
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('客戶 ID 為必填');
            }

            $this->customerService->deleteCustomer((int)$id);

            return $this->respond([
                'message' => '客戶刪除成功',
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('刪除客戶失敗: ' . $e->getMessage());
        }
    }
}

