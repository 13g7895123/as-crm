<template>
  <div class="audit-log-table">
    <!-- Table Container -->
    <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <!-- Table Header -->
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              時間
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              操作者
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              操作類型
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              目標
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              結果
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              詳情
            </th>
          </tr>
        </thead>

        <!-- Table Body -->
        <tbody class="bg-white divide-y divide-gray-200">
          <!-- Loading State -->
          <tr v-if="loading">
            <td colspan="6" class="px-6 py-12 text-center">
              <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
              <p class="mt-2 text-sm text-gray-500">載入中...</p>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-else-if="logs.length === 0">
            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
              沒有找到審計記錄
            </td>
          </tr>

          <!-- Data Rows -->
          <tr
            v-for="log in logs"
            :key="log.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="selectLog(log)"
          >
            <!-- Timestamp -->
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ formatDateTime(log.created_at) }}
            </td>

            <!-- User -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">
                {{ log.username || log.user_id }}
              </div>
              <div class="text-sm text-gray-500">
                {{ log.email }}
              </div>
            </td>

            <!-- Action -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full" :class="getActionClass(log.action)">
                {{ getActionText(log.action) }}
              </span>
            </td>

            <!-- Target -->
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <div>{{ getTargetTypeText(log.target_type) }}</div>
              <div v-if="log.target_id" class="text-xs text-gray-400">
                ID: {{ log.target_id }}
              </div>
            </td>

            <!-- Result -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full" :class="getResultClass(log.result)">
                {{ getResultText(log.result) }}
              </span>
            </td>

            <!-- Details -->
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <button
                @click.stop="viewDetails(log)"
                class="text-indigo-600 hover:text-indigo-900"
              >
                檢視
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="mt-4 flex items-center justify-between">
      <div class="text-sm text-gray-700">
        顯示第 {{ startIndex }} 至 {{ endIndex }} 筆，共 {{ totalRecords }} 筆
      </div>

      <div class="flex space-x-2">
        <button
          @click="previousPage"
          :disabled="currentPage === 1"
          class="px-3 py-2 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          上一頁
        </button>

        <button
          v-for="page in visiblePages"
          :key="page"
          @click="goToPage(page)"
          :class="[
            'px-3 py-2 text-sm font-medium rounded-md border',
            page === currentPage
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
          ]"
        >
          {{ page }}
        </button>

        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="px-3 py-2 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          下一頁
        </button>
      </div>
    </div>

    <!-- Detail Modal -->
    <div
      v-if="selectedLog"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex items-center justify-center min-h-screen px-4">
        <!-- Backdrop -->
        <div
          @click="closeDetails"
          class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        ></div>

        <!-- Modal Content -->
        <div class="relative bg-white rounded-lg max-w-3xl w-full max-h-[80vh] overflow-y-auto">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">審計記錄詳情</h3>
          </div>

          <div class="px-6 py-4 space-y-4">
            <!-- Basic Info -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">時間</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(selectedLog.created_at) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">操作者</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ selectedLog.username || selectedLog.user_id }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">操作類型</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ getActionText(selectedLog.action) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">結果</dt>
                <dd class="mt-1">
                  <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full" :class="getResultClass(selectedLog.result)">
                    {{ getResultText(selectedLog.result) }}
                  </span>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">IP 位址</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ selectedLog.ip_address }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Request ID</dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ selectedLog.request_id || '-' }}</dd>
              </div>
            </div>

            <!-- Changes (for update operations) -->
            <div v-if="selectedLog.old_values || selectedLog.new_values">
              <h4 class="text-sm font-medium text-gray-900 mb-2">資料變更</h4>

              <div class="grid grid-cols-2 gap-4">
                <div v-if="selectedLog.old_values">
                  <dt class="text-sm font-medium text-gray-500">修改前</dt>
                  <dd class="mt-1 text-sm bg-red-50 border border-red-200 rounded p-2">
                    <pre class="text-xs">{{ formatJSON(selectedLog.old_values) }}</pre>
                  </dd>
                </div>

                <div v-if="selectedLog.new_values">
                  <dt class="text-sm font-medium text-gray-500">修改後</dt>
                  <dd class="mt-1 text-sm bg-green-50 border border-green-200 rounded p-2">
                    <pre class="text-xs">{{ formatJSON(selectedLog.new_values) }}</pre>
                  </dd>
                </div>
              </div>
            </div>
          </div>

          <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
            <button
              @click="closeDetails"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
              關閉
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface AuditLog {
  id: number
  user_id: number
  username?: string
  email?: string
  action: string
  target_type: string
  target_id?: number
  old_values?: any
  new_values?: any
  result: string
  ip_address: string
  user_agent?: string
  request_id?: string
  created_at: string
}

