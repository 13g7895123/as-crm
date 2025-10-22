<?php

namespace App\Services;

use App\Libraries\PermissionChecker;
use App\Models\UserModel;
use CodeIgniter\Cache\CacheInterface;

/**
 * Authorization Service
 *
 * Core permission verification logic with caching and multi-role aggregation.
 * Implements the Maximum Permission Principle: if any role grants permission, access is allowed.
 *
 * Features:
 * - Permission checking with condition rules
 * - Permission caching (TTL: 30 minutes)
 * - Multi-role permission aggregation
 * - Cache invalidation on permission changes
 *
 * @package App\Services
 */
class AuthorizationService
{
    protected PermissionChecker $permissionChecker;
    protected UserModel $userModel;
    protected CacheInterface $cache;

    /**
     * Cache TTL in seconds (30 minutes)
     */
    protected int $cacheTTL = 1800;

    public function __construct()
    {
        $this->permissionChecker = new PermissionChecker();
        $this->userModel = new UserModel();
        $this->cache = \Config\Services::cache();
    }

    /**
     * Check if user is authorized for a specific action
     *
     * @param int $userId User ID
     * @param string $module Module name (e.g., 'customer', 'order')
     * @param string $action Action name (e.g., 'view', 'edit')
     * @param array $context Context data for condition evaluation
     * @param bool $useCache Whether to use cached permissions
     * @return bool
     */
    public function authorize(
        int $userId,
        string $module,
        string $action,
        array $context = [],
        bool $useCache = true
    ): bool {
        // Check cache first
        if ($useCache) {
            $cacheKey = $this->getPermissionCacheKey($userId, $module, $action, $context);
            $cached = $this->cache->get($cacheKey);

            if ($cached !== null) {
                return (bool)$cached;
            }
        }

        // Perform actual permission check
        $hasPermission = $this->permissionChecker->hasPermission($userId, $module, $action, $context);

        // Cache the result
        if ($useCache) {
            $this->cache->save($cacheKey, $hasPermission, $this->cacheTTL);
        }

        return $hasPermission;
    }

    /**
     * Check if user has any of the specified permissions
     *
     * @param int $userId User ID
     * @param array $permissions Array of 'module:action' strings or [module, action] arrays
     * @param array $context Context data
     * @return bool
     */
    public function authorizeAny(int $userId, array $permissions, array $context = []): bool
    {
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                [$module, $action] = explode(':', $permission);
            } else {
                [$module, $action] = $permission;
            }

