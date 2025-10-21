<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Services\PermissionService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Permission Controller
 *
 * Handles REST API endpoints for permission management
 * Endpoints: GET /permissions, POST /permissions/check, GET /users/{userId}/permissions, GET /my-permissions
 */
class PermissionController extends BaseController
{
    protected PermissionService $permissionService;

    public function __construct()
    {
        $this->permissionService = new PermissionService();
    }

    /**
     * GET /api/v1/permissions
     *
     * Get all permissions grouped by resource
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        try {
            $filters = [
                'resource' => $this->request->getGet('module') ?? $this->request->getGet('resource'),
                'action'   => $this->request->getGet('action'),
            ];

            $permissions = $this->permissionService->getAllPermissions();

            return $this->respond([
                'data' => $permissions,
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得權限清單失敗: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/v1/permissions/check
     *
     * Check user permissions
     *
     * @return ResponseInterface
     */
    public function check(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate input
            if (empty($data['permissions']) || !is_array($data['permissions'])) {
                return $this->fail([
                    'error' => 'Validation Error',
                    'message' => '輸入資料驗證失敗',
                    'details' => [
                        'permissions' => ['權限清單為必填且必須為陣列'],
                    ],
                ], 422);
            }

            // Get user ID from JWT token (assumes auth middleware sets it)
            $userId = $this->request->user_id ?? null;

            if (!$userId) {
                return $this->failUnauthorized('請先登入系統');
            }

            $context = $data['resource_context'] ?? [];

            $result = $this->permissionService->checkPermissions($userId, $data['permissions'], $context);

            return $this->respond([
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('檢查權限失敗: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/v1/users/{userId}/permissions
     *
     * Get user's permissions
     *
     * @param int $userId
     * @return ResponseInterface
     */
    public function userPermissions(int $userId): ResponseInterface
    {
        try {
            // Check if current user has permission to view other users' permissions
            // (In real implementation, this would use permission checking)
            $currentUserId = $this->request->user_id ?? null;

            if (!$currentUserId) {
                return $this->failUnauthorized('請先登入系統');
            }

            // For now, allow users to only view their own permissions unless they're admin
            // This would be replaced with proper permission checking
            if ($currentUserId != $userId) {
                // Check if user has 'user_permission.view' permission
                // Simplified for now
            }

            $includeInherited = $this->request->getGet('include_inherited') !== 'false';

            $permissions = $this->permissionService->getUserPermissions($userId, $includeInherited);

            return $this->respond([
                'data' => $permissions,
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得使用者權限失敗: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/v1/my-permissions
     *
     * Get current user's permissions
     *
     * @return ResponseInterface
     */
    public function myPermissions(): ResponseInterface
    {
        try {
            $userId = $this->request->user_id ?? null;

            if (!$userId) {
                return $this->failUnauthorized('請先登入系統');
            }

            $permissions = $this->permissionService->getUserPermissions($userId, true);

            // Group permissions by module for better presentation
            $permissionsByModule = [];
            foreach ($permissions['permissions'] as $permission) {
                $resource = $permission['resource'];

                if (!isset($permissionsByModule[$resource])) {
                    $permissionsByModule[$resource] = [];
                }

                $permissionsByModule[$resource][] = [
                    'id' => $permission['id'],
                    'resource' => $permission['resource'],
                    'action' => $permission['action'],
                    'name' => $permission['description'] ?? $permission['name'],
                    'source' => $permission['source'],
                ];
            }

            // Get condition summary
            $conditionSummary = $this->buildConditionSummary($permissions['effective_condition_rules']);

            return $this->respond([
                'data' => [
                    'user_id' => $userId,
                    'user_name' => $this->request->user_name ?? '', // Assume set by auth middleware
                    'roles' => $permissions['roles'],
                    'permissions_by_module' => $permissionsByModule,
                    'condition_summary' => $conditionSummary,
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得當前使用者權限失敗: ' . $e->getMessage());
        }
    }

    /**
     * Build condition summary from condition rules
     *
     * @param array $rules
     * @return array
     */
    private function buildConditionSummary(array $rules): array
    {
        $summary = [
            'department' => [],
            'region' => [],
        ];

        foreach ($rules as $rule) {
            $conditionType = $rule['condition_type'];
            $value = is_string($rule['value']) ? json_decode($rule['value'], true) : $rule['value'];

            if ($conditionType === 'department') {
                $summary['department'][] = $value['value'] ?? $value;
            } elseif ($conditionType === 'region') {
                if (isset($value['values'])) {
                    $summary['region'] = array_merge($summary['region'], $value['values']);
                } else {
                    $summary['region'][] = $value['value'] ?? $value;
                }
            }
        }

        // Remove duplicates
        $summary['department'] = array_unique($summary['department']);
        $summary['region'] = array_unique($summary['region']);

        return $summary;
    }
}
