import { ref, computed } from 'vue'
import { usePermissionsStore, type PermissionGroup, type Permission } from '~/stores/permissions'
import { useAuthStore } from '~/stores/auth'

/**
 * usePermissions Composable
 *
 * Provides methods for permission management including:
 * - getPermissions: Fetch all permissions (with caching)
 * - can: Check if user has a specific permission
 * - canAny: Check if user has any of the specified permissions
 * - checkPermissions: Check multiple permissions at once
 */
export function usePermissions() {
  const permissionsStore = usePermissionsStore()
  const authStore = useAuthStore()
  const config = useRuntimeConfig()

  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed properties from store
  const permissions = computed(() => permissionsStore.permissions)
  const allPermissions = computed(() => permissionsStore.allPermissions)
  const isCacheValid = computed(() => permissionsStore.isCacheValid)

  /**
   * Get API base URL
   */
  const getApiUrl = () => {
    return config.public.apiBaseUrl || 'http://localhost:8080/api/v1'
  }

  /**
   * Get authorization headers
   */
  const getHeaders = () => {
    const token = authStore.accessToken
    return {
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : '',
    }
  }

  /**
   * Fetch all permissions (uses cache if valid)
   */
  const getPermissions = async (forceRefresh: boolean = false): Promise<PermissionGroup[]> => {
    // Return cached data if valid and not forcing refresh
    if (!forceRefresh && permissionsStore.isCacheValid && permissionsStore.isLoaded) {
      return permissionsStore.permissions
    }

    loading.value = true
    error.value = null
    permissionsStore.setLoading(true)
    permissionsStore.clearError()

    try {
      const url = `${getApiUrl()}/permissions`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to fetch permissions')
      }

      const data = await response.json()
      const permissionGroups = data.data || []

      permissionsStore.setPermissions(permissionGroups)
      return permissionGroups
    } catch (err: any) {
      error.value = err.message
      permissionsStore.setError(err.message)
      throw err
    } finally {
      loading.value = false
      permissionsStore.setLoading(false)
    }
  }

  /**
   * Check if current user has a specific permission
   *
   * @param permissionName - Permission name (e.g., 'customer.view')
   * @param context - Optional context for conditional permissions
   */
  const can = async (permissionName: string, context: Record<string, any> = {}): Promise<boolean> => {
    try {
      const url = `${getApiUrl()}/permissions/check`
      const response = await fetch(url, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          permissions: [permissionName],
          resource_context: context,
        }),
      })

      if (!response.ok) {
        if (response.status === 401) {
          return false // Not authenticated
        }
        return false
      }

      const data = await response.json()
      return data.data[permissionName] || false
    } catch (err: any) {
      console.error('Permission check failed:', err)
      return false
    }
  }

  /**
   * Check if current user has any of the specified permissions
   *
   * @param permissionNames - Array of permission names
   * @param context - Optional context for conditional permissions
   */
  const canAny = async (permissionNames: string[], context: Record<string, any> = {}): Promise<boolean> => {
    try {
      const url = `${getApiUrl()}/permissions/check`
      const response = await fetch(url, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          permissions: permissionNames,
          resource_context: context,
        }),
      })

      if (!response.ok) {
        return false
      }

      const data = await response.json()
      const results = data.data

      // Return true if user has any of the permissions
      return permissionNames.some(name => results[name] === true)
    } catch (err: any) {
      console.error('Permission check failed:', err)
      return false
    }
  }

  /**
   * Check multiple permissions at once
   *
   * @param permissionNames - Array of permission names
   * @param context - Optional context for conditional permissions
   * @returns Object with permission names as keys and boolean values
   */
  const checkPermissions = async (
    permissionNames: string[],
    context: Record<string, any> = {}
  ): Promise<Record<string, boolean>> => {
    try {
      const url = `${getApiUrl()}/permissions/check`
      const response = await fetch(url, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          permissions: permissionNames,
          resource_context: context,
        }),
      })

      if (!response.ok) {
        // Return all false if request fails
        const result: Record<string, boolean> = {}
        permissionNames.forEach(name => {
          result[name] = false
        })
        return result
      }

      const data = await response.json()
      return data.data
    } catch (err: any) {
      console.error('Permission check failed:', err)
      // Return all false on error
      const result: Record<string, boolean> = {}
      permissionNames.forEach(name => {
        result[name] = false
      })
      return result
    }
  }

  /**
   * Get permissions by resource
   */
  const getPermissionsByResource = (resource: string): Permission[] => {
    return permissionsStore.getPermissionsByResource(resource)
  }

  /**
   * Get permission by ID
   */
  const getPermissionById = (id: number): Permission | undefined => {
    return permissionsStore.getPermissionById(id)
  }

  /**
   * Get permissions by IDs
   */
  const getPermissionsByIds = (ids: number[]): Permission[] => {
    return permissionsStore.getPermissionsByIds(ids)
  }

  /**
   * Invalidate cache (force refresh on next fetch)
   */
  const invalidateCache = () => {
    permissionsStore.invalidateCache()
  }

  /**
   * Clear cache completely
   */
  const clearCache = () => {
    permissionsStore.clearCache()
  }

  return {
    // State
    permissions,
    allPermissions,
    loading,
    error,
    isCacheValid,

    // Methods
    getPermissions,
    can,
    canAny,
    checkPermissions,
    getPermissionsByResource,
    getPermissionById,
    getPermissionsByIds,
    invalidateCache,
    clearCache,
  }
}
