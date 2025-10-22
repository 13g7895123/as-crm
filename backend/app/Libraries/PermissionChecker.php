<?php

namespace App\Libraries;

use App\Models\RoleAssignmentModel;
use App\Models\RolePermissionModel;
use App\Models\PermissionModel;
use App\Models\ConditionRuleModel;

/**
 * Permission Checker Library
 *
 * Utility class for checking user permissions with condition rule validation.
 * Supports complex permission checks including:
 * - Multiple role permission aggregation
 * - Condition-based access control (department, region, customer group, etc.)
 * - Time-based validity checks
 *
 * @package App\Libraries
 */
class PermissionChecker
{
    protected RoleAssignmentModel $roleAssignmentModel;
    protected RolePermissionModel $rolePermissionModel;
    protected PermissionModel $permissionModel;
    protected ConditionRuleModel $conditionRuleModel;

    public function __construct()
    {
        $this->roleAssignmentModel = new RoleAssignmentModel();
        $this->rolePermissionModel = new RolePermissionModel();
        $this->permissionModel = new PermissionModel();
        $this->conditionRuleModel = new ConditionRuleModel();
    }

    /**
     * Check if user has a specific permission
     *
     * @param int $userId User ID
     * @param string $module Module name (e.g., 'customer', 'order')
     * @param string $action Action name (e.g., 'view', 'edit')
     * @param array $context Context data for condition evaluation (e.g., ['department' => '業務部'])
     * @return bool
     */
    public function hasPermission(int $userId, string $module, string $action, array $context = []): bool
    {
        // Get user's active role assignments
        $roleAssignments = $this->roleAssignmentModel->getUserRoles($userId, false);

        if (empty($roleAssignments)) {
            return false;
        }

        // Get the permission ID
        $permission = $this->permissionModel
            ->where('module', $module)
            ->where('action', $action)
            ->first();

        if (!$permission) {
            return false;
        }

        // Check each role for this permission
        foreach ($roleAssignments as $assignment) {
            // Check if role has this permission
            $rolePermission = $this->rolePermissionModel
                ->where('role_id', $assignment['role_id'])
                ->where('permission_id', $permission['id'])
                ->first();

            if (!$rolePermission) {
                continue;
            }

            // Check condition rules for this role-permission combination
            $hasAccess = $this->checkConditionRules(
                $assignment['role_id'],
                $permission['id'],
                $context
            );

            if ($hasAccess) {
                return true; // Maximum permission principle: grant if any role allows
            }
        }

        return false;
    }

