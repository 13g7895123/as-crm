import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'
import type { Role } from '~/stores/roles'

/**
 * Role Hierarchy Composable
 *
 * Provides methods for managing role hierarchy
 */

export interface RoleWithChildren extends Role {
  children?: RoleWithChildren[]
}

export interface RoleHierarchyInfo {
  role: Role
  parents: Role[]
  children: Role[]
  ancestors: Role[]
  descendants: Role[]
}

export function useRoleHierarchy() {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()

  const hierarchyTree = ref<RoleWithChildren[]>([])
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
      'Authorization': `Bearer ${authStore.token}`,
    }
  }

  /**
   * Get complete hierarchy tree
   */
  const getHierarchyTree = async (): Promise<RoleWithChildren[]> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/roles/hierarchy`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取角色階層失敗')
      }

      const data = await response.json()
      hierarchyTree.value = data.data || []
      return data.data
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Get role's hierarchy information
   */
  const getRoleHierarchyInfo = async (roleId: number): Promise<RoleHierarchyInfo> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/roles/${roleId}/hierarchy`
      const response = await fetch(url, {
        method: 'GET',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '獲取角色階層資訊失敗')
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
   * Update role's parent
   */
  const updateParent = async (roleId: number, parentRoleId: number | null): Promise<void> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/roles/${roleId}/parent`
      const response = await fetch(url, {
        method: 'PUT',
        headers: getHeaders(),
        body: JSON.stringify({ parent_role_id: parentRoleId }),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '更新父角色失敗')
      }
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Add parent to role
   */
  const addParent = async (roleId: number, parentRoleId: number): Promise<void> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/roles/${roleId}/parents`
      const response = await fetch(url, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({ parent_role_id: parentRoleId }),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '新增父角色失敗')
      }
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Remove parent from role
   */
  const removeParent = async (roleId: number, parentRoleId: number): Promise<void> => {
    loading.value = true
    error.value = null

    try {
      const url = `${getApiUrl()}/roles/${roleId}/parents/${parentRoleId}`
      const response = await fetch(url, {
        method: 'DELETE',
        headers: getHeaders(),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || '移除父角色失敗')
      }
    } catch (err) {
      error.value = (err as Error).message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    hierarchyTree,
    loading,
    error,
    getHierarchyTree,
    getRoleHierarchyInfo,
    updateParent,
    addParent,
    removeParent,
  }
}
