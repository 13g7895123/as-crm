<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * RolePermission Model
 *
 * Manages the many-to-many relationship between roles and permissions
 */
class RolePermissionModel extends Model
{
    protected $table            = 'role_permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'role_id',
        'permission_id',
        'created_at'
    ];

    // Dates
    protected $useTimestamps = false; // Manual timestamp management
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';

    // Validation
    protected $validationRules = [
        'role_id'       => 'required|integer',
        'permission_id' => 'required|integer',
    ];

    protected $validationMessages = [
        'role_id' => [
            'required' => '角色 ID 為必填',
            'integer'  => '角色 ID 必須為整數',
        ],
        'permission_id' => [
            'required' => '權限 ID 為必填',
            'integer'  => '權限 ID 必須為整數',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['setCreatedAt'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Set created_at timestamp
     *
     * @param array $data
     * @return array
     */
    protected function setCreatedAt(array $data): array
    {
        if (!isset($data['data']['created_at'])) {
            $data['data']['created_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Get permissions for a role
     *
     * @param int $roleId
     * @return array
     */
    public function getPermissionsByRole(int $roleId): array
    {
        return $this->select('permissions.*')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->findAll();
    }

    /**
     * Get roles for a permission
     *
     * @param int $permissionId
     * @return array
     */
    public function getRolesByPermission(int $permissionId): array
    {
        return $this->select('roles.*')
            ->join('roles', 'roles.id = role_permissions.role_id')
            ->where('role_permissions.permission_id', $permissionId)
            ->findAll();
    }

    /**
     * Assign permissions to role (replace existing)
     *
     * @param int $roleId
     * @param array $permissionIds
     * @return bool
     */
    public function assignPermissionsToRole(int $roleId, array $permissionIds): bool
    {
        // Start transaction
        $this->db->transStart();

        // Remove existing permissions
        $this->where('role_id', $roleId)->delete();

        // Add new permissions
        if (!empty($permissionIds)) {
            $data = [];
            foreach ($permissionIds as $permissionId) {
                $data[] = [
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                    'created_at'    => date('Y-m-d H:i:s'),
                ];
            }

            $this->insertBatch($data);
        }

        // Complete transaction
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Add permission to role
     *
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function addPermissionToRole(int $roleId, int $permissionId): bool
    {
        // Check if already exists
        $exists = $this->where([
            'role_id'       => $roleId,
            'permission_id' => $permissionId,
        ])->first();

        if ($exists) {
            return true; // Already assigned
        }

        return (bool) $this->insert([
            'role_id'       => $roleId,
            'permission_id' => $permissionId,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Remove permission from role
     *
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function removePermissionFromRole(int $roleId, int $permissionId): bool
    {
        return $this->where([
            'role_id'       => $roleId,
            'permission_id' => $permissionId,
        ])->delete();
    }

    /**
     * Check if role has permission
     *
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function roleHasPermission(int $roleId, int $permissionId): bool
    {
        return $this->where([
            'role_id'       => $roleId,
            'permission_id' => $permissionId,
        ])->countAllResults() > 0;
    }

    /**
     * Remove all permissions from role
     *
     * @param int $roleId
     * @return bool
     */
    public function removeAllPermissionsFromRole(int $roleId): bool
    {
        return $this->where('role_id', $roleId)->delete();
    }
}
