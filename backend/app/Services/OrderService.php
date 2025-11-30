<?php

namespace App\Services;

use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\CustomerModel;

class OrderService
{
    protected $orderModel;
    protected $orderItemModel;
    protected $customerModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->orderItemModel = new OrderItemModel();
        $this->customerModel = new CustomerModel();
    }

    /**
     * Get orders with pagination
     *
     * @param array $filters
     * @return array
     */
    public function getOrders(array $filters = [])
    {
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;

        return $this->orderModel->getPaginated($page, $perPage, $filters);
    }

    /**
     * Get order by ID with details
     *
     * @param int $id
     * @return array|null
     */
    public function getOrder(int $id)
    {
        return $this->orderModel->getWithDetails($id);
    }

    /**
     * Create order with items
     *
     * @param array $data
     * @return int|false
     */
    public function createOrder(array $data)
    {
        // Validate customer exists
        if (!$this->customerModel->find($data['customer_id'])) {
            throw new \Exception('Customer not found');
        }

        // Prepare order data
        $orderData = [
            'order_no'   => $data['order_no'] ?? $this->generateOrderNo(),
            'customer_id' => $data['customer_id'],
            'amount'     => $data['amount'] ?? 0,
            'order_date' => $data['order_date'] ?? date('Y-m-d'),
            'status'     => $data['status'] ?? 'pending',
            'description' => $data['description'] ?? null,
            'created_by' => $data['created_by'] ?? 1,
        ];

        if (!$this->orderModel->validate($orderData)) {
            throw new \Exception('Validation failed: ' . implode(', ', $this->orderModel->errors()));
        }

        // Insert order
        $orderId = $this->orderModel->insert($orderData);

        // Insert order items if provided
        if (!empty($data['items']) && is_array($data['items'])) {
            $this->orderItemModel->createItems($orderId, $data['items']);
        }

        return $orderId;
    }

    /**
     * Update order
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateOrder(int $id, array $data)
    {
        $order = $this->orderModel->find($id);
        if (!$order) {
            throw new \Exception('Order not found');
        }

        // Update order items if provided
        if (!empty($data['items']) && is_array($data['items'])) {
            // Delete existing items
            $this->orderItemModel->deleteByOrder($id);
            // Create new items
            $this->orderItemModel->createItems($id, $data['items']);
            unset($data['items']);
        }

        if (!empty($data)) {
            if (!$this->orderModel->validate($data)) {
                throw new \Exception('Validation failed: ' . implode(', ', $this->orderModel->errors()));
            }

            return $this->orderModel->update($id, $data);
        }

        return true;
    }

    /**
     * Delete order (soft delete)
     *
     * @param int $id
     * @return bool
     */
    public function deleteOrder(int $id)
    {
        $order = $this->orderModel->find($id);
        if (!$order) {
            throw new \Exception('Order not found');
        }

        return $this->orderModel->delete($id);
    }

    /**
     * Generate unique order number
     *
     * @return string
     */
    private function generateOrderNo(): string
    {
        $date = date('Ymd');
        $count = $this->orderModel->where('order_date', date('Y-m-d'))->countAllResults();
        return 'ORD-' . $date . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get orders by customer
     *
     * @param int $customerId
     * @return array
     */
    public function getOrdersByCustomer(int $customerId)
    {
        return $this->orderModel->getByCustomer($customerId);
    }

    /**
     * Get sales summary
     *
     * @return array
     */
    public function getSalesSummary()
    {
        $db = \Config\Database::connect();
        
        // Total sales
        $totalSales = $db->table('orders')
            ->selectSum('amount', 'total')
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        // Sales by status
        $salesByStatus = $this->orderModel->getSalesSummary();

        // Total orders
        $totalOrders = $this->orderModel->countAllResults();

        return [
            'total_sales'      => $totalSales['total'] ?? 0,
            'total_orders'     => $totalOrders,
            'sales_by_status'  => $salesByStatus,
        ];
    }

    /**
     * Get sales trends (by date)
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string $groupBy day|week|month
     * @return array
     */
    public function getSalesTrends(?string $startDate = null, ?string $endDate = null, string $groupBy = 'month')
    {
        $db = \Config\Database::connect();
        
        $builder = $db->table('orders')
            ->select("DATE_FORMAT(order_date, '%Y-%m-01') as period, SUM(amount) as total_amount, COUNT(*) as order_count")
            ->where('deleted_at', null);

        if ($startDate) {
            $builder->where('order_date >=', $startDate);
        }
        if ($endDate) {
            $builder->where('order_date <=', $endDate);
        }

        $builder->groupBy("DATE_FORMAT(order_date, '%Y-%m-01')")
            ->orderBy('period', 'ASC');

        return $builder->get()->getResultArray();
    }
}
