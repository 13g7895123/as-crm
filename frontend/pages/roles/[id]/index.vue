<template>
  <LayoutContentArea title="角色詳情" subtitle="檢視角色資訊和權限設定">
    <template #actions>
      <NuxtLink
        :to="`/roles/${roleId}/edit`"
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        編輯角色
      </NuxtLink>
    </template>

    <!-- 返回連結 -->
    <div class="mb-6">
      <NuxtLink to="/roles" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        返回角色列表
      </NuxtLink>
    </div>

    <!-- Loading state -->
    <div v-if="initialLoading" class="text-center py-12">
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">載入中...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="loadError" class="rounded-md bg-red-50 p-4">
      <h3 class="text-sm font-medium text-red-800">載入角色失敗</h3>
      <p class="mt-2 text-sm text-red-700">{{ loadError }}</p>
      <div class="mt-4">
        <button
          type="button"
          @click="loadRole"
          class="text-sm font-medium text-red-800 hover:text-red-700"
        >
          重試
        </button>
      </div>
    </div>

    <!-- Role details -->
    <template v-else-if="role">
      <!-- System role warning -->
      <div v-if="role.is_system" class="mb-6 rounded-md bg-yellow-50 border border-yellow-200 p-4">
        <div class="flex">
          <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <div class="ml-3">
            <p class="text-sm text-yellow-700">
              這是系統角色，權限由系統預設。
            </p>
          </div>
        </div>
      </div>

      <!-- 基本資訊 -->
      <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">基本資訊</h2>
        </div>
        <div class="px-6 py-4">
          <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
              <dt class="text-sm font-medium text-gray-500">角色名稱</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ role.name }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">顯示名稱</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ role.display_name }}</dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-sm font-medium text-gray-500">描述</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ role.description || '-' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">類型</dt>
              <dd class="mt-1">
                <span
                  v-if="role.is_system"
                  class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-blue-800 bg-blue-100 rounded-full"
                >
                  系統角色
                </span>
                <span v-else class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-gray-800 bg-gray-100 rounded-full">
                  自訂角色
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">狀態</dt>
              <dd class="mt-1">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold leading-5 rounded-full',
                    !role.deleted_at ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'
                  ]"
                >
                  {{ !role.deleted_at ? '啟用' : '停用' }}
                </span>
              </dd>
            </div>
            <div v-if="role.parent_role_name">
              <dt class="text-sm font-medium text-gray-500">父角色</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ role.parent_role_name }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">建立時間</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDate(role.created_at) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">更新時間</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDate(role.updated_at) }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- 權限清單 -->
      <div v-if="role.permissions && role.permissions.length > 0" class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">權限清單 ({{ role.permissions.length }})</h2>
        </div>
        <div class="px-6 py-4">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="permission in role.permissions"
              :key="permission.id"
              class="flex items-center p-3 border border-gray-200 rounded-lg"
            >
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ permission.display_name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ permission.name }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 無權限提示 -->
      <div v-else class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">權限清單</h2>
        </div>
        <div class="px-6 py-8 text-center">
          <p class="text-sm text-gray-500">此角色尚未設定任何權限</p>
        </div>
      </div>

      <!-- 條件規則 -->
      <div v-if="role.condition_rules && role.condition_rules.length > 0" class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">條件限制 ({{ role.condition_rules.length }})</h2>
        </div>
        <div class="px-6 py-4">
          <div class="space-y-3">
            <div
              v-for="(rule, index) in role.condition_rules"
              :key="index"
              class="p-3 border border-gray-200 rounded-lg bg-gray-50"
            >
              <p class="text-sm font-medium text-gray-900">
                {{ rule.field_name || rule.condition_type }} {{ getOperatorLabel(rule.operator) }} {{ rule.condition_value }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </LayoutContentArea>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useRoles } from '~/composables/useRoles'
import type { Role } from '~/stores/roles'

definePageMeta({
  title: '角色詳情',
})

const route = useRoute()
const { getRoleById } = useRoles()

const roleId = computed(() => parseInt(route.params.id as string))
const role = ref<Role | null>(null)
const initialLoading = ref(true)
const loadError = ref<string | null>(null)

/**
 * Load role data
 */
const loadRole = async () => {
  initialLoading.value = true
  loadError.value = null

  try {
    const roleData = await getRoleById(roleId.value)
    role.value = roleData
  } catch (err) {
    console.error('Failed to load role:', err)
    loadError.value = (err as Error).message
  } finally {
    initialLoading.value = false
  }
}

/**
 * Format date
 */
const formatDate = (dateString?: string) => {
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
 * Get operator label
 */
const getOperatorLabel = (operator: string) => {
  const labels: Record<string, string> = {
    '==': '等於',
    '!=': '不等於',
    '>': '大於',
    '>=': '大於等於',
    '<': '小於',
    '<=': '小於等於',
    'in': '包含於',
    'not_in': '不包含於',
    'contains': '包含',
    'not_contains': '不包含',
  }
  return labels[operator] || operator
}

// Load role on mount
onMounted(() => {
  loadRole()
})
</script>
