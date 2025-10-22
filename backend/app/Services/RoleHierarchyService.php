<?php

namespace App\Services;

use App\Models\RoleModel;
use App\Models\RoleHierarchyModel;
use App\Models\AuditLogModel;

/**
 * Role Hierarchy Service
 *
 * Manages role hierarchy relationships and permission inheritance using Closure Table pattern.
 * Automatically clears permission cache when hierarchy changes to ensure inherited permissions
 * are immediately reflected.
 */
class RoleHierarchyService
{
    protected RoleModel $roleModel;
    protected RoleHierarchyModel $hierarchyModel;
    protected AuditLogModel $auditLogModel;
    protected ?AuthorizationService $authService = null;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->hierarchyModel = new RoleHierarchyModel();
        $this->auditLogModel = new AuditLogModel();
    }

    /**
     * Get AuthorizationService instance (lazy loading to avoid circular dependency)
     *
     * @return AuthorizationService
     */
    protected function getAuthService(): AuthorizationService
    {
        if ($this->authService === null) {
            $this->authService = new AuthorizationService();
        }
        return $this->authService;
    }

    /**
     * Add parent role to a role
     *
     * @param int $childRoleId
     * @param int $parentRoleId
     * @param int|null $createdBy User who created the relationship
     * @return bool
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function addParent(int $childRoleId, int $parentRoleId, ?int $createdBy = null): bool
    {
        // Validate roles exist
        $childRole = $this->roleModel->find($childRoleId);
        $parentRole = $this->roleModel->find($parentRoleId);

        if (!$childRole) {
            throw new \InvalidArgumentException('子角色不存在');
        }

        if (!$parentRole) {
            throw new \InvalidArgumentException('父角色不存在');
        }

        try {
            // Use Closure Table model to add parent
            $this->hierarchyModel->addParent($childRoleId, $parentRoleId, $createdBy ?? 0);

            // Clear permission cache for all users since hierarchy affects inherited permissions
            $this->getAuthService()->clearAllPermissionCache();

            // Log the action
            $this->auditLogModel->insert([
                'user_id' => $createdBy,
                'action' => 'role_hierarchy_created',
                'target_type' => 'role_hierarchy',
                'target_id' => null,
                'new_values' => json_encode([
                    'child_role_id' => $childRoleId,
                    'child_role_name' => $childRole['display_name'],
                    'parent_role_id' => $parentRoleId,
                    'parent_role_name' => $parentRole['display_name'],
                ], JSON_UNESCAPED_UNICODE),
                'result' => 'success',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Remove parent role from a role
     *
     * @param int $childRoleId
     * @param int $parentRoleId
     * @param int|null $deletedBy User who deleted the relationship
     * @return bool
     * @throws \RuntimeException
     */
    public function removeParent(int $childRoleId, int $parentRoleId, ?int $deletedBy = null): bool
    {
        try {
            // Get role info before deletion for logging
            $childRole = $this->roleModel->find($childRoleId);
            $parentRole = $this->roleModel->find($parentRoleId);

            // Use Closure Table model to remove parent
            $this->hierarchyModel->removeParent($childRoleId, $parentRoleId);

            // Clear permission cache for all users since hierarchy affects inherited permissions
            $this->getAuthService()->clearAllPermissionCache();

            // Log the action
            $this->auditLogModel->insert([
                'user_id' => $deletedBy,
                'action' => 'role_hierarchy_deleted',
                'target_type' => 'role_hierarchy',
                'target_id' => null,
                'old_values' => json_encode([
                    'child_role_id' => $childRoleId,
                    'child_role_name' => $childRole['display_name'] ?? '',
                    'parent_role_id' => $parentRoleId,
                    'parent_role_name' => $parentRole['display_name'] ?? '',
                ], JSON_UNESCAPED_UNICODE),
                'result' => 'success',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Set parent role (replaces all existing parents)
     *
     * @param int $childRoleId
     * @param int|null $parentRoleId
     * @param int|null $modifiedBy User who modified the relationship
     * @return bool
     * @throws \RuntimeException
     */
    public function setParent(int $childRoleId, ?int $parentRoleId, ?int $modifiedBy = null): bool
    {
        try {
            // Use Closure Table model to set parent
            $this->hierarchyModel->setParent($childRoleId, $parentRoleId, $modifiedBy ?? 0);

            // Clear permission cache for all users since hierarchy affects inherited permissions
            $this->getAuthService()->clearAllPermissionCache();

            // Log the action
            $childRole = $this->roleModel->find($childRoleId);
            $parentRole = $parentRoleId ? $this->roleModel->find($parentRoleId) : null;

            $this->auditLogModel->insert([
                'user_id' => $modifiedBy,
                'action' => 'role_hierarchy_updated',
                'target_type' => 'role_hierarchy',
                'target_id' => null,
                'new_values' => json_encode([
                    'child_role_id' => $childRoleId,
                    'child_role_name' => $childRole['display_name'] ?? '',
                    'parent_role_id' => $parentRoleId,
                    'parent_role_name' => $parentRole['display_name'] ?? null,
                ], JSON_UNESCAPED_UNICODE),
                'result' => 'success',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get role hierarchy tree
     *
     * @return array
     */
    public function getHierarchyTree(): array
    {
        return $this->hierarchyModel->getHierarchyTree();
    }

    /**
     * Get role's full hierarchy info
     *
     * @param int $roleId
     * @return array
     */
    public function getRoleHierarchyInfo(int $roleId): array
    {
        return $this->hierarchyModel->getRoleHierarchyInfo($roleId);
    }

    /**
     * Get effective permissions for a role (including inherited)
     *
     * @param int $roleId
     * @return array
     */
    public function getEffectivePermissions(int $roleId): array
    {
        $permissions = $this->roleModel->getRolePermissions($roleId, true);

        // Combine direct and inherited permissions
        $allPermissions = [];

        // Add direct permissions
        foreach ($permissions['direct_permissions'] as $perm) {
            $allPermissions[$perm['id']] = [
                'permission' => $perm,
                'source' => 'direct',
                'inherited_from' => null,
            ];
        }

        // Add inherited permissions
        foreach ($permissions['inherited_permissions'] as $inherited) {
            $permId = $inherited['permission']['id'];

            // Only add if not already granted directly
            if (!isset($allPermissions[$permId])) {
                $allPermissions[$permId] = [
                    'permission' => $inherited['permission'],
                    'source' => 'inherited',
                    'inherited_from' => $inherited['inherited_from'],
                ];
            }
        }

        return array_values($allPermissions);
    }

    /**
     * Validate hierarchy consistency
     *
     * @return array Validation errors
     */
    public function validateHierarchy(): array
    {
        $errors = [];
        $db = \Config\Database::connect();

        // Get all hierarchy relationships
        $hierarchies = $db->table('role_hierarchy')
            ->get()
            ->getResultArray();

        // Check for circular dependencies
        foreach ($hierarchies as $hierarchy) {
            $childId = $hierarchy['child_role_id'];
            $parentId = $hierarchy['parent_role_id'];

            if ($this->roleModel->wouldCreateCircularDependency($childId, $parentId)) {
                $childRole = $this->roleModel->find($childId);
                $parentRole = $this->roleModel->find($parentId);

                $errors[] = [
                    'type' => 'circular_dependency',
                    'child_role' => $childRole['display_name'] ?? "ID:{$childId}",
                    'parent_role' => $parentRole['display_name'] ?? "ID:{$parentId}",
                    'message' => '檢測到循環依賴',
                ];
            }
        }

        // Check for orphaned hierarchy records (references non-existent roles)
        foreach ($hierarchies as $hierarchy) {
            $childRole = $this->roleModel->find($hierarchy['child_role_id']);
            $parentRole = $this->roleModel->find($hierarchy['parent_role_id']);

            if (!$childRole) {
                $errors[] = [
                    'type' => 'orphaned_child',
                    'child_role_id' => $hierarchy['child_role_id'],
                    'message' => '階層關係引用不存在的子角色',
                ];
            }

            if (!$parentRole) {
                $errors[] = [
                    'type' => 'orphaned_parent',
                    'parent_role_id' => $hierarchy['parent_role_id'],
                    'message' => '階層關係引用不存在的父角色',
                ];
            }
        }

        return $errors;
    }

    /**
     * Get roles at a specific level
     *
     * @param int $level
     * @return array
     */
    public function getRolesByLevel(int $level): array
    {
        return $this->roleModel->where('level', $level)->findAll();
    }

    /**
     * Calculate and update role levels based on hierarchy
     *
     * @return int Number of roles updated
     */
    public function recalculateLevels(): int
    {
        $updated = 0;
        $allRoles = $this->roleModel->findAll();

        foreach ($allRoles as $role) {
            $level = $this->calculateRoleLevel($role['id']);

            if ($role['level'] !== $level) {
                $this->roleModel->update($role['id'], ['level' => $level]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Calculate role level based on hierarchy depth
     *
     * @param int $roleId
     * @return int
     */
    private function calculateRoleLevel(int $roleId): int
    {
        $ancestors = $this->hierarchyModel->getAncestors($roleId);
        return count($ancestors);
    }

    /**
     * Initialize role hierarchy for a new role
     *
     * @param int $roleId
     * @param int $createdBy
     * @return void
     */
    public function initializeRoleHierarchy(int $roleId, int $createdBy): void
    {
        $this->hierarchyModel->initializeRole($roleId, $createdBy);
    }

    /**
     * Remove role from hierarchy (called when deleting role)
     *
     * @param int $roleId
     * @return void
     */
    public function removeRoleFromHierarchy(int $roleId): void
    {
        $this->hierarchyModel->removeRole($roleId);
    }
}