            if ($this->authorize($userId, $module, $action, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the specified permissions
     *
     * @param int $userId User ID
     * @param array $permissions Array of 'module:action' strings or [module, action] arrays
     * @param array $context Context data
     * @return bool
     */
    public function authorizeAll(int $userId, array $permissions, array $context = []): bool
    {
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                [$module, $action] = explode(':', $permission);
            } else {
                [$module, $action] = $permission;
            }

            if (!$this->authorize($userId, $module, $action, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all permissions for a user (with caching)
     *
     * @param int $userId User ID
     * @param bool $useCache Whether to use cache
     * @return array Array of permissions
     */
    public function getUserPermissions(int $userId, bool $useCache = true): array
    {
        $cacheKey = "user_permissions_{$userId}";

        if ($useCache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $permissions = $this->permissionChecker->getUserPermissions($userId);

        if ($useCache) {
            $this->cache->save($cacheKey, $permissions, $this->cacheTTL);
        }

        return $permissions;
    }

    /**
     * Get user's permissions grouped by module (with caching)
     *
     * @param int $userId User ID
     * @param bool $useCache Whether to use cache
     * @return array Grouped permissions
     */
    public function getUserPermissionsGrouped(int $userId, bool $useCache = true): array
    {
        $cacheKey = "user_permissions_grouped_{$userId}";

        if ($useCache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $permissions = $this->permissionChecker->getUserPermissionsGrouped($userId);

        if ($useCache) {
            $this->cache->save($cacheKey, $permissions, $this->cacheTTL);
        }

        return $permissions;
    }

    /**
     * Clear permission cache for a specific user
     *
     * Call this when user's roles or permissions change.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function clearUserPermissionCache(int $userId): bool
    {
        // Clear all cache keys for this user
        $patterns = [
            "user_permissions_{$userId}",
            "user_permissions_grouped_{$userId}",
            "permission_{$userId}_*",
        ];

        $success = true;
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '*') !== false) {
                // Delete by pattern (if cache driver supports it)
                $success = $success && $this->clearCacheByPattern($pattern);
            } else {
                $success = $success && $this->cache->delete($pattern);
            }
        }

        return $success;
    }

    /**
     * Clear cache by pattern
     *
     * @param string $pattern Pattern with wildcard
     * @return bool
     */
    protected function clearCacheByPattern(string $pattern): bool
    {
        // Note: Not all cache drivers support pattern deletion
        // This is a best-effort approach

        try {
            // For Redis
            if (method_exists($this->cache, 'deleteMatching')) {
                return $this->cache->deleteMatching($pattern);
            }

            // For file-based cache, we'll need to manually scan and delete
            // This is less efficient but works as a fallback
            $cacheInfo = $this->cache->getCacheInfo();
            if (is_array($cacheInfo)) {
                $regex = '/' . str_replace('*', '.*', preg_quote($pattern, '/')) . '/';
                foreach ($cacheInfo as $key => $value) {
                    if (preg_match($regex, $key)) {
                        $this->cache->delete($key);
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Failed to clear cache by pattern: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear permission cache for all users
     *
     * Use this sparingly - only when global permission structure changes.
     *
     * @return bool
     */
    public function clearAllPermissionCache(): bool
    {
        return $this->clearCacheByPattern('permission_*') &&
               $this->clearCacheByPattern('user_permissions_*');
    }

    /**
     * Get data filter conditions for a user's permission
     *
     * Returns SQL WHERE conditions based on user's permission rules,
     * useful for filtering queries (e.g., "show only records user can access")
     *
     * @param int $userId User ID
     * @param string $module Module name
     * @param string $action Action name
     * @return array|null WHERE conditions or null if no restrictions
     */
    public function getDataFilterConditions(int $userId, string $module, string $action): ?array
    {
        $cacheKey = "data_filter_{$userId}_{$module}_{$action}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $conditions = $this->permissionChecker->getDataFilterConditions($userId, $module, $action);

        $this->cache->save($cacheKey, $conditions, $this->cacheTTL);

        return $conditions;
    }

    /**
     * Generate permission cache key
     *
     * @param int $userId User ID
     * @param string $module Module name
     * @param string $action Action name
     * @param array $context Context data
     * @return string
     */
    protected function getPermissionCacheKey(int $userId, string $module, string $action, array $context): string
    {
        $contextHash = empty($context) ? 'no_context' : md5(json_encode($context));
        return "permission_{$userId}_{$module}_{$action}_{$contextHash}";
    }

    /**
     * Preload permissions for a user into cache
     *
     * Useful to call on user login to warm up the cache.
     *
     * @param int $userId User ID
     * @return array Loaded permissions
     */
    public function preloadUserPermissions(int $userId): array
    {
        // Load and cache basic permissions
        $permissions = $this->getUserPermissions($userId, false);
        $grouped = $this->getUserPermissionsGrouped($userId, false);

        // Cache them
        $this->cache->save("user_permissions_{$userId}", $permissions, $this->cacheTTL);
        $this->cache->save("user_permissions_grouped_{$userId}", $grouped, $this->cacheTTL);

        return $permissions;
    }

    /**
     * Check if user is a system administrator
     *
     * @param int $userId User ID
     * @return bool
     */
    public function isSystemAdmin(int $userId): bool
    {
        $cacheKey = "is_admin_{$userId}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return (bool)$cached;
        }

        // Check if user has 'system_admin' role or full access
        $roleAssignmentModel = new \App\Models\RoleAssignmentModel();
        $assignments = $roleAssignmentModel->getUserRoles($userId, false);

        $isAdmin = false;
        foreach ($assignments as $assignment) {
            if ($assignment['name'] === 'system_admin') {
                $isAdmin = true;
                break;
            }
        }

        $this->cache->save($cacheKey, $isAdmin, $this->cacheTTL);

        return $isAdmin;
    }

    /**
     * Get human-readable permission summary for a user
     *
     * @param int $userId User ID
     * @return array Summary with roles and permissions
     */
    public function getPermissionSummary(int $userId): array
    {
        $roleAssignmentModel = new \App\Models\RoleAssignmentModel();
        $assignments = $roleAssignmentModel->getUserRoles($userId, false);

        $permissions = $this->getUserPermissionsGrouped($userId);

        return [
            'user_id' => $userId,
            'roles' => array_map(function ($assignment) {
                return [
                    'id' => $assignment['role_id'],
                    'name' => $assignment['name'],
                    'display_name' => $assignment['display_name'],
                    'expires_at' => $assignment['expires_at'] ?? null,
                ];
            }, $assignments),
            'permissions' => $permissions,
            'is_admin' => $this->isSystemAdmin($userId),
        ];
    }
}
