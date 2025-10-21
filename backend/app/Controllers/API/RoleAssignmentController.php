<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\RoleAssignmentService;

/**
 * Role Assignment API Controller
 *
 * Handles role assignment operations with time-based validity.
 */
class RoleAssignmentController extends ResourceController
{
    protected $modelName = 'App\Models\RoleAssignmentModel';
    protected $format    = 'json';
    protected RoleAssignmentService $roleAssignmentService;

    public function __construct()
    {
        $this->roleAssignmentService = new RoleAssignmentService();
    }

    /**
     * Get all role assignments with filters
     *
     * GET /api/v1/role-assignments
     *
     * Query parameters:
     * - user_id: Filter by user
     * - role_id: Filter by role
     * - include_expired: Include expired assignments (default: true)
     * - expiring_days: Get roles expiring within N days
     * - page: Page number (default: 1)
     * - per_page: Items per page (default: 20)
     * - sort: Sort field (default: created_at)
     * - order: Sort order (ASC/DESC, default: DESC)
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        try {
            $filters = [
                'user_id' => $this->request->getGet('user_id'),
                'role_id' => $this->request->getGet('role_id'),
                'include_expired' => $this->request->getGet('include_expired') !== 'false',
                'expiring_days' => $this->request->getGet('expiring_days'),
                'page' => $this->request->getGet('page') ?? 1,
                'per_page' => $this->request->getGet('per_page') ?? 20,
                'sort' => $this->request->getGet('sort') ?? 'role_assignments.created_at',
                'order' => $this->request->getGet('order') ?? 'DESC',
            ];

            $result = $this->roleAssignmentService->getAssignments($filters);

            return $this->respond([
                'data' => $result['data'],
                'meta' => $result['meta'],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get role assignments: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取角色指派失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new role assignment
     *
     * POST /api/v1/role-assignments
     *
     * Request body:
     * {
     *   "user_id": 1,
     *   "role_id": 2,
     *   "expires_at": "2025-12-31 23:59:59", // optional
     *   "scope_constraints": { // optional
     *     "department": "業務部",
     *     "region": "華東"
     *   }
     * }
     *
     * @return ResponseInterface
     */
    public function create(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            if (empty($data['user_id']) || empty($data['role_id'])) {
                return $this->fail([
                    'error' => '驗證失敗',
                    'errors' => [
                        'user_id' => empty($data['user_id']) ? ['使用者ID為必填'] : [],
                        'role_id' => empty($data['role_id']) ? ['角色ID為必填'] : [],
                    ],
                ], 422);
            }

            // Get current user ID from JWT token (mock for now)
            $assignedBy = $this->getCurrentUserId();

            $assignment = $this->roleAssignmentService->assignRole(
                (int)$data['user_id'],
                (int)$data['role_id'],
                $assignedBy,
                $data['expires_at'] ?? null,
                $data['scope_constraints'] ?? null
            );

            return $this->respondCreated([
                'data' => $assignment,
                'message' => '角色指派成功',
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->fail([
                'error' => '驗證失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return $this->fail([
                'error' => '指派失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            log_message('error', 'Failed to create role assignment: ' . $e->getMessage());
            return $this->fail([
                'error' => '角色指派失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific role assignment
     *
     * GET /api/v1/role-assignments/{id}
     *
     * @param int|string $id Assignment ID
     * @return ResponseInterface
     */
    public function show($id = null): ResponseInterface
    {
        try {
            $assignment = $this->roleAssignmentService->getAssignmentById((int)$id);
            return $this->respond(['data' => $assignment]);
        } catch (\RuntimeException $e) {
            return $this->failNotFound([
                'error' => '角色指派不存在',
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get role assignment: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a role assignment (revoke)
     *
     * DELETE /api/v1/role-assignments/{id}
     *
     * @param int|string $id Assignment ID
     * @return ResponseInterface
     */
    public function delete($id = null): ResponseInterface
    {
        try {
            $revokedBy = $this->getCurrentUserId();
            $this->roleAssignmentService->revokeAssignment((int)$id, $revokedBy);

            return $this->respond([
                'message' => '角色撤銷成功',
            ]);
        } catch (\RuntimeException $e) {
            return $this->failNotFound([
                'error' => '角色指派不存在',
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to revoke role assignment: ' . $e->getMessage());
            return $this->fail([
                'error' => '撤銷失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extend or shorten role validity period
     *
     * PUT /api/v1/role-assignments/{id}/extend
     *
     * Request body:
     * {
     *   "expires_at": "2025-12-31 23:59:59"
     * }
     *
     * @param int|string $id Assignment ID
     * @return ResponseInterface
     */
    public function extend($id = null): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            if (empty($data['expires_at'])) {
                return $this->fail([
                    'error' => '驗證失敗',
                    'errors' => [
                        'expires_at' => ['新的過期時間為必填'],
                    ],
                ], 422);
            }

            $modifiedBy = $this->getCurrentUserId();
            $assignment = $this->roleAssignmentService->extendRoleValidity(
                (int)$id,
                $data['expires_at'],
                $modifiedBy
            );

            return $this->respond([
                'data' => $assignment,
                'message' => '有效期更新成功',
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->fail([
                'error' => '驗證失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return $this->failNotFound([
                'error' => '角色指派不存在',
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to extend role validity: ' . $e->getMessage());
            return $this->fail([
                'error' => '更新失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk assign role to multiple users
     *
     * POST /api/v1/role-assignments/bulk
     *
     * Request body:
     * {
     *   "user_ids": [1, 2, 3],
     *   "role_id": 2,
     *   "expires_at": "2025-12-31 23:59:59", // optional
     *   "scope_constraints": {} // optional
     * }
     *
     * @return ResponseInterface
     */
    public function bulk(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            if (empty($data['user_ids']) || !is_array($data['user_ids']) || empty($data['role_id'])) {
                return $this->fail([
                    'error' => '驗證失敗',
                    'errors' => [
                        'user_ids' => empty($data['user_ids']) || !is_array($data['user_ids'])
                            ? ['使用者ID列表為必填且必須為陣列']
                            : [],
                        'role_id' => empty($data['role_id']) ? ['角色ID為必填'] : [],
                    ],
                ], 422);
            }

            $assignedBy = $this->getCurrentUserId();
            $assignments = $this->roleAssignmentService->bulkAssignRole(
                $data['user_ids'],
                (int)$data['role_id'],
                $assignedBy,
                $data['expires_at'] ?? null,
                $data['scope_constraints'] ?? null
            );

            return $this->respondCreated([
                'data' => $assignments,
                'message' => '批次指派成功',
                'count' => count($assignments),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->fail([
                'error' => '驗證失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return $this->fail([
                'error' => '指派失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            log_message('error', 'Failed to bulk assign role: ' . $e->getMessage());
            return $this->fail([
                'error' => '批次指派失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get roles expiring within specified days
     *
     * GET /api/v1/role-assignments/expiring
     *
     * Query parameters:
     * - days: Number of days (default: 7)
     *
     * @return ResponseInterface
     */
    public function expiring(): ResponseInterface
    {
        try {
            $days = (int)($this->request->getGet('days') ?? 7);
            $expiringRoles = $this->roleAssignmentService->getExpiringRoles($days);

            return $this->respond([
                'data' => $expiringRoles,
                'meta' => [
                    'days' => $days,
                    'count' => count($expiringRoles),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get expiring roles: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's assigned roles
     *
     * GET /api/v1/users/{userId}/roles
     *
     * Query parameters:
     * - include_expired: Include expired assignments (default: false)
     *
     * @param int|string $userId User ID
     * @return ResponseInterface
     */
    public function userRoles($userId = null): ResponseInterface
    {
        try {
            $includeExpired = $this->request->getGet('include_expired') === 'true';
            $roles = $this->roleAssignmentService->getUserRoles((int)$userId, $includeExpired);

            return $this->respond([
                'data' => $roles,
                'meta' => [
                    'user_id' => (int)$userId,
                    'count' => count($roles),
                    'include_expired' => $includeExpired,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get user roles: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取使用者角色失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check user authorization
     *
     * POST /api/v1/role-assignments/check-authorization
     *
     * Request body:
     * {
     *   "user_id": 1,
     *   "permission": "customer.view",
     *   "context": {
     *     "department": "業務部",
     *     "region": "華東"
     *   }
     * }
     *
     * @return ResponseInterface
     */
    public function checkAuthorization(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            if (empty($data['user_id']) || empty($data['permission'])) {
                return $this->fail([
                    'error' => '驗證失敗',
                    'errors' => [
                        'user_id' => empty($data['user_id']) ? ['使用者ID為必填'] : [],
                        'permission' => empty($data['permission']) ? ['權限名稱為必填'] : [],
                    ],
                ], 422);
            }

            $authorized = $this->roleAssignmentService->checkAuthorization(
                (int)$data['user_id'],
                $data['permission'],
                $data['context'] ?? []
            );

            return $this->respond([
                'authorized' => $authorized,
                'user_id' => (int)$data['user_id'],
                'permission' => $data['permission'],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to check authorization: ' . $e->getMessage());
            return $this->fail([
                'error' => '檢查授權失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current user ID from JWT token
     * TODO: Implement proper JWT token validation
     *
     * @return int|null
     */
    protected function getCurrentUserId(): ?int
    {
        // Mock implementation - replace with actual JWT validation
        return 1;
    }
}
