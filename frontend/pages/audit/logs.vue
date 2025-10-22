<template>
  <div class="audit-logs-page">
    <LayoutContentArea title="審計記錄" subtitle="查詢和檢視系統操作記錄">
      <!-- Filter Section -->
      <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- User Filter -->
          <div>
            <label for="user-filter" class="block text-sm font-medium text-gray-700 mb-1">
              操作者
            </label>
            <input
              id="user-filter"
              v-model="filters.username"
              type="text"
              placeholder="搜尋使用者名稱或信箱"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <!-- Action Filter -->
          <div>
            <label for="action-filter" class="block text-sm font-medium text-gray-700 mb-1">
              操作類型
            </label>
            <select
              id="action-filter"
              v-model="filters.action"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">全部</option>
              <option value="view">檢視</option>
              <option value="create">建立</option>
              <option value="update">更新</option>
              <option value="delete">刪除</option>
              <option value="export">匯出</option>
              <option value="assign">指派</option>
              <option value="role_assigned">角色指派</option>
              <option value="role_revoked">撤銷角色</option>
            </select>
          </div>

          <!-- Target Type Filter -->
          <div>
            <label for="target-filter" class="block text-sm font-medium text-gray-700 mb-1">
              目標類型
            </label>
            <select
              id="target-filter"
              v-model="filters.target_type"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">全部</option>
              <option value="role">角色</option>
              <option value="user">使用者</option>
              <option value="permission">權限</option>
              <option value="customer">客戶</option>
              <option value="order">訂單</option>
              <option value="user_permission">使用者權限</option>
            </select>
          </div>

          <!-- Result Filter -->
          <div>
            <label for="result-filter" class="block text-sm font-medium text-gray-700 mb-1">
              結果
            </label>
            <select
              id="result-filter"
              v-model="filters.result"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">全部</option>
              <option value="success">成功</option>
              <option value="failed">失敗</option>
              <option value="denied">拒絕</option>
            </select>
          </div>

          <!-- Start Date -->
          <div>
            <label for="start-date" class="block text-sm font-medium text-gray-700 mb-1">
              開始日期
            </label>
            <input
              id="start-date"
              v-model="filters.start_date"
              type="datetime-local"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <!-- End Date -->
          <div>
            <label for="end-date" class="block text-sm font-medium text-gray-700 mb-1">
              結束日期
            </label>
            <input
              id="end-date"
              v-model="filters.end_date"
              type="datetime-local"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <!-- Target ID -->
          <div>
            <label for="target-id" class="block text-sm font-medium text-gray-700 mb-1">
              目標 ID
            </label>
            <input
              id="target-id"
              v-model="filters.target_id"
              type="number"
              placeholder="輸入 ID"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <!-- IP Address -->
          <div>
            <label for="ip-address" class="block text-sm font-medium text-gray-700 mb-1">
              IP 位址
            </label>
            <input
              id="ip-address"
              v-model="filters.ip_address"
              type="text"
              placeholder="輸入 IP"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 flex items-center justify-between">
          <div class="flex space-x-2">
            <button
              @click="applyFilters"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              查詢
            </button>
            <button
              @click="resetFilters"
              class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              重置
            </button>
          </div>

          <div class="flex space-x-2">
            <button
              @click="exportLogs"
              :disabled="exporting || logs.length === 0"
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
              <svg v-if="!exporting" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <div v-else class="inline-block h-4 w-4 mr-2 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"></div>
              {{ exporting ? '匯出中...' : '匯出 CSV' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Statistics -->
      <div v-if="statistics" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
          <div class="text-sm font-medium text-blue-900">總記錄數</div>
          <div class="mt-2 text-3xl font-bold text-blue-600">{{ statistics.total_records?.toLocaleString() || 0 }}</div>
        </div>
        <div class="bg-green-50 rounded-lg border border-green-200 p-4">
          <div class="text-sm font-medium text-green-900">成功操作</div>
          <div class="mt-2 text-3xl font-bold text-green-600">{{ statistics.success_count?.toLocaleString() || 0 }}</div>
        </div>
        <div class="bg-red-50 rounded-lg border border-red-200 p-4">
          <div class="text-sm font-medium text-red-900">失敗操作</div>
          <div class="mt-2 text-3xl font-bold text-red-600">{{ statistics.failed_count?.toLocaleString() || 0 }}</div>
        </div>
        <div class="bg-yellow-50 rounded-lg border border-yellow-200 p-4">
          <div class="text-sm font-medium text-yellow-900">拒絕操作</div>
          <div class="mt-2 text-3xl font-bold text-yellow-600">{{ statistics.denied_count?.toLocaleString() || 0 }}</div>
        </div>
      </div>

      <!-- Audit Log Table -->
      <AuditLogTable
        :logs="logs"
        :loading="loading"
        :current-page="pagination.current_page"
        :total-pages="pagination.total_pages"
        :total-records="pagination.total_records"
        :per-page="pagination.per_page"
        @page-change="handlePageChange"
        @log-select="handleLogSelect"
      />
    </LayoutContentArea>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { useAuditLogs } from '~/composables/useAuditLogs'

definePageMeta({
  title: '審計記錄',
  layout: 'default',
  middleware: 'permission',
  permission: 'audit_log:view',
})

const { getAuditLogs, exportAuditLogs, getAuditStatistics } = useAuditLogs()

// State
const loading = ref(false)
const exporting = ref(false)
const logs = ref<any[]>([])
const statistics = ref<any>(null)

// Filters
const filters = reactive({
  username: '',
  action: '',
  target_type: '',
  target_id: null as number | null,
  result: '',
  start_date: '',
  end_date: '',
  ip_address: '',
})

// Pagination
const pagination = reactive({
  current_page: 1,
  total_pages: 1,
  total_records: 0,
  per_page: 20,
})

/**
 * Load audit logs with current filters
 */
const loadLogs = async () => {
  loading.value = true

  try {
    const queryParams: Record<string, any> = {
      page: pagination.current_page,
      per_page: pagination.per_page,
    }

    // Add filters
    if (filters.username) queryParams.username = filters.username
    if (filters.action) queryParams.action = filters.action
    if (filters.target_type) queryParams.target_type = filters.target_type
    if (filters.target_id) queryParams.target_id = filters.target_id
    if (filters.result) queryParams.result = filters.result
    if (filters.start_date) queryParams.start_date = filters.start_date
    if (filters.end_date) queryParams.end_date = filters.end_date
    if (filters.ip_address) queryParams.ip_address = filters.ip_address

    const response = await getAuditLogs(queryParams)

    logs.value = response.data || []
    pagination.current_page = response.pagination?.current_page || 1
    pagination.total_pages = response.pagination?.total_pages || 1
    pagination.total_records = response.pagination?.total_records || 0
    pagination.per_page = response.pagination?.per_page || 20
  } catch (error: any) {
    console.error('Failed to load audit logs:', error)
    // TODO: Show error notification
  } finally {
    loading.value = false
  }
}

/**
 * Load statistics
 */
const loadStatistics = async () => {
  try {
    const queryParams: Record<string, any> = {}

    // Add filters for statistics
    if (filters.start_date) queryParams.start_date = filters.start_date
    if (filters.end_date) queryParams.end_date = filters.end_date
    if (filters.action) queryParams.action = filters.action
    if (filters.target_type) queryParams.target_type = filters.target_type

    statistics.value = await getAuditStatistics(queryParams)
  } catch (error: any) {
    console.error('Failed to load statistics:', error)
  }
}

/**
 * Apply filters and reload
 */
const applyFilters = () => {
  pagination.current_page = 1 // Reset to first page
  loadLogs()
  loadStatistics()
}

/**
 * Reset filters
 */
const resetFilters = () => {
  filters.username = ''
  filters.action = ''
  filters.target_type = ''
  filters.target_id = null
  filters.result = ''
  filters.start_date = ''
  filters.end_date = ''
  filters.ip_address = ''

  applyFilters()
}

/**
 * Handle page change
 */
const handlePageChange = (page: number) => {
  pagination.current_page = page
  loadLogs()
}

/**
 * Handle log selection
 */
const handleLogSelect = (log: any) => {
  // Optional: Emit event or navigate to detail page
  console.log('Log selected:', log)
}

/**
 * Export logs to CSV
 */
const exportLogs = async () => {
  if (exporting.value) return

  exporting.value = true

  try {
    const queryParams: Record<string, any> = {}

    // Add filters for export
    if (filters.username) queryParams.username = filters.username
    if (filters.action) queryParams.action = filters.action
    if (filters.target_type) queryParams.target_type = filters.target_type
    if (filters.target_id) queryParams.target_id = filters.target_id
    if (filters.result) queryParams.result = filters.result
    if (filters.start_date) queryParams.start_date = filters.start_date
    if (filters.end_date) queryParams.end_date = filters.end_date
    if (filters.ip_address) queryParams.ip_address = filters.ip_address

    const blob = await exportAuditLogs(queryParams)

    // Create download link
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `audit-logs-${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    // TODO: Show success notification
  } catch (error: any) {
    console.error('Failed to export logs:', error)
    // TODO: Show error notification
  } finally {
    exporting.value = false
  }
}

// Load on mount
onMounted(() => {
  loadLogs()
  loadStatistics()
})
</script>

<style scoped>
.audit-logs-page {
  @apply p-6;
}
</style>
