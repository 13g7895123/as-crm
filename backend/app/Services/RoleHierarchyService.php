<?php

namespace App\Services;

use App\Models\RoleModel;
use App\Models\AuditLogModel;

/**
 * Role Hierarchy Service
 *
 * Manages role hierarchy relationships and permission inheritance.
 */
class RoleHierarchyService
{
    protected RoleModel $roleModel;
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->auditLogModel = new AuditLogModel();
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

        // Check for circular dependency
        if ($this->roleModel->wouldCreateCircularDependency($childRoleId, $parentRoleId)) {
            throw new \RuntimeException('無法新增父角色：會造成循環依賴');
        }

        // Check if relationship already exists
        $db = \Config\Database::connect();
        $existing = $db->table('role_hierarchy')
            ->where('child_role_id', $childRoleId)
            ->where('parent_role_id', $parentRoleId)
            ->get()
            ->getRowArray();

        if ($existing) {
            throw new \RuntimeException('父角色關係已存在');
        }

        // Create relationship
        $db->transStart();

        try {
            $db->table('role_hierarchy')->insert([
                'parent_role_id' => $parentRoleId,
                'child_role_id' => $childRoleId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Log the action
            $this->auditLogModel->insert([
                'user_id' => $createdBy,
                'action' => 'role_hierarchy_created',
                'resource_type' => 'role_hierarchy',
                'resource_id' => null,
                'details' => json_encode([
                    'child_role_id' => $childRoleId,
                    'child_role_name' => $childRole['display_name'],
                    'parent_role_id' => $parentRoleId,
                    'parent_role_name' => $parentRole['display_name'],
                ], JSON_UNESCAPED_UNICODE),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $db->transComplete();

            return $db->transStatus() !== false;
        } catch (\Exception $e) {
            $db->transRollback();
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
        $db = \Config\Database::connect();

        // Check if relationship exists
        $existing = $db->table('role_hierarchy')
            ->where('child_role_id', $childRoleId)
            ->where('parent_role_id', $parentRoleId)
            ->get()
            ->getRowArray();

        if (!$existing) {
            throw new \RuntimeException('父角色關係不存在');
        }

        $db->transStart();

        try {
            // Delete relationship
            $db->table('role_hierarchy')
                ->where('child_role_id', $childRoleId)
                ->where('parent_role_id', $parentRoleId)
                ->delete();

            // Log the action
            $childRole = $this->roleModel->find($childRoleId);
            $parentRole = $this->roleModel->find($parentRoleId);

            $this->auditLogModel->insert([
                'user_id' => $deletedBy,
                'action' => 'role_hierarchy_deleted',
                'resource_type' => 'role_hierarchy',
                'resource_id' => null,
                'details' => json_encode([
                    'child_role_id' => $childRoleId,
                    'child_role_name' => $childRole['display_name'] ?? '',
                    'parent_role_id' => $parentRoleId,
                    'parent_role_name' => $parentRole['display_name'] ?? '',
                ], JSON_UNESCAPED_UNICODE),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $db->transComplete();

            return $db->transStatus() !== false;
        } catch (\Exception $e) {
            $db->transRollback();
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
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Remove all existing parents
            $db->table('role_hierarchy')
                ->where('child_role_id', $childRoleId)
                ->delete();

            // Add new parent if provided
            if ($parentRoleId !== null) {
                $this->addParent($childRoleId, $parentRoleId, $modifiedBy);
            }

            $db->transComplete();

            return $db->transStatus() !== false;
        } catch (\Exception $e) {
            $db->transRollback();
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
        return $this->roleModel->getHierarchyTree();
    }

    /**
     * Get role's full hierarchy info
     *
     * @param int $roleId
     * @return array
     */
    public function getRoleHierarchyInfo(int $roleId): array
    {
        $role = $this->roleModel->find($roleId);

        if (!$role) {
            throw new \RuntimeException('角色不存在');
        }

        return [
            'role' => $role,
            'parents' => $this->roleModel->getParentRoles($roleId),
            'children' => $this->roleModel->getChildRoles($roleId),
            'ancestors' => $this->roleModel->getAncestorRoles($roleId),
            'descendants' => $this->roleModel->getDescendantRoles($roleId),
        ];
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
        $ancestors = $this->roleModel->getAncestorRoles($roleId);
        return count($ancestors);
    }
}
