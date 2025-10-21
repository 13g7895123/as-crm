import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import type { RoleAssignment, RoleAssignmentFilters } from '~/types/roleAssignment'

/**
 * Role Assignment Composable
 *
 * Provides methods for managing role assignments with time-based validity
 */
export function useRoleAssignments() {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()

  const assignments = ref<RoleAssignment[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  /**
   * Get API base URL
   */
  const getApiUrl = (): string => {
    return config.public.apiBaseUrl || 'http://localhost:8080/api/v1'
  }

  /**
   * Get authorization headers
   */
  const getHeaders = (): HeadersInit => {
    return {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${authStore.accessToken}`,
    }
  }

  /**
   * Get all role assignments with filters
   */
  const getAssignments = async (filters: RoleAssignmentFilters = {}): Promise<RoleAssignment[]> => {
    loading.value = true
    error.value = null

    try {
      const queryParams = new URLSearchParams()

      if (filters.user_id) queryParams.append('user_id', filters.user_id.toString())
      if (filters.role_id) queryParams.append('role_id', filters.role_id.toString())
      if (filters.include_expired !== undefined) {
        queryParams.append('include_expired', filters.include_expired.toString())
      }
      if (filters.expiring_days) {
        queryParams.append('expiring_days', filters.expiring_days.toString())
      }
      if (filters.page) queryParams.append('page', filters.page.toString())
      if (filters.per_page) queryParams.append('per_page', filters.per_page.toString())
      if (filters.sort) queryParams.append('sort', filters.sort)
      if (filters.order) queryParams.append('order', filters.order)

      const url = `${getApiUrl()}/role-assignments?${queryParams.toString()}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取角色指派失敗')
      }

      const data = await response.json()
      assignments.value = data.data || []
      return data
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Assign a role to a user
   */
  const assignRole = async (data: {
    user_id: number
    role_id: number
    expires_at?: string | null
    scope_constraints?: Record<string, any>
  }): Promise<RoleAssignment> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/role-assignments`
      const response = await fetch(url, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify(data),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '角色指派失敗')
      }

      const result = await response.json()
      return result.data
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Revoke a role assignment
   */
  const revokeAssignment = async (assignmentId: number): Promise<void> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/role-assignments/${assignmentId}`
      const response = await fetch(url, {
        method: 'DELETE',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '撤銷角色失敗')
      }
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Extend or shorten role validity period
   */
  const extendRoleValidity = async (
    assignmentId: number,
    newExpiryDate: string
  ): Promise<RoleAssignment> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/role-assignments/${assignmentId}/extend`
      const response = await fetch(url, {
        method: 'PUT',
        headers: getHeaders(),
        body: JSON.stringify({ expires_at: newExpiryDate }),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '更新有效期失敗')
      }

      const result = await response.json()
      return result.data
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Bulk assign role to multiple users
   */
  const bulkAssignRole = async (data: {
    user_ids: number[]
    role_id: number
    expires_at?: string | null
    scope_constraints?: Record<string, any>
  }): Promise<RoleAssignment[]> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/role-assignments/bulk`
      const response = await fetch(url, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify(data),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '批次指派失敗')
      }

      const result = await response.json()
      return result.data
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Get user's assigned roles
   */
  const getUserRoles = async (
    userId: number,
    includeExpired: boolean = false
  ): Promise<RoleAssignment[]> => {
    loading.value = true
    error.value = null

    try {
      const queryParams = new URLSearchParams()
      queryParams.append('include_expired', includeExpired.toString())

      const url = `${getApiUrl()}/users/${userId}/roles?${queryParams.toString()}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取使用者角色失敗')
      }

      const data = await response.json()
      return data.data || []
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Get roles expiring within specified days
   */
  const getExpiringRoles = async (days: number = 7): Promise<RoleAssignment[]> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/role-assignments/expiring?days=${days}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取即將過期角色失敗')
      }

      const data = await response.json()
      return data.data || []
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Check if assignment is expired
   */
  const isExpired = (assignment: RoleAssignment): boolean => {
    if (!assignment.expires_at) return false
    return new Date(assignment.expires_at) < new Date()
  }

  /**
   * Check if assignment is expiring soon
   */
  const isExpiringSoon = (assignment: RoleAssignment, days: number = 7): boolean => {
    if (!assignment.expires_at) return false
    const expiryDate = new Date(assignment.expires_at)
    const thresholdDate = new Date()
    thresholdDate.setDate(thresholdDate.getDate() + days)
    return expiryDate <= thresholdDate && expiryDate > new Date()
  }

  /**
   * Get days until expiry
   */
  const getDaysUntilExpiry = (assignment: RoleAssignment): number | null => {
    if (!assignment.expires_at) return null
    const expiryDate = new Date(assignment.expires_at)
    const now = new Date()
    const diffTime = expiryDate.getTime() - now.getTime()
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  }

  /**
   * Format expiry date for display
   */
  const formatExpiryDate = (assignment: RoleAssignment): string => {
    if (!assignment.expires_at) return '永久'

    const expiryDate = new Date(assignment.expires_at)
    const options: Intl.DateTimeFormatOptions = {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }
    return expiryDate.toLocaleDateString('zh-TW', options)
  }

  /**
   * Get expiry status
   */
  const getExpiryStatus = (assignment: RoleAssignment): {
    label: string
    color: string
    icon: string
  } => {
    if (!assignment.expires_at) {
      return {
        label: '永久',
        color: 'green',
        icon: 'check-circle',
      }
    }

    if (isExpired(assignment)) {
      return {
        label: '已過期',
        color: 'red',
        icon: 'x-circle',
      }
    }

    const days = getDaysUntilExpiry(assignment)
    if (days !== null && days <= 1) {
      return {
        label: '即將過期',
        color: 'red',
        icon: 'alert-circle',
      }
    }

    if (days !== null && days <= 7) {
      return {
        label: '近期過期',
        color: 'yellow',
        icon: 'alert-triangle',
      }
    }

    return {
      label: '有效',
      color: 'green',
      icon: 'check-circle',
    }
  }

  return {
    assignments,
    loading,
    error,
    getAssignments,
    assignRole,
    revokeAssignment,
    extendRoleValidity,
    bulkAssignRole,
    getUserRoles,
    getExpiringRoles,
    isExpired,
    isExpiringSoon,
    getDaysUntilExpiry,
    formatExpiryDate,
    getExpiryStatus,
  }
}
