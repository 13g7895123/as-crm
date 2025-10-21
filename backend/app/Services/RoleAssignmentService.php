<?php

namespace App\Services;

use App\Models\RoleAssignmentModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use App\Models\AuditLogModel;

/**
 * Role Assignment Service
 *
 * Handles business logic for managing role assignments with time-based validity.
 */
class RoleAssignmentService
{
    protected RoleAssignmentModel $roleAssignmentModel;
    protected RoleModel $roleModel;
    protected UserModel $userModel;
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->roleAssignmentModel = new RoleAssignmentModel();
        $this->roleModel = new RoleModel();
        $this->userModel = new UserModel();
        $this->auditLogModel = new AuditLogModel();
    }

    /**
     * Assign a role to a user
     *
     * @param int $userId
     * @param int $roleId
     * @param int|null $assignedBy User who assigned the role
     * @param string|null $expiresAt Expiry date (optional)
     * @param array|null $scopeConstraints Scope constraints (optional)
     * @return array Created assignment
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function assignRole(
        int $userId,
        int $roleId,
        ?int $assignedBy = null,
        ?string $expiresAt = null,
        ?array $scopeConstraints = null
    ): array {
        // Validate user exists
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('使用者不存在');
        }

        // Validate role exists
        $role = $this->roleModel->find($roleId);
        if (!$role) {
            throw new \InvalidArgumentException('角色不存在');
        }

        // Validate expiry date if provided
        if ($expiresAt !== null) {
            $expiryTime = strtotime($expiresAt);
            if ($expiryTime === false) {
                throw new \InvalidArgumentException('過期時間格式不正確');
            }
            if ($expiryTime < time()) {
                throw new \InvalidArgumentException('過期時間必須晚於當前時間');
            }
        }

        // Check for duplicate assignment
        $existing = $this->roleAssignmentModel->getByUserAndRole($userId, $roleId);
        if ($existing) {
            // Check if existing assignment is expired
            if ($existing['expires_at'] === null || strtotime($existing['expires_at']) > time()) {
                throw new \RuntimeException('該使用者已被指派此角色');
            }
        }

        // Create assignment
        $data = [
            'user_id' => $userId,
            'role_id' => $roleId,
            'assigned_by' => $assignedBy,
            'assigned_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'scope_constraints' => $scopeConstraints,
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $assignmentId = $this->roleAssignmentModel->insert($data);

            // Log the assignment
            $this->auditLogModel->insert([
                'user_id' => $assignedBy ?? $userId,
                'action' => 'role_assigned',
                'resource_type' => 'role_assignment',
                'resource_id' => $assignmentId,
                'details' => json_encode([
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'role_name' => $role['name'],
                    'expires_at' => $expiresAt,
                ], JSON_UNESCAPED_UNICODE),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('角色指派失敗');
            }

            return $this->getAssignmentById($assignmentId);
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Revoke a role assignment
     *
     * @param int $assignmentId
     * @param int|null $revokedBy User who revoked the assignment
     * @return bool
     * @throws \RuntimeException
     */
    public function revokeAssignment(int $assignmentId, ?int $revokedBy = null): bool
    {
        $assignment = $this->roleAssignmentModel->find($assignmentId);
        if (!$assignment) {
            throw new \RuntimeException('角色指派不存在');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Delete assignment
            $this->roleAssignmentModel->delete($assignmentId);

            // Log the revocation
            $this->auditLogModel->insert([
                'user_id' => $revokedBy ?? $assignment['user_id'],
                'action' => 'role_revoked',
                'resource_type' => 'role_assignment',
                'resource_id' => $assignmentId,
                'details' => json_encode([
                    'user_id' => $assignment['user_id'],
                    'role_id' => $assignment['role_id'],
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
     * Extend or shorten role validity period
     *
     * @param int $assignmentId
     * @param string $newExpiryDate New expiry date
     * @param int|null $modifiedBy User who modified the assignment
     * @return array Updated assignment
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function extendRoleValidity(
        int $assignmentId,
        string $newExpiryDate,
        ?int $modifiedBy = null
    ): array {
        $assignment = $this->roleAssignmentModel->find($assignmentId);
        if (!$assignment) {
            throw new \RuntimeException('角色指派不存在');
        }

        // Validate new expiry date
        $expiryTime = strtotime($newExpiryDate);
        if ($expiryTime === false) {
            throw new \InvalidArgumentException('過期時間格式不正確');
        }
        if ($expiryTime < time()) {
            throw new \InvalidArgumentException('新的過期時間必須晚於當前時間');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Update expiry date
            $this->roleAssignmentModel->update($assignmentId, [
                'expires_at' => $newExpiryDate,
            ]);

            // Log the modification
            $this->auditLogModel->insert([
                'user_id' => $modifiedBy ?? $assignment['user_id'],
                'action' => 'role_validity_modified',
                'resource_type' => 'role_assignment',
                'resource_id' => $assignmentId,
                'details' => json_encode([
                    'user_id' => $assignment['user_id'],
                    'role_id' => $assignment['role_id'],
                    'old_expires_at' => $assignment['expires_at'],
                    'new_expires_at' => $newExpiryDate,
                ], JSON_UNESCAPED_UNICODE),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('更新失敗');
            }

            return $this->getAssignmentById($assignmentId);
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Bulk assign role to multiple users
     *
     * @param array $userIds Array of user IDs
     * @param int $roleId Role ID to assign
     * @param int|null $assignedBy User who assigned the role
     * @param string|null $expiresAt Expiry date (optional)
     * @param array|null $scopeConstraints Scope constraints (optional)
     * @return array Array of created assignments
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function bulkAssignRole(
        array $userIds,
        int $roleId,
        ?int $assignedBy = null,
        ?string $expiresAt = null,
        ?array $scopeConstraints = null
    ): array {
        if (empty($userIds)) {
            throw new \InvalidArgumentException('使用者ID列表不能為空');
        }

        // Validate role exists
        $role = $this->roleModel->find($roleId);
        if (!$role) {
            throw new \InvalidArgumentException('角色不存在');
        }

        // Validate expiry date if provided
        if ($expiresAt !== null) {
            $expiryTime = strtotime($expiresAt);
            if ($expiryTime === false) {
                throw new \InvalidArgumentException('過期時間格式不正確');
            }
            if ($expiryTime < time()) {
                throw new \InvalidArgumentException('過期時間必須晚於當前時間');
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $assignmentIds = $this->roleAssignmentModel->bulkAssign(
                $userIds,
                $roleId,
                $expiresAt,
                $assignedBy,
                $scopeConstraints
            );

            // Log the bulk assignment
            $this->auditLogModel->insert([
                'user_id' => $assignedBy ?? 0,
                'action' => 'role_bulk_assigned',
                'resource_type' => 'role_assignment',
                'resource_id' => null,
                'details' => json_encode([
                    'user_ids' => $userIds,
                    'role_id' => $roleId,
                    'role_name' => $role['name'],
                    'count' => count($assignmentIds),
                    'expires_at' => $expiresAt,
                ], JSON_UNESCAPED_UNICODE),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('批次指派失敗');
            }

            // Get created assignments
            return $this->roleAssignmentModel->whereIn('id', $assignmentIds)->findAll();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Get user's assigned roles
     *
     * @param int $userId
     * @param bool $includeExpired Whether to include expired assignments
     * @return array
     */
    public function getUserRoles(int $userId, bool $includeExpired = false): array
    {
        return $this->roleAssignmentModel->getUserRoles($userId, $includeExpired);
    }

    /**
     * Get role assignments expiring within specified days
     *
     * @param int $days Number of days (default: 7)
     * @return array
     */
    public function getExpiringRoles(int $days = 7): array
    {
        return $this->roleAssignmentModel->getExpiringRoles($days);
    }

    /**
     * Get expired role assignments
     *
     * @return array
     */
    public function getExpiredRoles(): array
    {
        return $this->roleAssignmentModel->getExpiredRoles();
    }

    /**
     * Clean expired role assignments
     *
     * @return int Number of deleted assignments
     */
    public function cleanExpiredRoles(): int
    {
        $expiredRoles = $this->getExpiredRoles();
        $count = 0;

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            foreach ($expiredRoles as $assignment) {
                $this->roleAssignmentModel->delete($assignment['id']);

                // Log the cleanup
                $this->auditLogModel->insert([
                    'user_id' => 0, // System user
                    'action' => 'role_expired_cleaned',
                    'resource_type' => 'role_assignment',
                    'resource_id' => $assignment['id'],
                    'details' => json_encode([
                        'user_id' => $assignment['user_id'],
                        'role_id' => $assignment['role_id'],
                        'role_name' => $assignment['name'] ?? '',
                        'expired_at' => $assignment['expires_at'],
                    ], JSON_UNESCAPED_UNICODE),
                    'ip_address' => null,
                ]);

                $count++;
            }

            $db->transComplete();

            return $db->transStatus() !== false ? $count : 0;
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Check if user has active role assignment
     *
     * @param int $userId
     * @param int $roleId
     * @return bool
     */
    public function userHasRole(int $userId, int $roleId): bool
    {
        return $this->roleAssignmentModel->userHasRole($userId, $roleId, true);
    }

    /**
     * Get assignment by ID
     *
     * @param int $assignmentId
     * @return array
     * @throws \RuntimeException
     */
    public function getAssignmentById(int $assignmentId): array
    {
        $assignment = $this->roleAssignmentModel->select('role_assignments.*, roles.name, roles.display_name, users.username')
            ->join('roles', 'roles.id = role_assignments.role_id')
            ->join('users', 'users.id = role_assignments.user_id')
            ->find($assignmentId);

        if (!$assignment) {
            throw new \RuntimeException('角色指派不存在');
        }

        return $assignment;
    }

    /**
     * Get all assignments with filters and pagination
     *
     * @param array $filters
     * @return array
     */
    public function getAssignments(array $filters = []): array
    {
        $assignments = $this->roleAssignmentModel->getAssignmentsWithFilters($filters);
        $total = $this->roleAssignmentModel->countWithFilters($filters);

        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;

        return [
            'data' => $assignments,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Check authorization for user based on role assignment and scope constraints
     *
     * @param int $userId
     * @param string $permissionName
     * @param array $context Resource context for condition evaluation
     * @return bool
     */
    public function checkAuthorization(int $userId, string $permissionName, array $context = []): bool
    {
        // Get user's active role assignments
        $assignments = $this->getUserRoles($userId, false);

        if (empty($assignments)) {
            return false;
        }

        // Check each assignment
        foreach ($assignments as $assignment) {
            // Check if scope constraints match
            if (!empty($assignment['scope_constraints'])) {
                $constraints = is_string($assignment['scope_constraints'])
                    ? json_decode($assignment['scope_constraints'], true)
                    : $assignment['scope_constraints'];

                if (!$this->matchesScopeConstraints($constraints, $context)) {
                    continue;
                }
            }

            // Check if role has the permission
            $rolePermissionModel = new \App\Models\RolePermissionModel();
            $permissionModel = new \App\Models\PermissionModel();

            $permission = $permissionModel->where('name', $permissionName)->first();
            if (!$permission) {
                continue;
            }

            $hasPermission = $rolePermissionModel->roleHasPermission($assignment['role_id'], $permission['id']);
            if ($hasPermission) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if context matches scope constraints
     *
     * @param array $constraints
     * @param array $context
     * @return bool
     */
    protected function matchesScopeConstraints(array $constraints, array $context): bool
    {
        foreach ($constraints as $key => $value) {
            if (!isset($context[$key])) {
                return false;
            }

            if (is_array($value)) {
                if (!in_array($context[$key], $value)) {
                    return false;
                }
            } else {
                if ($context[$key] !== $value) {
                    return false;
                }
            }
        }

        return true;
    }
}
