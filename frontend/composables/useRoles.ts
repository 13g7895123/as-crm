import { ref, computed } from 'vue'
import { useRolesStore, type Role, type RoleFilters } from '~/stores/roles'
import { useAuthStore } from '~/stores/auth'

/**
 * useRoles Composable
 *
 * Provides methods for role management including:
 * - getRoles: Fetch roles with pagination and filters
 * - createRole: Create a new role
 * - updateRole: Update an existing role
 * - deleteRole: Delete a role
 * - getRoleById: Fetch a single role
 */
export function useRoles() {
  const rolesStore = useRolesStore()
  const authStore = useAuthStore()
  const config = useRuntimeConfig()

  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed properties from store
  const roles = computed(() => rolesStore.roles)
  const currentRole = computed(() => rolesStore.currentRole)
  const pagination = computed(() => rolesStore.pagination)

  /**
   * Get API base URL
   */
  const getApiUrl = () => {
    return config.public.apiBase || 'http://localhost:8080/api/v1'
  }

  /**
   * Get authorization headers
   */
  const getHeaders = () => {
    const token = authStore.token
    return {
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : '',
    }
  }

  /**
   * Fetch roles with filters and pagination
   */
  const getRoles = async (filters: RoleFilters = {}) => {
    loading.value = true
    error.value = null
    rolesStore.setLoading(true)
    rolesStore.clearError()

    try {
      const queryParams = new URLSearchParams()

      if (filters.page) queryParams.append('page', filters.page.toString())
      if (filters.per_page) queryParams.append('per_page', filters.per_page.toString())
      if (filters.is_active !== undefined) queryParams.append('is_active', filters.is_active.toString())
      if (filters.search) queryParams.append('search', filters.search)
      if (filters.sort) queryParams.append('sort', filters.sort)
      if (filters.order) queryParams.append('order', filters.order)

      const url = `${getApiUrl()}/roles?${queryParams.toString()}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to fetch roles')
      }

      const data = await response.json()

      rolesStore.setRoles(data.data || [])
      if (data.meta) {
        rolesStore.setPagination(data.meta)
      }

      return data.data
    } catch (err: any) {
      error.value = err.message
      rolesStore.setError(err.message)
      throw err
    } finally {
      loading.value = false
      rolesStore.setLoading(false)
    }
  }

  /**
   * Get a single role by ID
   */
  const getRoleById = async (id: number): Promise<Role> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/roles/${id}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        if (response.status === 404) {
          throw new Error('Role not found')
        }
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to fetch role')
      }

      const data = await response.json()
      const role = data.data

      rolesStore.setCurrentRole(role)
      return role
    } catch (err: any) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Create a new role
   */
  const createRole = async (roleData: Partial<Role>): Promise<Role> => {
    loading.value = true
    error.value = null

    try {
      // Validate required fields
      if (!roleData.name || !roleData.display_name) {
        throw new Error('Name and display name are required')
      }

      const url = `${getApiUrl()}/roles`
      const response = await fetch(url, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify(roleData),
      })

      if (!response.ok) {
        const errorData = await response.json()
        if (response.status === 422) {
          // Validation error
          const errors = errorData.errors || {}
          const errorMessages = Object.values(errors).flat().join(', ')
          throw new Error(errorMessages || errorData.message || 'Validation failed')
        }
        throw new Error(errorData.message || 'Failed to create role')
      }

      const data = await response.json()
      const newRole = data.data

      rolesStore.addRole(newRole)
      return newRole
    } catch (err: any) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Update an existing role
   */
  const updateRole = async (id: number, roleData: Partial<Role>): Promise<Role> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/roles/${id}`
      const response = await fetch(url, {
        method: 'PUT',
        headers: getHeaders(),
        body: JSON.stringify(roleData),
      })

      if (!response.ok) {
        if (response.status === 404) {
          throw new Error('ROLE_NOT_FOUND')
        }
        const errorData = await response.json()
        if (response.status === 422) {
          const errors = errorData.errors || {}
          const errorMessages = Object.values(errors).flat().join(', ')
          throw new Error(errorMessages || errorData.message || 'Validation failed')
        }
        throw new Error(errorData.message || 'Failed to update role')
      }

      const data = await response.json()
      const updatedRole = data.data

      rolesStore.updateRole(updatedRole)
      return updatedRole
    } catch (err: any) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete a role
   */
  const deleteRole = async (id: number): Promise<boolean> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/roles/${id}`
      const response = await fetch(url, {
        method: 'DELETE',
        headers: getHeaders(),
      })

      if (!response.ok) {
        if (response.status === 404) {
          throw new Error('Role not found')
        }
        if (response.status === 400) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Cannot delete role')
        }
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to delete role')
      }

      rolesStore.removeRole(id)
      return true
    } catch (err: any) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Get role permissions
   */
  const getRolePermissions = async (id: number, includeInherited: boolean = true) => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/roles/${id}/permissions?include_inherited=${includeInherited}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to fetch role permissions')
      }

      const data = await response.json()
      return data.data
    } catch (err: any) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    roles,
    currentRole,
    pagination,
    loading,
    error,

    // Methods
    getRoles,
    getRoleById,
    createRole,
    updateRole,
    deleteRole,
    getRolePermissions,
  }
}
