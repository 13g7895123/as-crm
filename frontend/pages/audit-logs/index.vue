<template>
  <LayoutContentArea title="審計記錄" subtitle="查看系統操作記錄和活動追蹤">
    <template #actions>
      <div class="flex items-center gap-3">
        <!-- Export Button -->
        <button
          type="button"
          @click="showExportDialog = true"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          匯出
        </button>

        <!-- Statistics Link -->
        <NuxtLink
          to="/audit-logs/statistics"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            統計分析
          </NuxtLink>
      </div>
    </template>

    <!-- Filters -->
    <AuditLogFiltersComponent
      v-model="filters"
      @apply="applyFilters"
      @reset="resetFilters"
    />

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">載入中...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="rounded-md bg-red-50 p-4">
      <p class="text-sm text-red-800">{{ error }}</p>
    </div>

    <!-- Audit Logs Table -->
    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                時間
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                使用者
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                操作
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                資源
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                IP 位址
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                操作
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="logs.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                沒有找到審計記錄
              </td>
            </tr>
            <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDateTime(log.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ log.username || '系統' }}
                </div>
                <div v-if="log.full_name" class="text-sm text-gray-500">
                  {{ log.full_name }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ formatAction(log.action) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div>{{ formatResourceType(log.resource_type) }}</div>
                <div v-if="log.resource_id" class="text-xs text-gray-500">
                  ID: {{ log.resource_id }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ log.ip_address || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <NuxtLink
                  :to="`/audit-logs/${log.id}`"
                  class="text-blue-600 hover:text-blue-900"
                >
                  詳細
                </NuxtLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total_pages > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            上一頁
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.total_pages"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            下一頁
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              顯示第
              <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span>
              至
              <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
              筆，共
              <span class="font-medium">{{ pagination.total }}</span>
              筆記錄
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
              >
                上一頁
              </button>
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="changePage(page)"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  page === pagination.current_page
                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                ]"
              >
                {{ page }}
              </button>
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.total_pages"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
              >
                下一頁
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Dialog -->
    <div
      v-if="showExportDialog"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showExportDialog = false"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">匯出審計記錄</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              匯出格式
            </label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  v-model="exportFormat"
                  type="radio"
                  value="csv"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">CSV (逗號分隔值)</span>
              </label>
              <label class="flex items-center">
                <input
                  v-model="exportFormat"
                  type="radio"
                  value="json"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">JSON (JavaScript 物件)</span>
              </label>
            </div>
          </div>

          <div class="text-sm text-gray-500">
            將匯出目前篩選條件下的所有記錄
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button
            type="button"
            @click="showExportDialog = false"
            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            取消
          </button>
          <button
            type="button"
            @click="handleExport"
            :disabled="exporting"
            class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
          >
            {{ exporting ? '匯出中...' : '確認匯出' }}
          </button>
        </div>
      </div>
    </div>
  </LayoutContentArea>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuditLogs } from '~/composables/useAuditLogs'
import type { AuditLogFilters, AuditLog } from '~/composables/useAuditLogs'
import AuditLogFiltersComponent from '~/components/audit-logs/AuditLogFilters.vue'

definePageMeta({
  title: '審計記錄',
})

const {
  logs,
  loading,
  error,
  getLogs,
  exportLogs,
  formatAction,
  formatResourceType,
  formatDateTime,
} = useAuditLogs()

const filters = ref<AuditLogFilters>({
  page: 1,
  per_page: 20,
  sort: 'audit_logs.created_at',
  order: 'DESC',
})

const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
})

const showExportDialog = ref(false)
const exportFormat = ref<'csv' | 'json'>('csv')
const exporting = ref(false)

/**
 * Visible page numbers for pagination
 */
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const total = pagination.value.total_pages
  const pages: number[] = []

  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i)
      }
      pages.push(-1) // Ellipsis
      pages.push(total)
    } else if (current >= total - 3) {
      pages.push(1)
      pages.push(-1) // Ellipsis
      for (let i = total - 4; i <= total; i++) {
        pages.push(i)
      }
    } else {
      pages.push(1)
      pages.push(-1) // Ellipsis
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i)
      }
      pages.push(-1) // Ellipsis
      pages.push(total)
    }
  }

  return pages
})

/**
 * Load audit logs
 */
const loadLogs = async () => {
  try {
    const result = await getLogs(filters.value)
    pagination.value = result.meta
  } catch (err) {
    console.error('Failed to load audit logs:', err)
  }
}

/**
 * Apply filters
 */
const applyFilters = () => {
  filters.value.page = 1
  loadLogs()
}

/**
 * Reset filters
 */
const resetFilters = () => {
  filters.value = {
    page: 1,
    per_page: 20,
    sort: 'audit_logs.created_at',
    order: 'DESC',
  }
  loadLogs()
}

/**
 * Change page
 */
const changePage = (page: number) => {
  if (page < 1 || page > pagination.value.total_pages) return
  filters.value.page = page
  loadLogs()
}

/**
 * Handle export
 */
const handleExport = async () => {
  exporting.value = true

  try {
    const blob = await exportLogs(exportFormat.value, filters.value)

    // Create download link
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `audit_logs_${new Date().toISOString().split('T')[0]}.${exportFormat.value}`
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)

    showExportDialog.value = false
  } catch (err) {
    alert('匯出失敗: ' + (err as Error).message)
  } finally {
    exporting.value = false
  }
}

// Load logs on mount
onMounted(() => {
  loadLogs()
})
</script>

<style scoped>
.audit-logs-page {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8;
}
</style>
