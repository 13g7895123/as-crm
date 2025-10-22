<template>
  <LayoutContentArea title="我的權限" subtitle="檢視您當前擁有的所有角色和權限">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="text-center">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
          <p class="mt-2 text-sm text-gray-500">載入權限資料中...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="rounded-md bg-red-50 p-4">
        <p class="text-sm text-red-800">{{ error }}</p>
      </div>

      <!-- Main Content -->
      <div v-else class="space-y-6">
        <!-- User Info Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="flex items-center space-x-4">
            <div class="flex-shrink-0">
              <div class="w-16 h-16 bg-indigo-600 text-white rounded-full flex items-center justify-center text-2xl font-medium">
                {{ userInitials }}
              </div>
            </div>
            <div class="flex-1">
              <h2 class="text-xl font-semibold text-gray-900">{{ currentUser?.full_name || currentUser?.username }}</h2>
              <p class="text-sm text-gray-500">{{ currentUser?.email }}</p>
              <div class="mt-2 flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  已啟用
                </span>
                <span v-if="isAdmin" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                  系統管理員
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Roles Section -->
        <div class="bg-white rounded-lg border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">我的角色</h3>
            <p class="mt-1 text-sm text-gray-500">您目前被指派的角色和有效期限</p>
          </div>

          <div class="divide-y divide-gray-200">
            <div v-if="roles.length === 0" class="px-6 py-8 text-center text-sm text-gray-500">
              目前沒有被指派任何角色
            </div>

            <div
              v-for="role in roles"
              :key="role.id"
              class="px-6 py-4 hover:bg-gray-50"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-3">
                    <h4 class="text-sm font-medium text-gray-900">
                      {{ role.display_name || role.name }}
                    </h4>
                    <span
                      v-if="role.is_system"
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                    >
                      系統預設
                    </span>
                    <span
                      v-if="isExpiringSoon(role)"
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800"
                    >
                      即將過期
                    </span>
                  </div>

                  <p v-if="role.description" class="mt-1 text-sm text-gray-500">
                    {{ role.description }}
                  </p>

                  <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                    <div class="flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span>指派時間: {{ formatDate(role.assigned_at) }}</span>
                    </div>

                    <div v-if="role.expires_at" class="flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span :class="{ 'text-red-600 font-medium': isExpiring(role) }">
                        {{ role.expires_at ? `過期時間: ${formatDate(role.expires_at)}` : '永久有效' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Permissions Section -->
        <div class="bg-white rounded-lg border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">我的權限</h3>
            <p class="mt-1 text-sm text-gray-500">從所有角色彙總的權限清單(按功能模組分組)</p>
          </div>

          <div class="p-6">
            <div v-if="Object.keys(permissionsGrouped).length === 0" class="text-center text-sm text-gray-500 py-8">
              目前沒有任何權限
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="(actions, module) in permissionsGrouped"
                :key="module"
                class="border border-gray-200 rounded-lg p-4"
              >
                <h4 class="text-sm font-medium text-gray-900 mb-3">
                  {{ getModuleDisplayName(module) }}
                </h4>

                <div class="space-y-2">
                  <div
                    v-for="action in actions"
                    :key="action"
                    class="flex items-center text-sm text-gray-600"
                  >
                    <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    {{ getActionDisplayName(action) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
            <div class="text-sm font-medium text-blue-900">總角色數</div>
            <div class="mt-2 text-3xl font-bold text-blue-600">{{ roles.length }}</div>
          </div>

          <div class="bg-green-50 rounded-lg border border-green-200 p-4">
            <div class="text-sm font-medium text-green-900">總權限數</div>
            <div class="mt-2 text-3xl font-bold text-green-600">{{ totalPermissions }}</div>
          </div>

          <div class="bg-purple-50 rounded-lg border border-purple-200 p-4">
            <div class="text-sm font-medium text-purple-900">功能模組數</div>
            <div class="mt-2 text-3xl font-bold text-purple-600">{{ Object.keys(permissionsGrouped).length }}</div>
          </div>
        </div>
      </div>
  </LayoutContentArea>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '~/composables/useAuth'
import { usePermissions } from '~/composables/usePermissions'

definePageMeta({
  title: '我的權限',
  layout: 'default',
})

const { currentUser } = useAuth()
const { getPermissions } = usePermissions()

const loading = ref(true)
const error = ref<string | null>(null)
const roles = ref<any[]>([])
const permissionsGrouped = ref<Record<string, string[]>>({})

const userInitials = computed(() => {
  const name = currentUser.value?.full_name || currentUser.value?.username || 'U'
  return name.substring(0, 2).toUpperCase()
})

const isAdmin = computed(() => {
  return roles.value.some(role => role.name === 'system_admin')
})

const totalPermissions = computed(() => {
  return Object.values(permissionsGrouped.value).reduce((sum, actions) => sum + actions.length, 0)
})

/**
 * Load user's roles and permissions
 */
const loadPermissions = async () => {
  loading.value = true
  error.value = null

  try {
    // Get permissions grouped by module
    const permissions = await getPermissions()

    permissionsGrouped.value = permissions.reduce((grouped: Record<string, string[]>, perm: any) => {
      if (!grouped[perm.resource]) {
        grouped[perm.resource] = []
      }
      grouped[perm.resource].push(perm.action)
      return grouped
    }, {})

    // Get user roles (mock data for now - replace with actual API call)
    // In production: const response = await fetch(`/api/v1/users/${currentUser.value.id}/roles`)
    roles.value = currentUser.value?.roles || []
  } catch (err: any) {
    error.value = err.message || '載入權限資料失敗'
    console.error('Failed to load permissions:', err)
  } finally {
    loading.value = false
  }
}

/**
 * Format date
 */
const formatDate = (dateString: string): string => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

/**
 * Check if role is expiring (within 7 days)
 */
const isExpiring = (role: any): boolean => {
  if (!role.expires_at) return false
  const expiryDate = new Date(role.expires_at).getTime()
  const now = Date.now()
  return expiryDate > now && expiryDate <= now + (7 * 24 * 60 * 60 * 1000)
}

/**
 * Check if role is expiring soon (within 3 days)
 */
const isExpiringSoon = (role: any): boolean => {
  if (!role.expires_at) return false
  const expiryDate = new Date(role.expires_at).getTime()
  const now = Date.now()
  return expiryDate > now && expiryDate <= now + (3 * 24 * 60 * 60 * 1000)
}

/**
 * Get module display name
 */
const getModuleDisplayName = (module: string): string => {
  const names: Record<string, string> = {
    customer: '客戶管理',
    order: '訂單管理',
    report: '報表中心',
    role: '角色管理',
    user_permission: '使用者權限',
    audit_log: '審計記錄',
  }
  return names[module] || module
}

/**
 * Get action display name
 */
const getActionDisplayName = (action: string): string => {
  const names: Record<string, string> = {
    view: '檢視',
    edit: '編輯',
    export: '匯出',
    assign: '指派',
  }
  return names[action] || action
}

// Load on mount
onMounted(() => {
  loadPermissions()
})
</script>