interface Props {
  logs: AuditLog[]
  loading?: boolean
  currentPage?: number
  totalPages?: number
  totalRecords?: number
  perPage?: number
}

interface Emits {
  (e: 'page-change', page: number): void
  (e: 'log-select', log: AuditLog): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  currentPage: 1,
  totalPages: 1,
  totalRecords: 0,
  perPage: 20,
})

const emit = defineEmits<Emits>()

const selectedLog = ref<AuditLog | null>(null)

const startIndex = computed(() => (props.currentPage - 1) * props.perPage + 1)
const endIndex = computed(() => Math.min(props.currentPage * props.perPage, props.totalRecords))

const visiblePages = computed(() => {
  const pages: number[] = []
  const maxVisible = 5
  let start = Math.max(1, props.currentPage - Math.floor(maxVisible / 2))
  let end = Math.min(props.totalPages, start + maxVisible - 1)

  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const formatDateTime = (datetime: string): string => {
  if (!datetime) return '-'
  const date = new Date(datetime)
  return date.toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

const getActionText = (action: string): string => {
  const texts: Record<string, string> = {
    view: '檢視',
    create: '建立',
    update: '更新',
    delete: '刪除',
    export: '匯出',
    assign: '指派',
    role_assigned: '角色指派',
    role_revoked: '撤銷角色',
    permission_check_failed: '權限檢查失敗',
  }
  return texts[action] || action
}

const getActionClass = (action: string): string => {
  if (action === 'create') return 'bg-green-100 text-green-800'
  if (action === 'update') return 'bg-blue-100 text-blue-800'
  if (action === 'delete') return 'bg-red-100 text-red-800'
  if (action === 'view') return 'bg-gray-100 text-gray-800'
  return 'bg-purple-100 text-purple-800'
}

const getTargetTypeText = (type: string): string => {
  const texts: Record<string, string> = {
    role: '角色',
    user: '使用者',
    permission: '權限',
    customer: '客戶',
    order: '訂單',
    user_permission: '使用者權限',
  }
  return texts[type] || type
}

const getResultText = (result: string): string => {
  const texts: Record<string, string> = {
    success: '成功',
    failed: '失敗',
    denied: '拒絕',
  }
  return texts[result] || result
}

const getResultClass = (result: string): string => {
  if (result === 'success') return 'bg-green-100 text-green-800'
  if (result === 'failed') return 'bg-red-100 text-red-800'
  if (result === 'denied') return 'bg-yellow-100 text-yellow-800'
  return 'bg-gray-100 text-gray-800'
}

const formatJSON = (obj: any): string => {
  if (typeof obj === 'string') {
    try {
      obj = JSON.parse(obj)
    } catch (e) {
      return obj
    }
  }
  return JSON.stringify(obj, null, 2)
}

const selectLog = (log: AuditLog) => {
  emit('log-select', log)
}

const viewDetails = (log: AuditLog) => {
  selectedLog.value = log
}

const closeDetails = () => {
  selectedLog.value = null
}

const previousPage = () => {
  if (props.currentPage > 1) {
    emit('page-change', props.currentPage - 1)
  }
}

const nextPage = () => {
  if (props.currentPage < props.totalPages) {
    emit('page-change', props.currentPage + 1)
  }
}

const goToPage = (page: number) => {
  emit('page-change', page)
}
</script>

<style scoped>
.audit-log-table {
  @apply w-full;
}

pre {
  @apply whitespace-pre-wrap break-words;
}
</style>
