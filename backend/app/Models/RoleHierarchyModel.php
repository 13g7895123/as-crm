<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Role Hierarchy Model
 *
 * Implements Closure Table pattern for role hierarchy management.
 * Stores all ancestor-descendant relationships for efficient querying.
 *
 * Table structure:
 * - ancestor_id: The ancestor role (parent or higher)
 * - descendant_id: The descendant role (child or lower)
 * - depth: Distance between ancestor and descendant (0 = self, 1 = direct parent, etc.)
 *
 * Example:
 * If we have: CEO -> Manager -> Employee
 * The table will contain:
 * - (CEO, CEO, 0)       - CEO's self-reference
 * - (CEO, Manager, 1)   - CEO is direct parent of Manager
 * - (CEO, Employee, 2)  - CEO is grandparent of Employee
 * - (Manager, Manager, 0)  - Manager's self-reference
 * - (Manager, Employee, 1) - Manager is direct parent of Employee
 * - (Employee, Employee, 0) - Employee's self-reference
 */
class RoleHierarchyModel extends Model
{
    protected $table = 'role_hierarchy';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'ancestor_id',
        'descendant_id',
        'depth',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = false; // No updated_at for this table
    protected $deletedField = false;

    protected $validationRules = [
        'ancestor_id' => 'required|integer',
        'descendant_id' => 'required|integer',
        'depth' => 'required|integer|greater_than_equal_to[0]',
    ];

    /**
     * Get all ancestors of a role (parents, grandparents, etc.)
     *
     * @param int $roleId
     * @param bool $includeSelf Include the role itself (depth 0)
     * @return array Array of ancestor role IDs
     */
    public function getAncestors(int $roleId, bool $includeSelf = false): array
    {
        $builder = $this->db->table($this->table)
            ->select('ancestor_id')
            ->where('descendant_id', $roleId);

        if (!$includeSelf) {
            $builder->where('depth >', 0);
        }

        $builder->orderBy('depth', 'ASC');

        $results = $builder->get()->getResultArray();

        return array_column($results, 'ancestor_id');
    }

    /**
     * Get all descendants of a role (children, grandchildren, etc.)
     *
     * @param int $roleId
     * @param bool $includeSelf Include the role itself (depth 0)
     * @return array Array of descendant role IDs
     */
    public function getDescendants(int $roleId, bool $includeSelf = false): array
    {
        $builder = $this->db->table($this->table)
            ->select('descendant_id')
            ->where('ancestor_id', $roleId);

        if (!$includeSelf) {
            $builder->where('depth >', 0);
        }

        $builder->orderBy('depth', 'ASC');

        $results = $builder->get()->getResultArray();

        return array_column($results, 'descendant_id');
    }

    /**
     * Get direct parent of a role (depth = 1)
     *
     * @param int $roleId
     * @return int|null Parent role ID or null if no parent
     */
    public function getParent(int $roleId): ?int
    {
        $result = $this->db->table($this->table)
            ->select('ancestor_id')
            ->where('descendant_id', $roleId)
            ->where('depth', 1)
            ->get()
            ->getRowArray();

        return $result ? (int)$result['ancestor_id'] : null;
    }

    /**
     * Get direct children of a role (depth = 1)
     *
     * @param int $roleId
     * @return array Array of child role IDs
     */
    public function getChildren(int $roleId): array
    {
        $results = $this->db->table($this->table)
            ->select('descendant_id')
            ->where('ancestor_id', $roleId)
            ->where('depth', 1)
            ->get()
            ->getResultArray();

        return array_column($results, 'descendant_id');
    }

    /**
     * Get depth (distance) between two roles
     *
     * @param int $ancestorId
     * @param int $descendantId
     * @return int|null Depth or null if no relationship exists
     */
    public function getDepth(int $ancestorId, int $descendantId): ?int
    {
        $result = $this->db->table($this->table)
            ->select('depth')
            ->where('ancestor_id', $ancestorId)
            ->where('descendant_id', $descendantId)
            ->get()
            ->getRowArray();

        return $result ? (int)$result['depth'] : null;
    }

    /**
     * Check if a role is an ancestor of another role
     *
     * @param int $ancestorId
     * @param int $descendantId
     * @return bool
     */
    public function isAncestor(int $ancestorId, int $descendantId): bool
    {
        if ($ancestorId === $descendantId) {
            return false; // A role is not its own ancestor
        }

        return $this->db->table($this->table)
            ->where('ancestor_id', $ancestorId)
            ->where('descendant_id', $descendantId)
            ->where('depth >', 0)
            ->countAllResults() > 0;
    }

    /**
     * Add a parent-child relationship
     * This will update the closure table to include all necessary paths
     *
     * @param int $childId
     * @param int $parentId
     * @param int $createdBy User ID who created this relationship
     * @return void
     * @throws \RuntimeException
     */
    public function addParent(int $childId, int $parentId, int $createdBy): void
    {
        // Validate: prevent self-parenting
        if ($childId === $parentId) {
            throw new \RuntimeException('角色不能設定自己為父角色');
        }

        // Validate: prevent circular reference (child is ancestor of parent)
        if ($this->isAncestor($childId, $parentId)) {
            throw new \RuntimeException('無法設定父角色: 會造成循環繼承');
        }

        // Check if relationship already exists
        if ($this->isAncestor($parentId, $childId)) {
            throw new \RuntimeException('父角色關係已存在');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Insert new paths:
            // For each ancestor of parent (including parent itself),
            // create a path to each descendant of child (including child itself)

            $sql = "
                INSERT INTO {$this->table} (ancestor_id, descendant_id, depth, created_by)
                SELECT parent_path.ancestor_id, child_path.descendant_id,
                       parent_path.depth + child_path.depth + 1, ?
                FROM {$this->table} AS parent_path
                CROSS JOIN {$this->table} AS child_path
                WHERE parent_path.descendant_id = ?
                  AND child_path.ancestor_id = ?
            ";

            $db->query($sql, [$createdBy, $parentId, $childId]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('新增父角色關係失敗');
            }
        } catch (\Exception $e) {
            $db->transRollback();
            throw new \RuntimeException('新增父角色關係失敗: ' . $e->getMessage());
        }
    }

