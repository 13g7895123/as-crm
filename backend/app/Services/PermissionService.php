<?php

namespace App\Services;

use App\Models\PermissionModel;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;
use App\Models\RoleHierarchyModel;
use App\Models\ConditionRuleModel;

/**
 * Permission Service
 *
 * Handles business logic for permission management including querying permissions,
 * checking user permissions, and supporting role hierarchy inheritance.
 */
class PermissionService
{
    protected PermissionModel $permissionModel;
    protected RoleModel $roleModel;
    protected RolePermissionModel $rolePermissionModel;
    protected RoleHierarchyModel $hierarchyModel;
    protected ConditionRuleModel $conditionRuleModel;

    public function __construct()
    {
        $this->permissionModel = new PermissionModel();
        $this->roleModel = new RoleModel();
        $this->rolePermissionModel = new RolePermissionModel();
        $this->hierarchyModel = new RoleHierarchyModel();
        $this->conditionRuleModel = new ConditionRuleModel();
    }

    /**
     * Get all permissions grouped by resource
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        return $this->permissionModel->getGroupedByResource();
    }

    /**
     * Get permissions with filters
     *
     * @param array $filters
     * @return array
     */
    public function getPermissions(array $filters = []): array
    {
        return $this->permissionModel->getPermissionsWithFilters($filters);
    }

    /**
     * Get permission by ID
     *
     * @param int $permissionId
     * @return array|null
     */
    public function getPermissionById(int $permissionId): ?array
    {
        return $this->permissionModel->find($permissionId);
    }

    /**
     * Check if user has permission (includes inherited permissions from role hierarchy)
     *
     * @param int $userId
     * @param string $permissionName
     * @param array $context
     * @return bool
     */
    public function userHasPermission(int $userId, string $permissionName, array $context = []): bool
    {
        // Get permission
        $permission = $this->permissionModel->where('name', $permissionName)->first();

        if (!$permission) {
            return false;
        }

        // Get user's roles
        $db = \Config\Database::connect();
        $userRoles = $db->table('role_assignments')
            ->select('role_id')
            ->where('user_id', $userId)
            ->where('(expires_at IS NULL OR expires_at > NOW())')
            ->get()
            ->getResultArray();

        if (empty($userRoles)) {
            return false;
        }

        $roleIds = array_column($userRoles, 'role_id');

        // Collect all roles to check (including ancestors via inheritance)
        $allRolesToCheck = [];
        foreach ($roleIds as $roleId) {
            // Add the role itself
            $allRolesToCheck[] = $roleId;

            // Add all ancestor roles (inherited)
            $ancestorIds = $this->hierarchyModel->getAncestors($roleId, false);
            $allRolesToCheck = array_merge($allRolesToCheck, $ancestorIds);
        }

        // Remove duplicates
        $allRolesToCheck = array_unique($allRolesToCheck);

        // Check if any role (direct or inherited) has the permission
        foreach ($allRolesToCheck as $roleId) {
            if ($this->rolePermissionModel->roleHasPermission($roleId, $permission['id'])) {
                // Check condition rules
                $rules = $this->conditionRuleModel->getRulesByPermission($permission['id']);

                if (empty($rules)) {
                    return true; // No conditions, permission granted
                }

                if ($this->conditionRuleModel->matchesConditions($rules, $context)) {
                    return true; // Conditions met, permission granted
                }
            }
        }

        return false;
    }

    /**
     * Check multiple permissions for a user
     *
     * @param int $userId
     * @param array $permissionNames
     * @param array $context
     * @return array
     */
    public function checkPermissions(int $userId, array $permissionNames, array $context = []): array
    {
        $result = [];

        foreach ($permissionNames as $permissionName) {
            $result[$permissionName] = $this->userHasPermission($userId, $permissionName, $context);
        }

        return $result;
    }

