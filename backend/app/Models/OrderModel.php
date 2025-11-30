<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table            = 'orders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['order_no', 'customer_id', 'amount', 'order_date', 'status', 'description', 'created_by', 'updated_by'];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'order_no'    => 'required|is_unique[orders.order_no,id,{id}]|min_length[5]|max_length[50]',
        'customer_id' => 'required|is_not_unique[customers.id]',
        'amount'      => 'required|numeric|greater_than[0]',
        'order_date'  => 'required|valid_date[Y-m-d]',
        'status'      => 'in_list[pending,processing,completed,cancelled]',
    ];

    protected $validationMessages = [
        'order_no' => [
            'required' => '訂單編號為必填',
            'is_unique' => '訂單編號已存在',
        ],
        'customer_id' => [
            'required' => '客戶 ID 為必填',
        ],
        'amount' => [
            'required' => '訂單金額為必填',
        ],
    ];

    /**
     * Get paginated orders with customer info
     *
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @return array
     */
    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = [])
    {
        $db = \Config\Database::connect();
        $builder = $db->table('orders')
            ->select('orders.*, customers.name as customer_name, customers.code as customer_code')
            ->join('customers', 'orders.customer_id = customers.id', 'left')
            ->where('orders.deleted_at', null);

        // Apply filters
        if (!empty($filters['customer_id'])) {
            $builder->where('orders.customer_id', $filters['customer_id']);
        }

        if (!empty($filters['status'])) {
            $builder->where('orders.status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('orders.order_no', $search)
                ->orLike('customers.name', $search)
                ->groupEnd();
        }

        // Count total
        $total = $builder->countAllResults(false);

        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $data = $builder->limit($perPage, $offset)
            ->orderBy('orders.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'total_pages'  => ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Get order with items and customer
     *
     * @param int $id
     * @return array|null
     */
    public function getWithDetails(int $id)
    {
        $db = \Config\Database::connect();
        
        // Get order with customer info
        $order = $db->table('orders')
            ->select('orders.*, customers.name as customer_name, customers.code as customer_code, customers.email as customer_email')
            ->join('customers', 'orders.customer_id = customers.id', 'left')
            ->where('orders.id', $id)
            ->where('orders.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$order) {
            return null;
        }

        // Get order items
        $items = $db->table('order_items')
            ->where('order_id', $id)
            ->get()
            ->getResultArray();

        $order['items'] = $items;
        return $order;
    }

    /**
     * Get orders by customer
     *
     * @param int $customerId
     * @return array
     */
    public function getByCustomer(int $customerId)
    {
        return $this->where('customer_id', $customerId)
            ->where('deleted_at', null)
            ->findAll();
    }

    /**
     * Get sales summary by status
     *
     * @return array
     */
    public function getSalesSummary()
    {
        $db = \Config\Database::connect();
        return $db->table('orders')
            ->select('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->where('deleted_at', null)
            ->groupBy('status')
            ->get()
            ->getResultArray();
    }
}
