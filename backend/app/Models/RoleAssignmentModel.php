<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Role Assignment Model
 *
 * Manages role assignments to users with time-based validity and scope constraints.
 *
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property int|null $assigned_by
 * @property string $assigned_at
 * @property string|null $expires_at
 * @property array|null $scope_constraints
 * @property string $created_at
 * @property string $updated_at
 */
class RoleAssignmentModel extends Model
{
    protected $table            = 'role_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'role_id',
        'assigned_by',
        'assigned_at',
        'expires_at',
        'scope_constraints',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'role_id' => 'required|is_natural_no_zero',
        'assigned_by' => 'permit_empty|is_natural_no_zero',
        'expires_at' => 'permit_empty|valid_date',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => '使用者ID為必填',
            'is_natural_no_zero' => '使用者ID必須為正整數',
        ],
        'role_id' => [
            'required' => '角色ID為必填',
            'is_natural_no_zero' => '角色ID必須為正整數',
        ],
        'expires_at' => [
            'valid_date' => '過期時間格式不正確',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['validateExpiryDate', 'encodeScopeConstraints', 'checkDuplicate'];
    protected $beforeUpdate   = ['validateExpiryDate', 'encodeScopeConstraints'];
    protected $afterFind      = ['decodeScopeConstraints'];

    /**
     * Validate that expiry date is in the future
     */
    protected function validateExpiryDate(array $data): array
    {
        if (isset($data['data']['expires_at']) && $data['data']['expires_at'] !== null) {
            $expiryTime = strtotime($data['data']['expires_at']);
            if ($expiryTime < time()) {
                throw new \InvalidArgumentException('過期時間必須晚於當前時間');
            }
        }

        return $data;
    }

    /**
     * Encode scope constraints to JSON
     */
    protected function encodeScopeConstraints(array $data): array
    {
        if (isset($data['data']['scope_constraints'])) {
            if (is_array($data['data']['scope_constraints'])) {
                $data['data']['scope_constraints'] = json_encode(
                    $data['data']['scope_constraints'],
                    JSON_UNESCAPED_UNICODE
                );
            }
        }

        return $data;
    }

    /**
     * Decode scope constraints from JSON
     */
    protected function decodeScopeConstraints(array $data): array
    {
        if ($data['singleton'] ?? false) {
            if (isset($data['data']['scope_constraints']) && is_string($data['data']['scope_constraints'])) {
                $decoded = json_decode($data['data']['scope_constraints'], true);
                $data['data']['scope_constraints'] = $decoded !== null ? $decoded : null;
            }
        } else {
            foreach ($data['data'] as &$row) {
                if (isset($row['scope_constraints']) && is_string($row['scope_constraints'])) {
                    $decoded = json_decode($row['scope_constraints'], true);
                    $row['scope_constraints'] = $decoded !== null ? $decoded : null;
                }
            }
        }

        return $data;
    }

    /**
     * Check for duplicate assignments
     */
    protected function checkDuplicate(array $data): array
    {
        if (isset($data['data']['user_id']) && isset($data['data']['role_id'])) {
            $existing = $this->where('user_id', $data['data']['user_id'])
                ->where('role_id', $data['data']['role_id'])
                ->where('(expires_at IS NULL OR expires_at > NOW())')
                ->first();

            if ($existing) {
                throw new \RuntimeException('該使用者已被指派此角色');
            }
        }

        return $data;
    }

    /**
     * Get user's role assignments
     *
     * @param int $userId
     * @param bool $includeExpired Whether to include expired assignments
     * @return array
     */
    public function getUserRoles(int $userId, bool $includeExpired = false): array
    {
        $builder = $this->select('role_assignments.*, roles.name, roles.display_name, roles.description')
            ->join('roles', 'roles.id = role_assignments.role_id')
            ->where('role_assignments.user_id', $userId);

        if (!$includeExpired) {
            $builder->groupStart()
                ->where('role_assignments.expires_at IS NULL')
                ->orWhere('role_assignments.expires_at >', date('Y-m-d H:i:s'))
                ->groupEnd();
        }

        return $builder->findAll();
    }

    /**
     * Get role assignments expiring within specified days
     *
     * @param int $days Number of days
     * @return array
     */
    public function getExpiringRoles(int $days = 7): array
    {
        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        return $this->select('role_assignments.*, roles.name, roles.display_name, users.username, users.email')
            ->join('roles', 'roles.id = role_assignments.role_id')
            ->join('users', 'users.id = role_assignments.user_id')
            ->where('role_assignments.expires_at IS NOT NULL')
            ->where('role_assignments.expires_at >', $startDate)
            ->where('role_assignments.expires_at <=', $endDate)
            ->orderBy('role_assignments.expires_at', 'ASC')
            ->findAll();
    }

    /**
     * Get expired role assignments
     *
     * @return array
     */
    public function getExpiredRoles(): array
    {
        return $this->select('role_assignments.*, roles.name, roles.display_name, users.username')
            ->join('roles', 'roles.id = role_assignments.role_id')
            ->join('users', 'users.id = role_assignments.user_id')
            ->where('role_assignments.expires_at IS NOT NULL')
            ->where('role_assignments.expires_at <', date('Y-m-d H:i:s'))
            ->findAll();
    }

    /**
     * Check if an assignment is expired
     *
     * @param int $assignmentId
     * @return bool
     */
    public function isExpired(int $assignmentId): bool
    {
        $assignment = $this->find($assignmentId);

        if (!$assignment) {
            return false;
        }

        if ($assignment['expires_at'] === null) {
            return false;
        }

        return strtotime($assignment['expires_at']) < time();
    }

    /**
     * Extend or shorten role validity period
     *
     * @param int $assignmentId
     * @param string $newExpiryDate
     * @return bool
     */
    public function updateExpiry(int $assignmentId, string $newExpiryDate): bool
    {
        $expiryTime = strtotime($newExpiryDate);
        if ($expiryTime < time()) {
            throw new \InvalidArgumentException('新的過期時間必須晚於當前時間');
        }

        return $this->update($assignmentId, ['expires_at' => $newExpiryDate]);
    }

    /**
     * Bulk assign role to multiple users
     *
     * @param array $userIds Array of user IDs
     * @param int $roleId Role ID to assign
     * @param string|null $expiresAt Expiry date (optional)
     * @param int|null $assignedBy User who assigned the role
     * @param array|null $scopeConstraints Scope constraints (optional)
     * @return array Array of created assignment IDs
     */
    public function bulkAssign(
        array $userIds,
        int $roleId,
        ?string $expiresAt = null,
        ?int $assignedBy = null,
        ?array $scopeConstraints = null
    ): array {
        $db = \Config\Database::connect();
        $db->transStart();

        $assignmentIds = [];

        foreach ($userIds as $userId) {
            // Check for existing assignment
            $existing = $this->where('user_id', $userId)
                ->where('role_id', $roleId)
                ->where('(expires_at IS NULL OR expires_at > NOW())')
                ->first();

            if ($existing) {
                // Skip duplicate
                continue;
            }

            $data = [
                'user_id' => $userId,
                'role_id' => $roleId,
                'assigned_by' => $assignedBy,
                'expires_at' => $expiresAt,
                'scope_constraints' => $scopeConstraints,
            ];

            // Manually validate expiry date
            if ($expiresAt !== null && strtotime($expiresAt) < time()) {
                throw new \InvalidArgumentException('過期時間必須晚於當前時間');
            }

            // Skip the beforeInsert checkDuplicate callback by using query builder
            $builder = $db->table($this->table);
            $builder->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
                'assigned_by' => $assignedBy,
                'assigned_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt,
                'scope_constraints' => $scopeConstraints ? json_encode($scopeConstraints, JSON_UNESCAPED_UNICODE) : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $assignmentIds[] = $db->insertID();
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException('批次指派失敗');
        }

        return $assignmentIds;
    }

    /**
     * Delete expired role assignments
     *
     * @return int Number of deleted assignments
     */
    public function deleteExpired(): int
    {
        return $this->where('expires_at IS NOT NULL')
            ->where('expires_at <', date('Y-m-d H:i:s'))
            ->delete();
    }

    /**
     * Check if user has role assignment
     *
     * @param int $userId
     * @param int $roleId
     * @param bool $checkExpiry Whether to check if assignment is expired
     * @return bool
     */
    public function userHasRole(int $userId, int $roleId, bool $checkExpiry = true): bool
    {
        $builder = $this->where('user_id', $userId)
            ->where('role_id', $roleId);

        if ($checkExpiry) {
            $builder->groupStart()
                ->where('expires_at IS NULL')
                ->orWhere('expires_at >', date('Y-m-d H:i:s'))
                ->groupEnd();
        }

        return $builder->first() !== null;
    }

    /**
     * Get assignment by user and role
     *
     * @param int $userId
     * @param int $roleId
     * @return array|null
     */
    public function getByUserAndRole(int $userId, int $roleId): ?array
    {
        return $this->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->first();
    }

    /**
     * Revoke role from user
     *
     * @param int $userId
     * @param int $roleId
     * @return bool
     */
    public function revokeRole(int $userId, int $roleId): bool
    {
        return $this->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete();
    }

    /**
     * Get all assignments with filters
     *
     * @param array $filters
     * @return array
     */
    public function getAssignmentsWithFilters(array $filters = []): array
    {
        $builder = $this->select('role_assignments.*, roles.name as role_name, roles.display_name as role_display_name, users.username, users.full_name')
            ->join('roles', 'roles.id = role_assignments.role_id')
            ->join('users', 'users.id = role_assignments.user_id');

        // Filter by user
        if (!empty($filters['user_id'])) {
            $builder->where('role_assignments.user_id', $filters['user_id']);
        }

        // Filter by role
        if (!empty($filters['role_id'])) {
            $builder->where('role_assignments.role_id', $filters['role_id']);
        }

        // Filter by expiry status
        if (isset($filters['include_expired']) && $filters['include_expired'] === false) {
            $builder->groupStart()
                ->where('role_assignments.expires_at IS NULL')
                ->orWhere('role_assignments.expires_at >', date('Y-m-d H:i:s'))
                ->groupEnd();
        }

        // Filter expiring soon
        if (!empty($filters['expiring_days'])) {
            $days = (int)$filters['expiring_days'];
            $startDate = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));

            $builder->where('role_assignments.expires_at IS NOT NULL')
                ->where('role_assignments.expires_at >', $startDate)
                ->where('role_assignments.expires_at <=', $endDate);
        }

        // Pagination
        if (!empty($filters['per_page'])) {
            $page = $filters['page'] ?? 1;
            $perPage = (int)$filters['per_page'];
            $offset = ($page - 1) * $perPage;
            $builder->limit($perPage, $offset);
        }

        // Sorting
        $sortBy = $filters['sort'] ?? 'role_assignments.created_at';
        $order = $filters['order'] ?? 'DESC';
        $builder->orderBy($sortBy, $order);

        return $builder->findAll();
    }

    /**
     * Get total count with filters
     *
     * @param array $filters
     * @return int
     */
    public function countWithFilters(array $filters = []): int
    {
        $builder = $this->builder();

        // Filter by user
        if (!empty($filters['user_id'])) {
            $builder->where('user_id', $filters['user_id']);
        }

        // Filter by role
        if (!empty($filters['role_id'])) {
            $builder->where('role_id', $filters['role_id']);
        }

        // Filter by expiry status
        if (isset($filters['include_expired']) && $filters['include_expired'] === false) {
            $builder->groupStart()
                ->where('expires_at IS NULL')
                ->orWhere('expires_at >', date('Y-m-d H:i:s'))
                ->groupEnd();
        }

        return $builder->countAllResults();
    }
}
