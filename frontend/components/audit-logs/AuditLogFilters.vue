<template>
  <div class="bg-white shadow rounded-lg p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-medium text-gray-900">篩選條件</h3>
      <button
        type="button"
        @click="toggleExpanded"
        class="text-sm text-blue-600 hover:text-blue-800"
      >
        {{ expanded ? '收起' : '展開' }}
      </button>
    </div>

    <div v-show="expanded" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Action Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            操作類型
          </label>
          <select
            v-model="localFilters.action"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">全部操作</option>
            <option value="role_created">建立角色</option>
            <option value="role_updated">更新角色</option>
            <option value="role_deleted">刪除角色</option>
            <option value="role_assigned">指派角色</option>
            <option value="role_revoked">撤銷角色</option>
            <option value="role_validity_modified">修改角色有效期</option>
            <option value="role_bulk_assigned">批次指派角色</option>
            <option value="permission_assigned">指派權限</option>
            <option value="permission_revoked">撤銷權限</option>
            <option value="user_login">使用者登入</option>
            <option value="user_logout">使用者登出</option>
            <option value="audit_logs_exported">匯出審計記錄</option>
          </select>
        </div>

        <!-- Resource Type Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            資源類型
          </label>
          <select
            v-model="localFilters.resource_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">全部資源</option>
            <option value="role">角色</option>
            <option value="permission">權限</option>
            <option value="role_assignment">角色指派</option>
            <option value="user">使用者</option>
            <option value="audit_log">審計記錄</option>
            <option value="customer">客戶</option>
            <option value="order">訂單</option>
          </select>
        </div>

        <!-- Start Date Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            開始日期
          </label>
          <input
            v-model="localFilters.start_date"
            type="datetime-local"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- End Date Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            結束日期
          </label>
          <input
            v-model="localFilters.end_date"
            type="datetime-local"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- User ID Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            使用者 ID (選填)
          </label>
          <input
            v-model.number="localFilters.user_id"
            type="number"
            placeholder="輸入使用者 ID"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Search Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            關鍵字搜尋
          </label>
          <input
            v-model="localFilters.search"
            type="text"
            placeholder="搜尋詳細資訊..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- Quick Date Filters -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          快速日期篩選
        </label>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            @click="setQuickDateRange('today')"
            class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md hover:bg-gray-50"
          >
            今天
          </button>
          <button
            type="button"
            @click="setQuickDateRange('yesterday')"
            class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md hover:bg-gray-50"
          >
            昨天
          </button>
          <button
            type="button"
            @click="setQuickDateRange('last7days')"
            class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md hover:bg-gray-50"
          >
            最近 7 天
          </button>
          <button
            type="button"
            @click="setQuickDateRange('last30days')"
            class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md hover:bg-gray-50"
          >
            最近 30 天
          </button>
          <button
            type="button"
            @click="setQuickDateRange('thisMonth')"
            class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md hover:bg-gray-50"
          >
            本月
          </button>
          <button
            type="button"
            @click="setQuickDateRange('lastMonth')"
            class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md hover:bg-gray-50"
          >
            上個月
          </button>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
        <button
          type="button"
          @click="handleReset"
          class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          重置
        </button>
        <button
          type="button"
          @click="handleApply"
          class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700"
        >
          套用篩選
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { AuditLogFilters } from '~/composables/useAuditLogs'

interface Props {
  modelValue: AuditLogFilters
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: AuditLogFilters): void
  (e: 'apply'): void
  (e: 'reset'): void
}>()

const expanded = ref(true)
const localFilters = ref<AuditLogFilters>({ ...props.modelValue })

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localFilters.value = { ...newValue }
}, { deep: true })

/**
 * Toggle expanded state
 */
const toggleExpanded = () => {
  expanded.value = !expanded.value
}

/**
 * Set quick date range
 */
const setQuickDateRange = (range: string) => {
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())

  let startDate: Date
  let endDate: Date = new Date(today.getTime() + 86400000 - 1000) // End of today

  switch (range) {
    case 'today':
      startDate = today
      break
    case 'yesterday':
      startDate = new Date(today.getTime() - 86400000)
      endDate = new Date(today.getTime() - 1000)
      break
    case 'last7days':
      startDate = new Date(today.getTime() - 6 * 86400000)
      break
    case 'last30days':
      startDate = new Date(today.getTime() - 29 * 86400000)
      break
    case 'thisMonth':
      startDate = new Date(now.getFullYear(), now.getMonth(), 1)
      break
    case 'lastMonth':
      startDate = new Date(now.getFullYear(), now.getMonth() - 1, 1)
      endDate = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59)
      break
    default:
      return
  }

  localFilters.value.start_date = formatDateTimeLocal(startDate)
  localFilters.value.end_date = formatDateTimeLocal(endDate)
}

/**
 * Format date for datetime-local input
 */
const formatDateTimeLocal = (date: Date): string => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')

  return `${year}-${month}-${day}T${hours}:${minutes}`
}

/**
 * Handle apply filters
 */
const handleApply = () => {
  // Convert datetime-local format to MySQL datetime format
  const filters = { ...localFilters.value }

  if (filters.start_date) {
    filters.start_date = filters.start_date.replace('T', ' ') + ':00'
  }

  if (filters.end_date) {
    filters.end_date = filters.end_date.replace('T', ' ') + ':00'
  }

  emit('update:modelValue', filters)
  emit('apply')
}

/**
 * Handle reset filters
 */
const handleReset = () => {
  localFilters.value = {
    page: 1,
    per_page: 20,
    sort: 'audit_logs.created_at',
    order: 'DESC',
  }
  emit('reset')
}
</script>
