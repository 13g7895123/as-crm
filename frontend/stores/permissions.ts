import { defineStore } from 'pinia'

/**
 * Permission Group interface
 */
export interface PermissionGroup {
  resource: string
  resource_name: string
  permissions: Permission[]
}

export interface Permission {
  id: number
  name: string
  display_name: string
  resource: string
  action: 'view' | 'edit' | 'export' | 'assign'
  description?: string
}

export interface PermissionsState {
  permissions: PermissionGroup[]
  loading: boolean
  error: string | null
  lastFetched: number | null
  cacheTTL: number // 30 minutes in milliseconds
}

/**
 * Permissions Store
 *
 * Manages permission data with caching (TTL: 30 minutes)
 * Permissions are relatively static, so we cache them to reduce API calls
 */
export const usePermissionsStore = defineStore('permissions', {
  state: (): PermissionsState => ({
    permissions: [],
    loading: false,
    error: null,
    lastFetched: null,
    cacheTTL: 30 * 60 * 1000, // 30 minutes
  }),

  getters: {
    /**
     * Get all permissions as flat array
     */
    allPermissions: (state): Permission[] => {
      return state.permissions.flatMap(group => group.permissions)
    },

    /**
     * Get permissions by resource
     */
    getPermissionsByResource: (state) => (resource: string): Permission[] => {
      const group = state.permissions.find(g => g.resource === resource)
      return group ? group.permissions : []
    },

    /**
     * Get permission by ID
     */
    getPermissionById: (state) => (id: number): Permission | undefined => {
      for (const group of state.permissions) {
        const permission = group.permissions.find(p => p.id === id)
        if (permission) return permission
      }
      return undefined
    },

    /**
     * Get permissions by IDs
     */
    getPermissionsByIds: (state) => (ids: number[]): Permission[] => {
      const result: Permission[] = []
      for (const group of state.permissions) {
        for (const permission of group.permissions) {
          if (ids.includes(permission.id)) {
            result.push(permission)
          }
        }
      }
      return result
    },

    /**
     * Check if cache is valid
     */
    isCacheValid: (state): boolean => {
      if (!state.lastFetched) return false
      const now = Date.now()
      return (now - state.lastFetched) < state.cacheTTL
    },

    /**
     * Check if permissions are loaded
     */
    isLoaded: (state): boolean => {
      return state.permissions.length > 0
    },

    /**
     * Get resource names
     */
    resourceNames: (state): string[] => {
      return state.permissions.map(g => g.resource_name)
    },

    /**
     * Get permissions grouped by action
     */
    permissionsByAction: (state): Record<string, Permission[]> => {
      const grouped: Record<string, Permission[]> = {
        view: [],
        edit: [],
        export: [],
        assign: [],
      }

      for (const group of state.permissions) {
        for (const permission of group.permissions) {
          grouped[permission.action].push(permission)
        }
      }

      return grouped
    },
  },

  actions: {
    /**
     * Set permissions
     */
    setPermissions(permissions: PermissionGroup[]) {
      this.permissions = permissions
      this.lastFetched = Date.now()
    },

    /**
     * Set loading state
     */
    setLoading(loading: boolean) {
      this.loading = loading
    },

    /**
     * Set error
     */
    setError(error: string | null) {
      this.error = error
    },

    /**
     * Clear cache
     */
    clearCache() {
      this.permissions = []
      this.lastFetched = null
      this.error = null
    },

    /**
     * Invalidate cache (force refresh on next fetch)
     */
    invalidateCache() {
      this.lastFetched = null
    },

    /**
     * Clear error
     */
    clearError() {
      this.error = null
    },

    /**
     * Check if permission exists by name
     */
    hasPermission(permissionName: string): boolean {
      for (const group of this.permissions) {
        if (group.permissions.some(p => p.name === permissionName)) {
          return true
        }
      }
      return false
    },
  },
})
