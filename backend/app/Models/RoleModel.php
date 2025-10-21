<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Role Model
 *
 * Manages roles in the RBAC system
 */
class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'name',
        'display_name',
        'description',
        'level',
        'is_system',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'name'         => 'required|min_length[3]|max_length[100]|regex_match[/^[a-z0-9_]+$/]|is_unique[roles.name,id,{id}]',
        'display_name' => 'required|min_length[2]|max_length[255]',
        'description'  => 'permit_empty|max_length[1000]',
        'level'        => 'permit_empty|integer',
        'is_system'    => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required'     => '角色名稱為必填',
            'min_length'   => '角色名稱至少需要3個字元',
            'max_length'   => '角色名稱不能超過100個字元',
            'regex_match'  => '角色名稱只能包含小寫字母、數字和底線',
            'is_unique'    => '角色名稱已存在',
        ],
        'display_name' => [
            'required'   => '顯示名稱為必填',
            'min_length' => '顯示名稱至少需要2個字元',
            'max_length' => '顯示名稱不能超過255個字元',
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
    protected $beforeDelete   = ['checkSystemRole'];
    protected $afterDelete    = [];

    /**
     * Prevent deletion of system roles
     *
     * @param array $data
     * @return array
     * @throws \RuntimeException
     */
    protected function checkSystemRole(array $data): array
    {
        if (isset($data['id'])) {
            $ids = is_array($data['id']) ? $data['id'] : [$data['id']];

            foreach ($ids as $id) {
                $role = $this->find($id);
                if ($role && $role['is_system']) {
                    throw new \RuntimeException('無法刪除系統角色');
                }
            }
        }

        return $data;
    }

    /**
     * Get role with permissions
     *
     * @param int $roleId
     * @return array|null
     */
    public function getRoleWithPermissions(int $roleId): ?array
    {
        $role = $this->find($roleId);

        if (!$role) {
            return null;
        }

        // Get permissions
        $permissions = $this->db->table('role_permissions')
            ->select('permissions.*')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->get()
            ->getResultArray();

        $role['permissions'] = $permissions;

        // Get condition rules
        $conditionRules = $this->db->table('condition_rules')
            ->where('permission_id IS NULL OR permission_id IN (SELECT permission_id FROM role_permissions WHERE role_id = ?)', $roleId)
            ->get()
            ->getResultArray();

        $role['condition_rules'] = $conditionRules;

        return $role;
    }

    /**
     * Get roles with pagination and filters
     *
     * @param array $filters
     * @return array
     */
    public function getRolesWithFilters(array $filters = []): array
    {
        $builder = $this->builder();

        // Apply filters
        if (isset($filters['is_active'])) {
            // Note: We don't have is_active in migration, using deleted_at instead
            if ($filters['is_active']) {
                $builder->where('deleted_at IS NULL');
            } else {
                $builder->where('deleted_at IS NOT NULL');
            }
        }

        if (isset($filters['is_system'])) {
            $builder->where('is_system', $filters['is_system']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $builder->groupStart()
                ->like('name', $filters['search'])
                ->orLike('display_name', $filters['search'])
                ->groupEnd();
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';
        $builder->orderBy($sortField, $sortOrder);

        // Pagination
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;

        return [
            'data' => $builder->paginate($perPage, 'default', $page),
            'meta' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $this->countAllResults(false),
                'total_pages'  => ceil($this->countAllResults(false) / $perPage),
            ],
        ];
    }

    /**
     * Assign permissions to role
     *
     * @param int $roleId
     * @param array $permissionIds
     * @return bool
     */
    public function assignPermissions(int $roleId, array $permissionIds): bool
    {
        // Remove existing permissions
        $this->db->table('role_permissions')
            ->where('role_id', $roleId)
            ->delete();

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

            return $this->db->table('role_permissions')->insertBatch($data);
        }

        return true;
    }

    /**
     * Get role permissions including inherited ones
     *
     * @param int $roleId
     * @param bool $includeInherited
     * @return array
     */
    public function getRolePermissions(int $roleId, bool $includeInherited = true): array
    {
        $result = [
            'direct_permissions'    => [],
            'inherited_permissions' => [],
        ];

        // Get direct permissions
        $result['direct_permissions'] = $this->db->table('role_permissions')
            ->select('permissions.*')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->get()
            ->getResultArray();

        // Get inherited permissions from parent roles
        if ($includeInherited) {
            $parentRoles = $this->db->table('role_hierarchy')
                ->select('parent_role_id')
                ->where('child_role_id', $roleId)
                ->get()
                ->getResultArray();

            foreach ($parentRoles as $parent) {
                $parentPermissions = $this->db->table('role_permissions')
                    ->select('permissions.*, roles.display_name as inherited_from')
                    ->join('permissions', 'permissions.id = role_permissions.permission_id')
                    ->join('roles', 'roles.id = role_permissions.role_id')
                    ->where('role_permissions.role_id', $parent['parent_role_id'])
                    ->get()
                    ->getResultArray();

                foreach ($parentPermissions as $perm) {
                    $result['inherited_permissions'][] = [
                        'permission'     => $perm,
                        'inherited_from' => $perm['inherited_from'],
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Check if role name is unique
     *
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     */
    public function isNameUnique(string $name, ?int $excludeId = null): bool
    {
        $builder = $this->builder()->where('name', $name);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() === 0;
    }

    /**
     * Get role's parent roles
     *
     * @param int $roleId
     * @return array
     */
    public function getParentRoles(int $roleId): array
    {
        return $this->db->table('role_hierarchy')
            ->select('roles.*')
            ->join('roles', 'roles.id = role_hierarchy.parent_role_id')
            ->where('role_hierarchy.child_role_id', $roleId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get role's child roles
     *
     * @param int $roleId
     * @return array
     */
    public function getChildRoles(int $roleId): array
    {
        return $this->db->table('role_hierarchy')
            ->select('roles.*')
            ->join('roles', 'roles.id = role_hierarchy.child_role_id')
            ->where('role_hierarchy.parent_role_id', $roleId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get all ancestor roles (parents, grandparents, etc.)
     *
     * @param int $roleId
     * @return array
     */
    public function getAncestorRoles(int $roleId): array
    {
        $ancestors = [];
        $visited = [];
        $this->collectAncestors($roleId, $ancestors, $visited);
        return $ancestors;
    }

    /**
     * Recursively collect ancestor roles
     *
     * @param int $roleId
     * @param array $ancestors
     * @param array $visited
     * @return void
     */
    private function collectAncestors(int $roleId, array &$ancestors, array &$visited): void
    {
        if (in_array($roleId, $visited)) {
            return; // Prevent infinite loop
        }

        $visited[] = $roleId;
        $parents = $this->getParentRoles($roleId);

        foreach ($parents as $parent) {
            if (!in_array($parent['id'], array_column($ancestors, 'id'))) {
                $ancestors[] = $parent;
                $this->collectAncestors($parent['id'], $ancestors, $visited);
            }
        }
    }

    /**
     * Get all descendant roles (children, grandchildren, etc.)
     *
     * @param int $roleId
     * @return array
     */
    public function getDescendantRoles(int $roleId): array
    {
        $descendants = [];
        $visited = [];
        $this->collectDescendants($roleId, $descendants, $visited);
        return $descendants;
    }

    /**
     * Recursively collect descendant roles
     *
     * @param int $roleId
     * @param array $descendants
     * @param array $visited
     * @return void
     */
    private function collectDescendants(int $roleId, array &$descendants, array &$visited): void
    {
        if (in_array($roleId, $visited)) {
            return; // Prevent infinite loop
        }

        $visited[] = $roleId;
        $children = $this->getChildRoles($roleId);

        foreach ($children as $child) {
            if (!in_array($child['id'], array_column($descendants, 'id'))) {
                $descendants[] = $child;
                $this->collectDescendants($child['id'], $descendants, $visited);
            }
        }
    }

    /**
     * Get complete role hierarchy tree
     *
     * @return array
     */
    public function getHierarchyTree(): array
    {
        // Get all roles
        $allRoles = $this->findAll();

        // Get all hierarchy relationships
        $hierarchies = $this->db->table('role_hierarchy')
            ->get()
            ->getResultArray();

        // Build parent-child map
        $childrenMap = [];
        foreach ($hierarchies as $h) {
            $parentId = $h['parent_role_id'];
            $childId = $h['child_role_id'];

            if (!isset($childrenMap[$parentId])) {
                $childrenMap[$parentId] = [];
            }
            $childrenMap[$parentId][] = $childId;
        }

        // Find root roles (roles with no parents)
        $rootRoles = [];
        foreach ($allRoles as $role) {
            $hasParent = false;
            foreach ($hierarchies as $h) {
                if ($h['child_role_id'] == $role['id']) {
                    $hasParent = true;
                    break;
                }
            }

            if (!$hasParent) {
                $rootRoles[] = $this->buildRoleTree($role, $childrenMap, $allRoles);
            }
        }

        return $rootRoles;
    }

    /**
     * Build role tree recursively
     *
     * @param array $role
     * @param array $childrenMap
     * @param array $allRoles
     * @return array
     */
    private function buildRoleTree(array $role, array $childrenMap, array $allRoles): array
    {
        $tree = $role;
        $tree['children'] = [];

        if (isset($childrenMap[$role['id']])) {
            foreach ($childrenMap[$role['id']] as $childId) {
                $childRole = $this->find($childId);
                if ($childRole) {
                    $tree['children'][] = $this->buildRoleTree($childRole, $childrenMap, $allRoles);
                }
            }
        }

        return $tree;
    }

    /**
     * Check if adding parent would create circular dependency
     *
     * @param int $childRoleId
     * @param int $parentRoleId
     * @return bool True if would create circular dependency
     */
    public function wouldCreateCircularDependency(int $childRoleId, int $parentRoleId): bool
    {
        // If child and parent are the same
        if ($childRoleId === $parentRoleId) {
            return true;
        }

        // Check if parentRole is a descendant of childRole
        $descendants = $this->getDescendantRoles($childRoleId);
        foreach ($descendants as $descendant) {
            if ($descendant['id'] === $parentRoleId) {
                return true;
            }
        }

        return false;
    }
}
