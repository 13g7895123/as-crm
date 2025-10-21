<?php

namespace App\Services;

use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;
use App\Models\ConditionRuleModel;

/**
 * Role Service
 *
 * Handles business logic for role management including
 * CRUD operations, permission assignments, and condition rules
 */
class RoleService
{
    protected RoleModel $roleModel;
    protected PermissionModel $permissionModel;
    protected RolePermissionModel $rolePermissionModel;
    protected ConditionRuleModel $conditionRuleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->permissionModel = new PermissionModel();
        $this->rolePermissionModel = new RolePermissionModel();
        $this->conditionRuleModel = new ConditionRuleModel();
    }

    /**
     * Create a new role
     *
     * @param array $data
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function createRole(array $data): array
    {
        // Validate required fields
        if (empty($data['name']) || empty($data['display_name'])) {
            throw new \InvalidArgumentException('角色名稱和顯示名稱為必填');
        }

        // Validate name format
        if (!preg_match('/^[a-z0-9_]{3,100}$/', $data['name'])) {
            throw new \InvalidArgumentException('角色名稱只能包含小寫字母、數字和底線');
        }

        // Check name uniqueness
        if (!$this->roleModel->isNameUnique($data['name'])) {
            throw new \RuntimeException('角色名稱已存在');
        }

        // Extract permissions and condition rules before inserting role
        $permissions = $data['permissions'] ?? [];
        $conditionRules = $data['condition_rules'] ?? [];
        unset($data['permissions'], $data['condition_rules']);

        // Set defaults
        $data['is_system'] = $data['is_system'] ?? 0;
        $data['level'] = $data['level'] ?? 0;

        // Start transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Insert role
            $roleId = $this->roleModel->insert($data);

            if (!$roleId) {
                throw new \RuntimeException('建立角色失敗: ' . implode(', ', $this->roleModel->errors()));
            }

            // Assign permissions
            if (!empty($permissions)) {
                $this->validatePermissions($permissions);
                $this->rolePermissionModel->assignPermissionsToRole($roleId, $permissions);
            }

            // Create condition rules
            if (!empty($conditionRules)) {
                $this->createConditionRules($roleId, $conditionRules);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('建立角色失敗');
            }

            // Return complete role data
            return $this->getRoleById($roleId);
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Update an existing role
     *
     * @param int $roleId
     * @param array $data
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function updateRole(int $roleId, array $data): array
    {
        $role = $this->roleModel->find($roleId);

        if (!$role) {
            throw new \RuntimeException('角色不存在');
        }

        // System roles can only update certain fields
        if ($role['is_system']) {
            $allowedFields = ['description'];
            $data = array_intersect_key($data, array_flip($allowedFields));
        }

        // Extract permissions and condition rules
        $permissions = $data['permissions'] ?? null;
        $conditionRules = $data['condition_rules'] ?? null;
        unset($data['permissions'], $data['condition_rules']);

        // Remove name from update data (name cannot be changed)
        unset($data['name']);

        // Start transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Update basic role data
            if (!empty($data)) {
                $this->roleModel->update($roleId, $data);
            }

            // Update permissions if provided
            if ($permissions !== null) {
                if (!$role['is_system']) {
                    $this->validatePermissions($permissions);
                    $this->rolePermissionModel->assignPermissionsToRole($roleId, $permissions);
                }
            }

            // Update condition rules if provided
            if ($conditionRules !== null) {
                $this->updateConditionRules($roleId, $conditionRules);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('更新角色失敗');
            }

            return $this->getRoleById($roleId);
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Delete a role
     *
     * @param int $roleId
     * @return bool
     * @throws \RuntimeException
     */
    public function deleteRole(int $roleId): bool
    {
        $role = $this->roleModel->find($roleId);

        if (!$role) {
            throw new \RuntimeException('角色不存在');
        }

        if ($role['is_system']) {
            throw new \RuntimeException('無法刪除系統角色');
        }

        // Check if role is assigned to any users
        $db = \Config\Database::connect();
        $assignmentCount = $db->table('role_assignments')
            ->where('role_id', $roleId)
            ->countAllResults();

        if ($assignmentCount > 0) {
            throw new \RuntimeException('此角色正在使用中,無法刪除');
        }

        return $this->roleModel->delete($roleId);
    }

    /**
     * Get role by ID with full details
     *
     * @param int $roleId
     * @return array
     * @throws \RuntimeException
     */
    public function getRoleById(int $roleId): array
    {
        $role = $this->roleModel->find($roleId);

        if (!$role) {
            throw new \RuntimeException('角色不存在');
        }

        // Get permissions
        $role['permissions'] = $this->rolePermissionModel->getPermissionsByRole($roleId);

        // Get condition rules
        $role['condition_rules'] = $this->conditionRuleModel->getRulesByPermission($roleId);

        return $role;
    }

    /**
     * Get roles with filters and pagination
     *
     * @param array $filters
     * @return array
     */
    public function getRoles(array $filters = []): array
    {
        return $this->roleModel->getRolesWithFilters($filters);
    }

    /**
     * Get role permissions
     *
     * @param int $roleId
     * @param bool $includeInherited
     * @return array
     * @throws \RuntimeException
     */
    public function getRolePermissions(int $roleId, bool $includeInherited = true): array
    {
        $role = $this->roleModel->find($roleId);

        if (!$role) {
            throw new \RuntimeException('角色不存在');
        }

        return $this->roleModel->getRolePermissions($roleId, $includeInherited);
    }

    /**
     * Validate permissions exist
     *
     * @param array $permissionIds
     * @throws \InvalidArgumentException
     */
    private function validatePermissions(array $permissionIds): void
    {
        if (empty($permissionIds)) {
            return;
        }

        $existingPermissions = $this->permissionModel->getByIds($permissionIds);

        if (count($existingPermissions) !== count($permissionIds)) {
            throw new \InvalidArgumentException('部分權限不存在');
        }
    }

    /**
     * Create condition rules for a role
     *
     * @param int $roleId
     * @param array $rules
     * @throws \InvalidArgumentException
     */
    private function createConditionRules(int $roleId, array $rules): void
    {
        foreach ($rules as $rule) {
            // Validate condition type
            if (!in_array($rule['condition_type'], ConditionRuleModel::CONDITION_TYPES)) {
                throw new \InvalidArgumentException('無效的條件類型: ' . $rule['condition_type']);
            }

            // Validate operator
            if (!in_array($rule['operator'], ConditionRuleModel::OPERATORS)) {
                throw new \InvalidArgumentException('無效的運算子: ' . $rule['operator']);
            }

            $this->conditionRuleModel->insert([
                'permission_id'  => $rule['permission_id'] ?? null,
                'condition_type' => $rule['condition_type'],
                'field_name'     => $rule['field_name'] ?? $rule['condition_type'],
                'operator'       => $rule['operator'],
                'value'          => json_encode($rule['condition_value'] ?? $rule['value'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * Update condition rules for a role
     *
     * @param int $roleId
     * @param array $rules
     */
    private function updateConditionRules(int $roleId, array $rules): void
    {
        // Delete existing rules
        $db = \Config\Database::connect();
        $db->table('condition_rules')
            ->where('permission_id IN (SELECT id FROM role_permissions WHERE role_id = ' . $roleId . ')')
            ->orWhere('permission_id', null)
            ->delete();

        // Create new rules
        if (!empty($rules)) {
            $this->createConditionRules($roleId, $rules);
        }
    }
}
