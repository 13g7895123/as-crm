<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table            = 'order_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['order_id', 'product_name', 'product_code', 'quantity', 'unit_price', 'total_price'];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';

    /**
     * Create order items
     *
     * @param int $orderId
     * @param array $items
     * @return bool
     */
    public function createItems(int $orderId, array $items): bool
    {
        $itemsToInsert = [];
        foreach ($items as $item) {
            $itemsToInsert[] = [
                'order_id'    => $orderId,
                'product_name' => $item['product_name'] ?? '',
                'product_code' => $item['product_code'] ?? null,
                'quantity'    => $item['quantity'] ?? 1,
                'unit_price'  => $item['unit_price'] ?? 0,
                'total_price' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
                'created_at'  => date('Y-m-d H:i:s'),
            ];
        }

        return $this->insertBatch($itemsToInsert);
    }

    /**
     * Get items by order
     *
     * @param int $orderId
     * @return array
     */
    public function getByOrder(int $orderId)
    {
        return $this->where('order_id', $orderId)
            ->findAll();
    }

    /**
     * Delete order items
     *
     * @param int $orderId
     * @return bool
     */
    public function deleteByOrder(int $orderId): bool
    {
        return $this->where('order_id', $orderId)
            ->delete();
    }
}