    /**
     * Remove a parent-child relationship
     * This will update the closure table to remove all affected paths
     *
     * @param int $childId
     * @param int $parentId
     * @return void
     * @throws \RuntimeException
     */
    public function removeParent(int $childId, int $parentId): void
    {
        // Check if relationship exists
        if (!$this->isAncestor($parentId, $childId)) {
            throw new \RuntimeException('父角色關係不存在');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Delete all paths that go through this parent-child relationship:
            // Any path from an ancestor of parent to a descendant of child
            // that has the exact depth sum (parent_depth + 1 + child_depth)

            $sql = "
                DELETE FROM {$this->table}
                WHERE ancestor_id IN (
                    SELECT ancestor_id FROM (
                        SELECT ancestor_id FROM {$this->table}
                        WHERE descendant_id = ?
                    ) AS parent_ancestors
                )
                AND descendant_id IN (
                    SELECT descendant_id FROM (
                        SELECT descendant_id FROM {$this->table}
                        WHERE ancestor_id = ?
                    ) AS child_descendants
                )
                AND depth > 0
            ";

            $db->query($sql, [$parentId, $childId]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('移除父角色關係失敗');
            }
        } catch (\Exception $e) {
            $db->transRollback();
            throw new \RuntimeException('移除父角色關係失敗: ' . $e->getMessage());
        }
    }

    /**
     * Set/change the parent of a role
     * This will remove old parent (if exists) and set new parent
     *
     * @param int $childId
     * @param int|null $newParentId Null to remove all parents
     * @param int $modifiedBy User ID who made this change
     * @return void
     * @throws \RuntimeException
     */
    public function setParent(int $childId, ?int $newParentId, int $modifiedBy): void
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Get current parent
            $currentParent = $this->getParent($childId);

            // Remove current parent if exists
            if ($currentParent !== null) {
                $this->removeParent($childId, $currentParent);
            }

            // Add new parent if provided
            if ($newParentId !== null) {
                $this->addParent($childId, $newParentId, $modifiedBy);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('設定父角色失敗');
            }
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Initialize hierarchy for a new role (self-reference only)
     *
     * @param int $roleId
     * @param int $createdBy
     * @return void
     */
    public function initializeRole(int $roleId, int $createdBy): void
    {
        // Add self-reference (depth 0)
        $this->insert([
            'ancestor_id' => $roleId,
            'descendant_id' => $roleId,
            'depth' => 0,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Remove all hierarchy relationships for a role
     * This should be called when deleting a role
     *
     * @param int $roleId
     * @return void
     */
    public function removeRole(int $roleId): void
    {
        $db = \Config\Database::connect();

        // Delete all paths where this role is either ancestor or descendant
        $db->table($this->table)
            ->where('ancestor_id', $roleId)
            ->orWhere('descendant_id', $roleId)
            ->delete();
    }

    /**
     * Get full hierarchy tree structure
     * Returns all roles organized in a tree structure
     *
     * @return array
     */
    public function getHierarchyTree(): array
    {
        $roleModel = new RoleModel();

        // Get all roles
        $roles = $roleModel->findAll();

        // Build role map
        $roleMap = [];
        foreach ($roles as $role) {
            $roleMap[$role['id']] = [
                'id' => $role['id'],
                'name' => $role['name'],
                'display_name' => $role['display_name'],
                'description' => $role['description'],
                'children' => [],
                'parent_id' => $this->getParent($role['id']),
            ];
        }

        // Build tree structure
        $tree = [];
        foreach ($roleMap as $id => $role) {
            if ($role['parent_id'] === null) {
                // Top-level role
                $tree[] = &$roleMap[$id];
            } else {
                // Add as child to parent
                if (isset($roleMap[$role['parent_id']])) {
                    $roleMap[$role['parent_id']]['children'][] = &$roleMap[$id];
                }
            }
        }

        return $tree;
    }

    /**
     * Get hierarchy information for a specific role
     *
     * @param int $roleId
     * @return array
     */
    public function getRoleHierarchyInfo(int $roleId): array
    {
        $roleModel = new RoleModel();
        $role = $roleModel->find($roleId);

        if (!$role) {
            throw new \RuntimeException('角色不存在');
        }

        $ancestors = $this->getAncestors($roleId);
        $descendants = $this->getDescendants($roleId);
        $parent = $this->getParent($roleId);
        $children = $this->getChildren($roleId);

        // Get role details for ancestors
        $ancestorRoles = [];
        if (!empty($ancestors)) {
            $ancestorRoles = $roleModel->find($ancestors);
        }

        // Get role details for descendants
        $descendantRoles = [];
        if (!empty($descendants)) {
            $descendantRoles = $roleModel->find($descendants);
        }

        return [
            'role' => $role,
            'parent_id' => $parent,
            'ancestors' => $ancestorRoles,
            'children' => $children,
            'descendants' => $descendantRoles,
            'depth' => $parent === null ? 0 : ($this->getDepth($parent, $roleId) ?? 0),
        ];
    }
}