    /**
     * Check condition rules for a role-permission combination
     *
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @param array $context Context data
     * @return bool True if all conditions pass or no conditions exist
     */
    protected function checkConditionRules(int $roleId, int $permissionId, array $context): bool
    {
        // Get condition rules for this role-permission
        $rules = $this->conditionRuleModel
            ->where('role_id', $roleId)
            ->groupStart()
                ->where('permission_id', $permissionId)
                ->orWhere('permission_id IS NULL') // Global rules for entire role
            ->groupEnd()
            ->findAll();

        if (empty($rules)) {
            return true; // No conditions = access granted
        }

        // All conditions must pass (AND logic)
        foreach ($rules as $rule) {
            if (!$this->evaluateCondition($rule, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition rule
     *
     * @param array $rule Condition rule data
     * @param array $context Context data
     * @return bool
     */
    protected function evaluateCondition(array $rule, array $context): bool
    {
        $conditionType = $rule['condition_type'];
        $operator = $rule['operator'];
        $conditionValue = is_string($rule['condition_value'])
            ? json_decode($rule['condition_value'], true)
            : $rule['condition_value'];

        // Get the actual value from context
        $actualValue = $context[$conditionType] ?? null;

        if ($actualValue === null) {
            // Context doesn't contain this attribute
            // Depending on business logic, you might want to deny or allow
            // For now, we'll deny access if required context is missing
            return false;
        }

        // Evaluate based on operator
        switch ($operator) {
            case 'equals':
                return $this->evaluateEquals($actualValue, $conditionValue);

            case 'not_equals':
                return !$this->evaluateEquals($actualValue, $conditionValue);

            case 'in':
                return $this->evaluateIn($actualValue, $conditionValue);

            case 'not_in':
                return !$this->evaluateIn($actualValue, $conditionValue);

            case 'greater_than':
                return $this->evaluateGreaterThan($actualValue, $conditionValue);

            case 'less_than':
                return $this->evaluateLessThan($actualValue, $conditionValue);

            case 'between':
                return $this->evaluateBetween($actualValue, $conditionValue);

            default:
                // Unknown operator - deny access for safety
                log_message('error', "Unknown condition operator: {$operator}");
                return false;
        }
    }

    /**
     * Evaluate equals operator
     */
    protected function evaluateEquals($actualValue, $conditionValue): bool
    {
        if (is_array($conditionValue) && isset($conditionValue['value'])) {
            return $actualValue == $conditionValue['value'];
        }
        return $actualValue == $conditionValue;
    }

    /**
     * Evaluate 'in' operator
     */
    protected function evaluateIn($actualValue, $conditionValue): bool
    {
        $values = $conditionValue['values'] ?? $conditionValue;

        if (!is_array($values)) {
            return false;
        }

        return in_array($actualValue, $values);
    }

    /**
     * Evaluate greater_than operator
     */
    protected function evaluateGreaterThan($actualValue, $conditionValue): bool
    {
        $threshold = $conditionValue['value'] ?? $conditionValue;
        return $actualValue > $threshold;
    }

    /**
     * Evaluate less_than operator
     */
    protected function evaluateLessThan($actualValue, $conditionValue): bool
    {
        $threshold = $conditionValue['value'] ?? $conditionValue;
        return $actualValue < $threshold;
    }

    /**
     * Evaluate between operator
     */
    protected function evaluateBetween($actualValue, $conditionValue): bool
    {
        if (!isset($conditionValue['min']) || !isset($conditionValue['max'])) {
            return false;
        }

        return $actualValue >= $conditionValue['min'] && $actualValue <= $conditionValue['max'];
    }

    /**
     * Check if user has any of the specified permissions
     *
     * @param int $userId User ID
     * @param array $permissions Array of [module, action] pairs
     * @param array $context Context data
     * @return bool
     */
    public function hasAnyPermission(int $userId, array $permissions, array $context = []): bool
    {
        foreach ($permissions as $permission) {
            [$module, $action] = is_array($permission) ? $permission : explode(':', $permission);

            if ($this->hasPermission($userId, $module, $action, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the specified permissions
     *
     * @param int $userId User ID
     * @param array $permissions Array of [module, action] pairs
     * @param array $context Context data
     * @return bool
     */
    public function hasAllPermissions(int $userId, array $permissions, array $context = []): bool
    {
        foreach ($permissions as $permission) {
            [$module, $action] = is_array($permission) ? $permission : explode(':', $permission);

            if (!$this->hasPermission($userId, $module, $action, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all permissions for a user
     *
     * @param int $userId User ID
     * @return array Array of permission objects with module and action
     */
    public function getUserPermissions(int $userId): array
    {
        $roleAssignments = $this->roleAssignmentModel->getUserRoles($userId, false);

        if (empty($roleAssignments)) {
            return [];
        }

        $roleIds = array_column($roleAssignments, 'role_id');

        // Get all permissions for these roles
        $permissions = $this->permissionModel
            ->select('DISTINCT permissions.*')
            ->join('role_permissions', 'role_permissions.permission_id = permissions.id')
            ->whereIn('role_permissions.role_id', $roleIds)
            ->findAll();

        return $permissions;
    }

    /**
     * Get user's permissions grouped by module
     *
     * @param int $userId User ID
     * @return array Grouped permissions
     */
    public function getUserPermissionsGrouped(int $userId): array
    {
        $permissions = $this->getUserPermissions($userId);

        $grouped = [];
        foreach ($permissions as $permission) {
            $module = $permission['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission['action'];
        }

        return $grouped;
    }

    /**
     * Build WHERE clause for condition-based data filtering
     *
     * This method generates SQL WHERE conditions based on user's permission conditions,
     * useful for filtering data queries (e.g., "show only customers in user's department")
     *
     * @param int $userId User ID
     * @param string $module Module name
     * @param string $action Action name
     * @return array|null WHERE clause array for Query Builder, or null if no restrictions
     */
    public function getDataFilterConditions(int $userId, string $module, string $action): ?array
    {
        $roleAssignments = $this->roleAssignmentModel->getUserRoles($userId, false);

        if (empty($roleAssignments)) {
            return ['1' => 0]; // No access
        }

        $permission = $this->permissionModel
            ->where('module', $module)
            ->where('action', $action)
            ->first();

        if (!$permission) {
            return ['1' => 0]; // No access
        }

        $allConditions = [];

        foreach ($roleAssignments as $assignment) {
            // Check if role has this permission
            $rolePermission = $this->rolePermissionModel
                ->where('role_id', $assignment['role_id'])
                ->where('permission_id', $permission['id'])
                ->first();

            if (!$rolePermission) {
                continue;
            }

            // Get condition rules
            $rules = $this->conditionRuleModel
                ->where('role_id', $assignment['role_id'])
                ->groupStart()
                    ->where('permission_id', $permission['id'])
                    ->orWhere('permission_id IS NULL')
                ->groupEnd()
                ->findAll();

            if (empty($rules)) {
                // No conditions = full access
                return null;
            }

            // Convert rules to WHERE conditions
            $roleConditions = $this->buildWhereFromRules($rules);
            if ($roleConditions) {
                $allConditions[] = $roleConditions;
            }
        }

        if (empty($allConditions)) {
            return ['1' => 0]; // No access
        }

        // Combine with OR (maximum permission principle)
        return ['OR' => $allConditions];
    }

    /**
     * Build WHERE clause from condition rules
     *
     * @param array $rules Condition rules
     * @return array WHERE clause
     */
    protected function buildWhereFromRules(array $rules): array
    {
        $conditions = [];

        foreach ($rules as $rule) {
            $field = $rule['condition_type'];
            $operator = $rule['operator'];
            $value = is_string($rule['condition_value'])
                ? json_decode($rule['condition_value'], true)
                : $rule['condition_value'];

            switch ($operator) {
                case 'equals':
                    $conditions[$field] = $value['value'] ?? $value;
                    break;

                case 'in':
                    $conditions[$field . ' IN'] = $value['values'] ?? $value;
                    break;

                case 'not_in':
                    $conditions[$field . ' NOT IN'] = $value['values'] ?? $value;
                    break;

                case 'greater_than':
                    $conditions[$field . ' >'] = $value['value'] ?? $value;
                    break;

                case 'less_than':
                    $conditions[$field . ' <'] = $value['value'] ?? $value;
                    break;

                case 'between':
                    $conditions[$field . ' >='] = $value['min'];
                    $conditions[$field . ' <='] = $value['max'];
                    break;
            }
        }

        return $conditions;
    }
}
