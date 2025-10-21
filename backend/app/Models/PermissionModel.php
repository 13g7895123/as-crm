<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Permission Model
 *
 * Manages permissions in the RBAC system
 */
class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'name',
        'resource',
        'action',
        'description',
        'created_at',
        'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'name'        => 'required|max_length[100]|is_unique[permissions.name,id,{id}]',
        'resource'    => 'required|max_length[100]',
        'action'      => 'required|max_length[50]|in_list[view,edit,export,assign]',
        'description' => 'permit_empty|max_length[1000]',
    ];

    protected $validationMessages = [
        'name' => [
            'required'  => '權限名稱為必填',
            'is_unique' => '權限名稱已存在',
        ],
        'resource' => [
            'required' => '資源名稱為必填',
        ],
        'action' => [
            'required' => '操作類型為必填',
            'in_list'  => '操作類型必須為 view, edit, export, 或 assign',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get permissions grouped by resource
     *
     * @return array
     */
    public function getGroupedByResource(): array
    {
        $permissions = $this->findAll();
        $grouped = [];

        foreach ($permissions as $permission) {
            $resource = $permission['resource'];

            if (!isset($grouped[$resource])) {
                $grouped[$resource] = [
                    'resource' => $resource,
                    'resource_name' => $this->getResourceDisplayName($resource),
                    'permissions' => []
                ];
            }

            $grouped[$resource]['permissions'][] = $permission;
        }

        return array_values($grouped);
    }

    /**
     * Get permissions with filters
     *
     * @param array $filters
     * @return array
     */
    public function getPermissionsWithFilters(array $filters = []): array
    {
        $builder = $this->builder();

        if (isset($filters['resource'])) {
            $builder->where('resource', $filters['resource']);
        }

        if (isset($filters['action'])) {
            $builder->where('action', $filters['action']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get resource display name
     *
     * @param string $resource
     * @return string
     */
    private function getResourceDisplayName(string $resource): string
    {
        $displayNames = [
            'customer' => '客戶管理',
            'order' => '訂單管理',
            'report' => '報表中心',
            'role' => '角色管理',
            'user_permission' => '使用者權限管理',
        ];

        return $displayNames[$resource] ?? ucfirst($resource);
    }

    /**
     * Check if permission exists
     *
     * @param string $name
     * @return bool
     */
    public function permissionExists(string $name): bool
    {
        return $this->where('name', $name)->countAllResults() > 0;
    }

    /**
     * Get permissions by IDs
     *
     * @param array $ids
     * @return array
     */
    public function getByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->whereIn('id', $ids)->findAll();
    }

    /**
     * Get permissions by resource and action
     *
     * @param string $resource
     * @param string $action
     * @return array|null
     */
    public function getByResourceAndAction(string $resource, string $action): ?array
    {
        return $this->where([
            'resource' => $resource,
            'action'   => $action,
        ])->first();
    }
}
