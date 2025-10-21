<?php

/**
 * Permission Helper Functions
 *
 * Convenient helper functions for permission checking and authorization.
 */

use App\Services\PermissionService;
use App\Services\AuditLogService;

if (!function_exists('can')) {
    /**
     * Check if current user has permission
     *
     * @param string $permissionName Permission name (e.g., 'customer.view')
     * @param array $context Resource context for condition evaluation
     * @return bool
     */
    function can(string $permissionName, array $context = []): bool
    {
        $userId = get_current_user_id();

        if (!$userId) {
            return false;
        }

        $permissionService = new PermissionService();
        return $permissionService->userHasPermission($userId, $permissionName, $context);
    }
}

if (!function_exists('cannot')) {
    /**
     * Check if current user does NOT have permission
     *
     * @param string $permissionName Permission name
     * @param array $context Resource context
     * @return bool
     */
    function cannot(string $permissionName, array $context = []): bool
    {
        return !can($permissionName, $context);
    }
}

if (!function_exists('can_any')) {
    /**
     * Check if current user has ANY of the specified permissions
     *
     * @param array $permissionNames Array of permission names
     * @param array $context Resource context
     * @return bool
     */
    function can_any(array $permissionNames, array $context = []): bool
    {
        foreach ($permissionNames as $permission) {
            if (can($permission, $context)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('can_all')) {
    /**
     * Check if current user has ALL of the specified permissions
     *
     * @param array $permissionNames Array of permission names
     * @param array $context Resource context
     * @return bool
     */
    function can_all(array $permissionNames, array $context = []): bool
    {
        foreach ($permissionNames as $permission) {
            if (!can($permission, $context)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('authorize')) {
    /**
     * Authorize an action or throw exception
     *
     * @param string $permissionName Permission name
     * @param array $context Resource context
     * @param string|null $message Custom error message
     * @throws \RuntimeException
     * @return void
     */
    function authorize(string $permissionName, array $context = [], ?string $message = null): void
    {
        if (!can($permissionName, $context)) {
            throw new \RuntimeException($message ?? '您沒有權限執行此操作');
        }
    }
}

if (!function_exists('get_current_user_id')) {
    /**
     * Get current authenticated user ID
     * TODO: Replace with actual JWT token validation
     *
     * @return int|null
     */
    function get_current_user_id(): ?int
    {
        // Try session first
        $session = session();
        $userId = $session->get('user_id');

        if ($userId) {
            return (int)$userId;
        }

        // Try JWT token from request
        $request = service('request');
        $authHeader = $request->getHeader('Authorization');

        if ($authHeader) {
            $token = str_replace('Bearer ', '', $authHeader->getValue());
            // TODO: Decode JWT and get user ID
            // For now, return mock user ID
            return 1;
        }

        return null;
    }
}

if (!function_exists('get_user_permissions')) {
    /**
     * Get current user's permissions
     *
     * @param bool $includeInherited Include inherited permissions from parent roles
     * @return array
     */
    function get_user_permissions(bool $includeInherited = true): array
    {
        $userId = get_current_user_id();

        if (!$userId) {
            return [];
        }

        $permissionService = new PermissionService();
        return $permissionService->getUserPermissions($userId, $includeInherited);
    }
}

if (!function_exists('audit_log')) {
    /**
     * Create an audit log entry
     *
     * @param string $action Action performed
     * @param string $resourceType Resource type (e.g., 'role', 'permission', 'user')
     * @param int|null $resourceId Resource ID
     * @param array|string|null $details Additional details
     * @return int Log ID
     */
    function audit_log(
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        $details = null
    ): int {
        $userId = get_current_user_id();
        $auditLogService = new AuditLogService();

        if ($userId) {
            return $auditLogService->logUserAction($userId, $action, $resourceType, $resourceId, $details);
        } else {
            return $auditLogService->logSystemAction($action, $resourceType, $resourceId, $details);
        }
    }
}

if (!function_exists('format_permission_name')) {
    /**
     * Format permission name for display
     *
     * @param string $permissionName Permission name (e.g., 'customer.view')
     * @return string Formatted name (e.g., '查看客戶')
     */
    function format_permission_name(string $permissionName): string
    {
        $actionMap = [
            'view' => '查看',
            'create' => '建立',
            'edit' => '編輯',
            'update' => '更新',
            'delete' => '刪除',
            'export' => '匯出',
            'import' => '匯入',
            'assign' => '指派',
            'approve' => '核准',
            'reject' => '拒絕',
        ];

        $resourceMap = [
            'customer' => '客戶',
            'order' => '訂單',
            'role' => '角色',
            'permission' => '權限',
            'user' => '使用者',
            'report' => '報表',
            'audit_log' => '審計記錄',
        ];

        $parts = explode('.', $permissionName);

        if (count($parts) !== 2) {
            return $permissionName;
        }

        [$resource, $action] = $parts;

        $resourceText = $resourceMap[$resource] ?? $resource;
        $actionText = $actionMap[$action] ?? $action;

        return $actionText . $resourceText;
    }
}

if (!function_exists('check_permissions')) {
    /**
     * Check multiple permissions at once
     *
     * @param array $permissionNames Array of permission names
     * @param array $context Resource context
     * @return array Associative array of permission => bool
     */
    function check_permissions(array $permissionNames, array $context = []): array
    {
        $userId = get_current_user_id();

        if (!$userId) {
            return array_fill_keys($permissionNames, false);
        }

        $permissionService = new PermissionService();
        return $permissionService->checkPermissions($userId, $permissionNames, $context);
    }
}
