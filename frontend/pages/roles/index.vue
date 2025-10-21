<template>
  <div class="roles-page">
    <!-- Page header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">角色管理</h1>
          <p class="mt-1 text-sm text-gray-500">管理系統角色和自訂角色的權限設定</p>
        </div>
        <NuxtLink
          to="/roles/create"
          data-testid="create-role-button"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          建立角色
        </NuxtLink>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg shadow p-4">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <!-- Search -->
        <div>
          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">搜尋</label>
          <input
            id="search"
            v-model="filters.search"
            type="text"
            data-testid="search-input"
            placeholder="搜尋角色名稱..."
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            @input="debounceSearch"
          />
        </div>

        <!-- Active filter -->
        <div>
          <label for="filter-active" class="block text-sm font-medium text-gray-700 mb-1">狀態</label>
          <select
            id="filter-active"
            v-model="filters.is_active"
            data-testid="filter-active"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            @change="loadRoles"
          >
            <option :value="undefined">全部</option>
            <option :value="true" data-testid="filter-active-option-true">啟用</option>
            <option :value="false">停用</option>
          </select>
        </div>

        <!-- Sort -->
        <div>
          <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">排序</label>
          <select
            id="sort"
            v-model="filters.sort"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            @change="loadRoles"
          >
            <option value="created_at">建立時間</option>
            <option value="updated_at">更新時間</option>
            <option value="name">名稱</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading && roles.length === 0" class="text-center py-12">
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">載入中...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="rounded-md bg-red-50 p-4" data-testid="error-message">
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">載入角色失敗</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{{ error }}</p>
          </div>
          <div class="mt-4">
            <button
              type="button"
              data-testid="retry-button"
              @click="loadRoles"
              class="text-sm font-medium text-red-800 hover:text-red-700"
            >
              重試
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Roles table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200" data-testid="roles-table">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">描述</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">類型</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="role in roles" :key="role.id" data-testid="role-row">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900" data-testid="role-display-name">
                {{ role.display_name }}
              </div>
              <div class="text-sm text-gray-500">{{ role.name }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900">{{ role.description || '-' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                v-if="role.is_system"
                data-testid="system-role-badge"
                class="inline-flex px-2 text-xs font-semibold leading-5 text-blue-800 bg-blue-100 rounded-full"
              >
                系統角色
              </span>
              <span v-else class="inline-flex px-2 text-xs font-semibold leading-5 text-gray-800 bg-gray-100 rounded-full">
                自訂角色
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :data-testid="'role-active-badge'"
                :class="[
                  'inline-flex px-2 text-xs font-semibold leading-5 rounded-full',
                  !role.deleted_at ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'
                ]"
              >
                {{ !role.deleted_at ? '啟用' : '停用' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
              <NuxtLink
                :to="`/roles/${role.id}`"
                data-testid="view-role-button"
                class="text-indigo-600 hover:text-indigo-900"
              >
                檢視
              </NuxtLink>
              <NuxtLink
                :to="`/roles/${role.id}/edit`"
                data-testid="edit-role-button"
                class="text-indigo-600 hover:text-indigo-900"
              >
                編輯
              </NuxtLink>
              <button
                v-if="!role.is_system"
                type="button"
                data-testid="delete-role-button"
                @click="confirmDelete(role)"
                class="text-red-600 hover:text-red-900"
              >
                刪除
              </button>
              <span v-else data-testid="delete-role-button" class="text-gray-400 cursor-not-allowed" disabled>
                刪除
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty state -->
      <div v-if="roles.length === 0" class="text-center py-12">
        <p class="text-sm text-gray-500">尚無角色資料</p>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination.total_pages > 1"
        data-testid="pagination-controls"
        class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6"
      >
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            顯示第
            <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span>
            至
            <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
            筆，共
            <span class="font-medium">{{ pagination.total }}</span>
            筆
          </div>
          <div class="flex space-x-2">
            <button
              type="button"
              :disabled="pagination.current_page === 1"
              @click="changePage(pagination.current_page - 1)"
              :class="[
                'px-3 py-1 text-sm font-medium rounded-md',
                pagination.current_page === 1
                  ? 'text-gray-400 bg-gray-100 cursor-not-allowed'
                  : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
              ]"
            >
              上一頁
            </button>
            <span data-testid="current-page" class="px-3 py-1 text-sm font-medium text-gray-700">
              第 {{ pagination.current_page }} / {{ pagination.total_pages }} 頁
            </span>
            <button
              type="button"
              data-testid="next-page-button"
              :disabled="pagination.current_page === pagination.total_pages"
              @click="changePage(pagination.current_page + 1)"
              :class="[
                'px-3 py-1 text-sm font-medium rounded-md',
                pagination.current_page === pagination.total_pages
                  ? 'text-gray-400 bg-gray-100 cursor-not-allowed'
                  : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
              ]"
            >
              下一頁
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <div
      v-if="deleteConfirmation.show"
      data-testid="confirm-delete-modal"
      class="fixed inset-0 z-50 overflow-y-auto"
    >
      <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="cancelDelete"></div>

        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
          <div>
            <div class="mt-3 text-center sm:mt-0 sm:text-left">
              <h3 class="text-lg font-medium leading-6 text-gray-900">
                確認刪除角色
              </h3>
              <div class="mt-2">
                <p class="text-sm text-gray-500">
                  確定要刪除角色「<span class="font-medium">{{ deleteConfirmation.role?.display_name }}</span>」嗎？
                  此操作無法復原。
                </p>
              </div>
            </div>
          </div>
          <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
            <button
              type="button"
              data-testid="confirm-delete-button"
              @click="executeDelete"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
            >
              刪除
            </button>
            <button
              type="button"
              @click="cancelDelete"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
            >
              取消
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoles } from '~/composables/useRoles'
import type { Role } from '~/stores/roles'

definePageMeta({
  title: '角色管理',
})

const { getRoles, deleteRole, roles, pagination, loading, error } = useRoles()

// Filters
const filters = reactive({
  search: '',
  is_active: undefined as boolean | undefined,
  sort: 'created_at' as 'name' | 'created_at' | 'updated_at',
  order: 'desc' as 'asc' | 'desc',
  page: 1,
  per_page: 20,
})

// Delete confirmation
const deleteConfirmation = reactive({
  show: false,
  role: null as Role | null,
})

// Load roles
const loadRoles = async () => {
  try {
    await getRoles(filters)
  } catch (err) {
    console.error('Failed to load roles:', err)
  }
}

// Debounce search
let searchTimeout: NodeJS.Timeout
const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    loadRoles()
  }, 300)
}

// Change page
const changePage = (page: number) => {
  filters.page = page
  loadRoles()
}

// Confirm delete
const confirmDelete = (role: Role) => {
  deleteConfirmation.show = true
  deleteConfirmation.role = role
}

// Cancel delete
const cancelDelete = () => {
  deleteConfirmation.show = false
  deleteConfirmation.role = null
}

// Execute delete
const executeDelete = async () => {
  if (!deleteConfirmation.role) return

  try {
    await deleteRole(deleteConfirmation.role.id)
    cancelDelete()
    await loadRoles()
  } catch (err) {
    console.error('Failed to delete role:', err)
    alert('刪除角色失敗: ' + (err as Error).message)
  }
}

// Load roles on mount
onMounted(() => {
  loadRoles()
})
</script>

<style scoped>
.roles-page {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8;
}
</style>
