<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['code', 'name', 'contact', 'phone', 'email', 'status', 'created_by', 'updated_by'];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'code'    => 'required|is_unique[customers.code,id,{id}]|min_length[2]|max_length[50]',
        'name'    => 'required|min_length[2]|max_length[255]',
        'contact' => 'permit_empty|max_length[100]',
        'phone'   => 'permit_empty|max_length[20]',
        'email'   => 'permit_empty|valid_email|max_length[255]',
        'status'  => 'in_list[active,inactive]',
    ];

    protected $validationMessages = [
        'code' => [
            'required' => '客戶代碼為必填',
            'is_unique' => '客戶代碼已存在',
        ],
        'name' => [
            'required' => '客戶名稱為必填',
        ],
    ];

    /**
     * Get paginated customers
     *
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @return array
     */
    public function getPaginated(int $page = 1, int $perPage = 20, array $filters = [])
    {
        $builder = $this->builder();

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('code', $search)
                ->orLike('contact', $search)
                ->orLike('email', $search)
                ->groupEnd();
        }

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        // Count total
        $total = $builder->countAllResults(false);

        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $data = $builder->limit($perPage, $offset)
            ->orderBy('created_at', 'DESC')
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
     * Get customer with orders count
     *
     * @param int $id
     * @return array|null
     */
    public function getWithOrdersCount(int $id)
    {
        $customer = $this->find($id);
        if (!$customer) {
            return null;
        }

        $db = \Config\Database::connect();
        $ordersCount = $db->table('orders')
            ->where('customer_id', $id)
            ->where('deleted_at', null)
            ->countAllResults();

        $customer['orders_count'] = $ordersCount;
        return $customer;
    }
}
