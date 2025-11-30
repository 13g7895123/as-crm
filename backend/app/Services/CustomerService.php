<?php

namespace App\Services;

use App\Models\CustomerModel;

class CustomerService
{
    protected $customerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
    }

    /**
     * Get customers with pagination
     *
     * @param array $filters
     * @return array
     */
    public function getCustomers(array $filters = [])
    {
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;

        return $this->customerModel->getPaginated($page, $perPage, $filters);
    }

    /**
     * Get customer by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getCustomer(int $id)
    {
        return $this->customerModel->getWithOrdersCount($id);
    }

    /**
     * Create customer
     *
     * @param array $data
     * @return int|false
     */
    public function createCustomer(array $data)
    {
        if (!$this->customerModel->validate($data)) {
            throw new \Exception('Validation failed: ' . implode(', ', $this->customerModel->errors()));
        }

        return $this->customerModel->insert($data);
    }

    /**
     * Update customer
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateCustomer(int $id, array $data)
    {
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        if (!$this->customerModel->validate($data)) {
            throw new \Exception('Validation failed: ' . implode(', ', $this->customerModel->errors()));
        }

        return $this->customerModel->update($id, $data);
    }

    /**
     * Delete customer (soft delete)
     *
     * @param int $id
     * @return bool
     */
    public function deleteCustomer(int $id)
    {
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        return $this->customerModel->delete($id);
    }

    /**
     * Get customer statistics
     *
     * @return array
     */
    public function getStatistics()
    {
        $total = $this->customerModel->countAllResults();
        $active = $this->customerModel->where('status', 'active')->countAllResults(false);
        $inactive = $this->customerModel->where('status', 'inactive')->countAllResults(false);

        return [
            'total'    => $total,
            'active'   => $active,
            'inactive' => $inactive,
        ];
    }
}
