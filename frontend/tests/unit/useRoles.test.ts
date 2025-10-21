import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useRoles } from '~/composables/useRoles'
import { useRolesStore } from '~/stores/roles'

/**
 * useRoles Composable Unit Tests
 *
 * Tests the role management composable functionality
 * including CRUD operations and state management
 */
describe('useRoles composable', () => {
  beforeEach(() => {
    // Create a fresh pinia instance for each test
    setActivePinia(createPinia())

    // Mock fetch globally
    global.fetch = vi.fn()
  })

  describe('getRoles', () => {
    it('should fetch roles from API', async () => {
      const mockRoles = {
        data: [
          {
            id: 1,
            name: 'sales_manager',
            display_name: '業務主管',
            is_active: true,
            is_system: true
          }
        ],
        meta: {
          current_page: 1,
          per_page: 20,
          total: 1,
          total_pages: 1
        }
      }

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => mockRoles
      })

      const { getRoles, roles } = useRoles()
      await getRoles()

      expect(roles.value).toHaveLength(1)
      expect(roles.value[0].display_name).toBe('業務主管')
    })

    it('should handle pagination parameters', async () => {
      const mockResponse = {
        data: [],
        meta: { current_page: 2, per_page: 10, total: 25, total_pages: 3 }
      }

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => mockResponse
      })

      const { getRoles } = useRoles()
      await getRoles({ page: 2, per_page: 10 })

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('page=2'),
        expect.any(Object)
      )
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('per_page=10'),
        expect.any(Object)
      )
    })

    it('should handle search parameter', async () => {
      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({ data: [], meta: {} })
      })

      const { getRoles } = useRoles()
      await getRoles({ search: '業務' })

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('search=%E6%A5%AD%E5%8B%99'),
        expect.any(Object)
      )
    })

    it('should handle API errors', async () => {
      global.fetch = vi.fn().mockResolvedValue({
        ok: false,
        status: 500,
        json: async () => ({ error: 'Internal server error' })
      })

      const { getRoles, error } = useRoles()
      await getRoles()

      expect(error.value).toBeTruthy()
    })
  })

  describe('createRole', () => {
    it('should create a new role', async () => {
      const newRole = {
        name: 'regional_sales_manager',
        display_name: '區域業務主管',
        description: '負責特定區域的業務管理',
        permissions: [1, 2, 5]
      }

      const mockResponse = {
        data: {
          id: 5,
          ...newRole,
          is_active: true,
          is_system: false,
          created_at: '2025-10-21T10:00:00Z'
        },
        message: '角色建立成功'
      }

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        status: 201,
        json: async () => mockResponse
      })

      const { createRole } = useRoles()
      const result = await createRole(newRole)

      expect(result).toBeTruthy()
      expect(result.id).toBe(5)
      expect(result.display_name).toBe('區域業務主管')
    })

    it('should validate required fields', async () => {
      const invalidRole = {
        description: 'Missing required fields'
      }

      const { createRole, error } = useRoles()
      await createRole(invalidRole as any)

      expect(error.value).toBeTruthy()
    })

    it('should include condition rules', async () => {
      const roleWithConditions = {
        name: 'conditional_role',
        display_name: '有條件的角色',
        permissions: [1],
        condition_rules: [
          {
            condition_type: 'region',
            operator: 'equals',
            condition_value: { value: '華東' }
          }
        ]
      }

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        status: 201,
        json: async () => ({ data: { id: 6, ...roleWithConditions }, message: 'Success' })
      })

      const { createRole } = useRoles()
      const result = await createRole(roleWithConditions)

      expect(global.fetch).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          method: 'POST',
          body: expect.stringContaining('condition_rules')
        })
      )
    })
  })

  describe('updateRole', () => {
    it('should update an existing role', async () => {
      const updateData = {
        display_name: '更新後的角色名',
        is_active: false
      }

      const mockResponse = {
        data: {
          id: 1,
          name: 'test_role',
          ...updateData,
          updated_at: '2025-10-21T11:00:00Z'
        },
        message: '角色更新成功'
      }

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => mockResponse
      })

      const { updateRole } = useRoles()
      const result = await updateRole(1, updateData)

      expect(result).toBeTruthy()
      expect(result.display_name).toBe('更新後的角色名')
      expect(result.is_active).toBe(false)
    })

    it('should update role permissions', async () => {
      const updateData = {
        permissions: [2, 3, 4]
      }

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({
          data: { id: 1, name: 'test', display_name: 'Test', permissions: [2, 3, 4] },
          message: 'Success'
        })
      })

      const { updateRole } = useRoles()
      await updateRole(1, updateData)

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/roles/1'),
        expect.objectContaining({
          method: 'PUT',
          body: expect.stringContaining('permissions')
        })
      )
    })

    it('should handle 404 errors', async () => {
      global.fetch = vi.fn().mockResolvedValue({
        ok: false,
        status: 404,
        json: async () => ({ error: 'Not found', code: 'ROLE_NOT_FOUND' })
      })

      const { updateRole, error } = useRoles()
      await updateRole(99999, { display_name: 'Test' })

      expect(error.value).toContain('NOT_FOUND')
    })
  })

  describe('deleteRole', () => {
    it('should delete a role', async () => {
      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({ message: '角色刪除成功' })
      })

      const { deleteRole } = useRoles()
      const result = await deleteRole(5)

      expect(result).toBe(true)
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/roles/5'),
        expect.objectContaining({ method: 'DELETE' })
      )
    })

    it('should prevent deleting system roles', async () => {
      global.fetch = vi.fn().mockResolvedValue({
        ok: false,
        status: 400,
        json: async () => ({ error: '無法刪除系統角色' })
      })

      const { deleteRole, error } = useRoles()
      const result = await deleteRole(1)

      expect(result).toBe(false)
      expect(error.value).toBeTruthy()
    })
  })

  describe('getRoleById', () => {
    it('should fetch a single role with details', async () => {
      const mockRole = {
        data: {
          id: 1,
          name: 'sales_manager',
          display_name: '業務主管',
          permissions: [
            { id: 1, module: 'customer', action: 'view' },
            { id: 2, module: 'customer', action: 'edit' }
          ],
          condition_rules: [
            {
              id: 1,
              condition_type: 'region',
              operator: 'equals',
              condition_value: { value: '華東' }
            }
          ]
        }
      }

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => mockRole
      })

      const { getRoleById } = useRoles()
      const role = await getRoleById(1)

      expect(role).toBeTruthy()
      expect(role.id).toBe(1)
      expect(role.permissions).toHaveLength(2)
      expect(role.condition_rules).toHaveLength(1)
    })
  })

  describe('state management', () => {
    it('should update store when fetching roles', async () => {
      const mockRoles = {
        data: [{ id: 1, name: 'test', display_name: 'Test' }],
        meta: { current_page: 1, per_page: 20, total: 1, total_pages: 1 }
      }

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => mockRoles
      })

      const store = useRolesStore()
      const { getRoles } = useRoles()

      await getRoles()

      expect(store.roles).toHaveLength(1)
      expect(store.roles[0].name).toBe('test')
    })

    it('should clear error state on successful operation', async () => {
      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({ data: [], meta: {} })
      })

      const { getRoles, error } = useRoles()
      await getRoles()

      expect(error.value).toBeNull()
    })
  })

  describe('loading states', () => {
    it('should set loading state during async operations', async () => {
      let resolvePromise: any
      const promise = new Promise((resolve) => {
        resolvePromise = resolve
      })

      global.fetch = vi.fn().mockReturnValue(promise)

      const { getRoles, loading } = useRoles()
      const fetchPromise = getRoles()

      // Should be loading
      expect(loading.value).toBe(true)

      // Resolve the promise
      resolvePromise({
        ok: true,
        json: async () => ({ data: [], meta: {} })
      })

      await fetchPromise

      // Should no longer be loading
      expect(loading.value).toBe(false)
    })
  })
})
