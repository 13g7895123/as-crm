/**
 * Permission Middleware
 *
 * Protects routes that require specific permissions.
 * Usage in pages:
 *
 * definePageMeta({
 *   middleware: 'permission',
 *   permission: 'role:view',  // Single permission
 *   // OR
 *   permissions: ['role:view', 'role:edit'],  // Any of these
 *   // OR
 *   requireAll: ['role:view', 'role:edit'],  // All required
 * })
 */

export default defineNuxtRouteMiddleware(async (to, from) => {
  const { user, isAuthenticated } = useAuth()
  const { can, canAny } = usePermissions()

  // Check if user is authenticated
  if (!isAuthenticated.value) {
    return navigateTo({
      path: '/login',
      query: { redirect: to.fullPath },
    })
  }

  // Get permission requirements from route meta
  const meta = to.meta

  // Single permission check
  if (meta.permission) {
    const [module, action] = String(meta.permission).split(':')

    const hasPermission = await can(`${module}:${action}`)

    if (!hasPermission) {
      return abortNavigation({
        statusCode: 403,
        statusMessage: '權限不足',
        message: `您沒有「${meta.permission}」權限來存取此頁面`,
      })
    }
  }

  // Multiple permissions (any) check
  if (meta.permissions && Array.isArray(meta.permissions)) {
    const hasAnyPermission = await canAny(meta.permissions as string[])

    if (!hasAnyPermission) {
      return abortNavigation({
        statusCode: 403,
        statusMessage: '權限不足',
        message: '您沒有權限來存取此頁面',
      })
    }
  }

  // Multiple permissions (all required) check
  if (meta.requireAll && Array.isArray(meta.requireAll)) {
    for (const permission of meta.requireAll as string[]) {
      const [module, action] = permission.split(':')
      const hasPermission = await can(`${module}:${action}`)

      if (!hasPermission) {
        return abortNavigation({
          statusCode: 403,
          statusMessage: '權限不足',
          message: `您沒有「${permission}」權限來存取此頁面`,
        })
      }
    }
  }

  // Role-based check (optional, for simple role checks)
  if (meta.roles && Array.isArray(meta.roles)) {
    const userRoles = user.value?.roles || []
    const hasRequiredRole = (meta.roles as string[]).some(role =>
      userRoles.some((r: any) => r.name === role || r === role)
    )

    if (!hasRequiredRole) {
      return abortNavigation({
        statusCode: 403,
        statusMessage: '角色權限不足',
        message: '您的角色沒有權限來存取此頁面',
      })
    }
  }

  // Admin only check
  if (meta.requireAdmin) {
    const isAdmin = user.value?.roles?.some((r: any) =>
      r.name === 'system_admin' || r === 'system_admin'
    )

    if (!isAdmin) {
      return abortNavigation({
        statusCode: 403,
        statusMessage: '需要管理員權限',
        message: '只有系統管理員可以存取此頁面',
      })
    }
  }

  // Permission check passed
  return
})
