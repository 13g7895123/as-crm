import { defineStore } from 'pinia'
import type { Ref } from 'vue'

/**
 * Role interface matching backend API response
 */
export interface Role {
  id: number
  name: string
  display_name: string
  description?: string
  level: number
  is_active: boolean
  is_system: boolean
  created_at: string
  updated_at?: string
  deleted_at?: string
  permissions?: Permission[]
  condition_rules?: ConditionRule[]
}

/**
 * Role input for create/update - permissions are IDs
 */
export interface RoleInput {
  name?: string
  display_name?: string
  description?: string
  level?: number
  is_active?: boolean
  is_system?: boolean
  permissions?: number[]
  condition_rules?: ConditionRule[]
}

export interface Permission {
  id: number
  name: string
  resource: string
  action: string
  description?: string
}

export interface ConditionRule {
  id?: number
  permission_id?: number | null
  condition_type: 'department' | 'region' | 'customer_group' | 'order_status' | 'amount_range' | string
  operator: 'equals' | 'not_equals' | 'in' | 'not_in' | 'greater_than' | 'less_than' | 'between' | string
  condition_value: any
  field_name?: string
}

export interface RolesState {
  roles: Role[]
  currentRole: Role | null
  loading: boolean
  error: string | null
  pagination: {
    current_page: number
    per_page: number
    total: number
    total_pages: number
  }
}

export interface RoleFilters {
  page?: number
  per_page?: number
  is_active?: boolean
  search?: string
  sort?: 'name' | 'created_at' | 'updated_at'
  order?: 'asc' | 'desc'
}

/**
 * Roles Store
 *
 * Manages role state including list, current role, and pagination
 */
export const useRolesStore = defineStore('roles', {
  state: (): RolesState => ({
    roles: [],
    currentRole: null,
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      per_page: 20,
      total: 0,
      total_pages: 0,
    },
  }),

  getters: {
    /**
     * Get roles sorted by name
     */
    rolesSortedByName: (state): Role[] => {
      return [...state.roles].sort((a, b) => a.display_name.localeCompare(b.display_name))
    },

    /**
     * Get system roles
     */
    systemRoles: (state): Role[] => {
      return state.roles.filter(role => role.is_system)
    },

    /**
     * Get custom roles
     */
    customRoles: (state): Role[] => {
      return state.roles.filter(role => !role.is_system)
    },

    /**
     * Get role by ID
     */
    getRoleById: (state) => (id: number): Role | undefined => {
      return state.roles.find(role => role.id === id)
    },

    /**
     * Check if there are more pages
     */
    hasMorePages: (state): boolean => {
      return state.pagination.current_page < state.pagination.total_pages
    },

    /**
     * Check if currently loading
     */
    isLoading: (state): boolean => {
      return state.loading
    },
  },

  actions: {
    /**
     * Set roles list
     */
    setRoles(roles: Role[]) {
      this.roles = roles
    },

    /**
     * Set current role
     */
    setCurrentRole(role: Role | null) {
      this.currentRole = role
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
     * Set pagination metadata
     */
    setPagination(meta: RolesState['pagination']) {
      this.pagination = meta
    },

    /**
     * Add role to list
     */
    addRole(role: Role) {
      this.roles.push(role)
      this.pagination.total++
    },

    /**
     * Update role in list
     */
    updateRole(role: Role) {
      const index = this.roles.findIndex(r => r.id === role.id)
      if (index !== -1) {
        this.roles[index] = role
      }

      if (this.currentRole && this.currentRole.id === role.id) {
        this.currentRole = role
      }
    },

    /**
     * Remove role from list
     */
    removeRole(roleId: number) {
      const index = this.roles.findIndex(r => r.id === roleId)
      if (index !== -1) {
        this.roles.splice(index, 1)
        this.pagination.total--
      }

      if (this.currentRole && this.currentRole.id === roleId) {
        this.currentRole = null
      }
    },

    /**
     * Clear all roles
     */
    clearRoles() {
      this.roles = []
      this.currentRole = null
      this.pagination = {
        current_page: 1,
        per_page: 20,
        total: 0,
        total_pages: 0,
      }
    },

    /**
     * Reset error state
     */
    clearError() {
      this.error = null
    },
  },
})
