import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'

/**
 * Audit Log Composable
 *
 * Provides methods for querying and exporting audit logs
 */

export interface AuditLog {
  id: number
  user_id: number | null
  action: string
  resource_type: string
  resource_id: number | null
  details: Record<string, any> | string | null
  ip_address: string | null
  user_agent: string | null
  created_at: string
  // Joined fields
  username?: string
  full_name?: string
  email?: string
}

export interface AuditLogFilters {
  user_id?: number
  action?: string
  resource_type?: string
  resource_id?: number
  start_date?: string
  end_date?: string
  search?: string
  page?: number
  per_page?: number
  sort?: string
  order?: 'ASC' | 'DESC'
}

export interface ActionStatistic {
  action: string
  count: number
}

export interface UserActivityStatistic {
  user_id: number
  username: string
  full_name: string
  action_count: number
}

export function useAuditLogs() {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()

  const logs = ref<AuditLog[]>([])
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
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
    }
    if (authStore.accessToken) {
      headers['Authorization'] = `Bearer ${authStore.accessToken}`
    }
    return headers
  }

  /**
   * Get all audit logs with filters
   */
  const getLogs = async (filters: AuditLogFilters = {}): Promise<any> => {
    loading.value = true
    error.value = null

    try {
      const queryParams = new URLSearchParams()

      if (filters.user_id) queryParams.append('user_id', filters.user_id.toString())
      if (filters.action) queryParams.append('action', filters.action)
      if (filters.resource_type) queryParams.append('resource_type', filters.resource_type)
      if (filters.resource_id) queryParams.append('resource_id', filters.resource_id.toString())
      if (filters.start_date) queryParams.append('start_date', filters.start_date)
      if (filters.end_date) queryParams.append('end_date', filters.end_date)
      if (filters.search) queryParams.append('search', filters.search)
      if (filters.page) queryParams.append('page', filters.page.toString())
      if (filters.per_page) queryParams.append('per_page', filters.per_page.toString())
      if (filters.sort) queryParams.append('sort', filters.sort)
      if (filters.order) queryParams.append('order', filters.order)

      const url = `${getApiUrl()}/audit-logs?${queryParams.toString()}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取審計記錄失敗')
      }

      const data = await response.json()
      logs.value = data.data || []
      return data
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Get log by ID
   */
  const getLogById = async (logId: number): Promise<AuditLog> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/audit-logs/${logId}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取審計記錄失敗')
      }

      const data = await response.json()
      return data.data
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Get user's audit logs
   */
  const getUserLogs = async (userId: number, limit: number = 50): Promise<AuditLog[]> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/audit-logs/user/${userId}?limit=${limit}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取使用者審計記錄失敗')
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
   * Get resource audit trail
   */
  const getResourceAuditTrail = async (
    resourceType: string,
    resourceId: number
  ): Promise<AuditLog[]> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/audit-logs/resource/${resourceType}/${resourceId}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取資源審計記錄失敗')
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
   * Get recent activity
   */
  const getRecentActivity = async (limit: number = 100): Promise<AuditLog[]> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/audit-logs/recent?limit=${limit}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取最近活動失敗')
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
   * Get statistics
   */
  const getStatistics = async (filters: Partial<AuditLogFilters> = {}): Promise<{
    action_statistics: ActionStatistic[]
    user_activity: UserActivityStatistic[]
  }> => {
    loading.value = true
    error.value = null

    try {
      const queryParams = new URLSearchParams()

      if (filters.start_date) queryParams.append('start_date', filters.start_date)
      if (filters.end_date) queryParams.append('end_date', filters.end_date)
      if (filters.user_id) queryParams.append('user_id', filters.user_id.toString())

      const url = `${getApiUrl()}/audit-logs/statistics?${queryParams.toString()}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取統計資料失敗')
      }

      const data = await response.json()
      return data.data
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Get summary
   */
  const getSummary = async (filters: Partial<AuditLogFilters> = {}): Promise<any> => {
    loading.value = true
    error.value = null

    try {
      const queryParams = new URLSearchParams()

      if (filters.start_date) queryParams.append('start_date', filters.start_date)
      if (filters.end_date) queryParams.append('end_date', filters.end_date)
      if (filters.user_id) queryParams.append('user_id', filters.user_id.toString())
      if (filters.action) queryParams.append('action', filters.action)
      if (filters.resource_type) queryParams.append('resource_type', filters.resource_type)

      const url = `${getApiUrl()}/audit-logs/summary?${queryParams.toString()}`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取摘要失敗')
      }

      const data = await response.json()
      return data.data
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Export logs
   */
  const exportLogs = async (
    format: 'csv' | 'json',
    filters: Partial<AuditLogFilters> = {}
  ): Promise<Blob> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/audit-logs/export`
      const response = await fetch(url, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          format,
          filters,
        }),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '匯出失敗')
      }

      return await response.blob()
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Format action name for display
   */
  const formatAction = (action: string): string => {
    const actionMap: Record<string, string> = {
      'role_created': '建立角色',
      'role_updated': '更新角色',
      'role_deleted': '刪除角色',
      'role_assigned': '指派角色',
      'role_revoked': '撤銷角色',
      'role_validity_modified': '修改角色有效期',
      'role_bulk_assigned': '批次指派角色',
      'role_expired_cleaned': '清理過期角色',
      'permission_assigned': '指派權限',
      'permission_revoked': '撤銷權限',
      'user_login': '使用者登入',
      'user_logout': '使用者登出',
      'audit_logs_exported': '匯出審計記錄',
      'audit_logs_cleaned': '清理審計記錄',
    }

    return actionMap[action] || action
  }

  /**
   * Format resource type for display
   */
  const formatResourceType = (resourceType: string): string => {
    const resourceMap: Record<string, string> = {
      'role': '角色',
      'permission': '權限',
      'role_assignment': '角色指派',
      'user': '使用者',
      'audit_log': '審計記錄',
      'customer': '客戶',
      'order': '訂單',
    }

    return resourceMap[resourceType] || resourceType
  }

  /**
   * Format datetime
   */
  const formatDateTime = (datetime: string): string => {
    const date = new Date(datetime)
    const options: Intl.DateTimeFormatOptions = {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    }
    return date.toLocaleDateString('zh-TW', options)
  }

  return {
    logs,
    loading,
    error,
    getLogs,
    getLogById,
    getUserLogs,
    getResourceAuditTrail,
    getRecentActivity,
    getStatistics,
    getSummary,
    exportLogs,
    formatAction,
    formatResourceType,
    formatDateTime,
  }
}
