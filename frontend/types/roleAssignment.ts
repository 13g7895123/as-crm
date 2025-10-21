/**
 * Role Assignment Types
 */

export interface RoleAssignment {
  id: number
  user_id: number
  role_id: number
  assigned_by: number | null
  assigned_at: string
  expires_at: string | null
  scope_constraints: Record<string, any> | null
  created_at: string
  updated_at: string
  // Joined fields
  username?: string
  email?: string
  full_name?: string
  name?: string // role name
  display_name?: string // role display name
  description?: string // role description
  role_name?: string
  role_display_name?: string
}

export interface RoleAssignmentFilters {
  user_id?: number
  role_id?: number
  include_expired?: boolean
  expiring_days?: number
  page?: number
  per_page?: number
  sort?: string
  order?: 'ASC' | 'DESC'
}

export interface CreateRoleAssignmentData {
  user_id: number
  role_id: number
  expires_at?: string | null
  scope_constraints?: Record<string, any>
}

export interface BulkRoleAssignmentData {
  user_ids: number[]
  role_id: number
  expires_at?: string | null
  scope_constraints?: Record<string, any>
}

export interface ExtendRoleValidityData {
  expires_at: string
}