    /**
     * Get user's permissions
     *
     * @param int $userId
     * @param bool $includeInherited
     * @return array
     */
    public function getUserPermissions(int $userId, bool $includeInherited = true): array
    {
        $db = \Config\Database::connect();

        // Get user's roles
        $userRoles = $db->table('role_assignments')
            ->select('role_assignments.*, roles.name as role_name, roles.display_name as role_display_name')
            ->join('roles', 'roles.id = role_assignments.role_id')
            ->where('role_assignments.user_id', $userId)
            ->where('(role_assignments.expires_at IS NULL OR role_assignments.expires_at > NOW())')
            ->get()
            ->getResultArray();

        if (empty($userRoles)) {
            return [
                'user_id'     => $userId,
                'roles'       => [],
                'permissions' => [],
                'effective_condition_rules' => [],
            ];
        }

        $roleIds = array_column($userRoles, 'role_id');

        // Get permissions for all roles
        $allPermissions = [];
        $permissionSources = []; // Track which role grants which permission

        foreach ($roleIds as $index => $roleId) {
            // Get direct permissions for this role
            $rolePermissions = $this->rolePermissionModel->getPermissionsByRole($roleId);

            foreach ($rolePermissions as $permission) {
                $permId = $permission['id'];

                if (!isset($allPermissions[$permId])) {
                    $allPermissions[$permId] = $permission;
                    $permissionSources[$permId] = [
                        'source' => $userRoles[$index]['role_display_name'],
                        'source_role_id' => $roleId,
                        'is_inherited' => false,
                    ];
                }
            }

            // Get inherited permissions from all ancestor roles if requested
            if ($includeInherited) {
                // Use Closure Table to get all ancestors (not just direct parents)
                $ancestorIds = $this->hierarchyModel->getAncestors($roleId, false);

                if (!empty($ancestorIds)) {
                    // Get roles data for ancestors
                    $ancestorRoles = $this->roleModel->find($ancestorIds);

                    // Ensure it's an array of roles
                    if (!empty($ancestorRoles) && !isset($ancestorRoles[0])) {
                        $ancestorRoles = [$ancestorRoles];
                    }

                    // Create a map of ancestor roles
                    $ancestorRoleMap = [];
                    foreach ($ancestorRoles as $ancestorRole) {
                        $ancestorRoleMap[$ancestorRole['id']] = $ancestorRole;
                    }

                    // Get permissions from each ancestor role
                    foreach ($ancestorIds as $ancestorId) {
                        $ancestorPermissions = $this->rolePermissionModel->getPermissionsByRole($ancestorId);

                        foreach ($ancestorPermissions as $permission) {
                            $permId = $permission['id'];

                            // Only add if not already granted by a closer role (max permission principle)
                            if (!isset($allPermissions[$permId])) {
                                $allPermissions[$permId] = $permission;
                                $permissionSources[$permId] = [
                                    'source' => $ancestorRoleMap[$ancestorId]['display_name'] ?? "Role #{$ancestorId}",
                                    'source_role_id' => $ancestorId,
                                    'is_inherited' => true,
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Add source information to permissions
        $permissionsWithSource = [];
        foreach ($allPermissions as $permId => $permission) {
            $permission['source'] = $permissionSources[$permId]['source'];
            $permission['is_inherited'] = $permissionSources[$permId]['is_inherited'];
            $permissionsWithSource[] = $permission;
        }

        // Get effective condition rules
        $conditionRules = [];
        if (!empty($allPermissions)) {
            $permissionIds = array_keys($allPermissions);

            $rules = $db->table('condition_rules')
                ->whereIn('permission_id', $permissionIds)
                ->orWhere('permission_id', null)
                ->get()
                ->getResultArray();

            foreach ($rules as $rule) {
                $conditionRules[] = $rule;
            }
        }

        // Format roles for response
        $formattedRoles = [];
        foreach ($userRoles as $role) {
            $formattedRoles[] = [
                'role_id' => $role['role_id'],
                'role_name' => $role['role_display_name'],
                'valid_from' => $role['assigned_at'] ?? null,
                'valid_until' => $role['expires_at'] ?? null,
                'is_expired' => !empty($role['expires_at']) && strtotime($role['expires_at']) < time(),
                'days_until_expiry' => $this->calculateDaysUntilExpiry($role['expires_at'] ?? null),
            ];
        }

        return [
            'user_id' => $userId,
            'roles' => $formattedRoles,
            'permissions' => array_values($permissionsWithSource),
            'effective_condition_rules' => $conditionRules,
        ];
    }

    /**
     * Get permissions by resource
     *
     * @param string $resource
     * @return array
     */
    public function getPermissionsByResource(string $resource): array
    {
        return $this->permissionModel->where('resource', $resource)->findAll();
    }

    /**
     * Get permission by resource and action
     *
     * @param string $resource
     * @param string $action
     * @return array|null
     */
    public function getPermissionByResourceAndAction(string $resource, string $action): ?array
    {
        return $this->permissionModel->getByResourceAndAction($resource, $action);
    }

    /**
     * Calculate days until expiry
     *
     * @param string|null $expiryDate
     * @return int|null
     */
    private function calculateDaysUntilExpiry(?string $expiryDate): ?int
    {
        if (empty($expiryDate)) {
            return null;
        }

        $expiry = strtotime($expiryDate);
        $now = time();

        if ($expiry < $now) {
            return 0; // Already expired
        }

        return (int) ceil(($expiry - $now) / 86400);
    }
}
