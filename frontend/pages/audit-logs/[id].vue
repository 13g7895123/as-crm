<template>
  <div class="audit-log-detail-page">
    <!-- Breadcrumb -->
    <div class="mb-6">
      <NuxtLink to="/audit-logs" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        返回審計記錄列表
      </NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">載入中...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="rounded-md bg-red-50 p-4">
      <h3 class="text-sm font-medium text-red-800">載入失敗</h3>
      <p class="mt-2 text-sm text-red-700">{{ error }}</p>
    </div>

    <!-- Log Detail -->
    <div v-else-if="log" class="space-y-6">
      <!-- Header -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">審計記錄詳情</h1>
            <p class="mt-1 text-sm text-gray-500">記錄 ID: {{ log.id }}</p>
          </div>
          <span
            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800"
          >
            {{ formatAction(log.action) }}
          </span>
        </div>
      </div>

      <!-- Basic Information -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">基本資訊</h2>
        </div>
        <div class="p-6">
          <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
              <dt class="text-sm font-medium text-gray-500">操作時間</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(log.created_at) }}</dd>
            </div>

            <div>
              <dt class="text-sm font-medium text-gray-500">執行使用者</dt>
              <dd class="mt-1 text-sm text-gray-900">
                <div class="font-medium">{{ log.username || '系統' }}</div>
                <div v-if="log.full_name" class="text-gray-600">{{ log.full_name }}</div>
                <div v-if="log.email" class="text-gray-500">{{ log.email }}</div>
              </dd>
            </div>

            <div>
              <dt class="text-sm font-medium text-gray-500">操作類型</dt>
              <dd class="mt-1 text-sm text-gray-900">
                {{ formatAction(log.action) }}
                <span class="ml-2 text-gray-500">({{ log.action }})</span>
              </dd>
            </div>

            <div>
              <dt class="text-sm font-medium text-gray-500">資源類型</dt>
              <dd class="mt-1 text-sm text-gray-900">
                {{ formatResourceType(log.resource_type) }}
                <span class="ml-2 text-gray-500">({{ log.resource_type }})</span>
              </dd>
            </div>

            <div>
              <dt class="text-sm font-medium text-gray-500">資源 ID</dt>
              <dd class="mt-1 text-sm text-gray-900">
                {{ log.resource_id || '-' }}
                <NuxtLink
                  v-if="log.resource_id && resourceLink"
                  :to="resourceLink"
                  class="ml-2 text-blue-600 hover:text-blue-800"
                >
                  查看資源
                </NuxtLink>
              </dd>
            </div>

            <div>
              <dt class="text-sm font-medium text-gray-500">IP 位址</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ log.ip_address || '-' }}</dd>
            </div>

            <div class="sm:col-span-2">
              <dt class="text-sm font-medium text-gray-500">使用者代理</dt>
              <dd class="mt-1 text-sm text-gray-900 break-words">
                {{ log.user_agent || '-' }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Details -->
      <div v-if="log.details" class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">詳細資訊</h2>
        </div>
        <div class="p-6">
          <!-- JSON View -->
          <div v-if="isJsonDetails" class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-900">{{ formatJson(log.details) }}</pre>
          </div>

          <!-- Plain Text View -->
          <div v-else class="text-sm text-gray-900">
            {{ log.details }}
          </div>

          <!-- Structured View (if JSON) -->
          <div v-if="isJsonDetails && structuredDetails" class="mt-6">
            <h3 class="text-sm font-medium text-gray-700 mb-3">結構化檢視</h3>
            <dl class="space-y-3">
              <div v-for="(value, key) in structuredDetails" :key="key">
                <dt class="text-sm font-medium text-gray-500">{{ key }}</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <span v-if="typeof value === 'object'">
                    {{ JSON.stringify(value) }}
                  </span>
                  <span v-else>{{ value }}</span>
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Related Resource Audit Trail -->
      <div v-if="log.resource_id && relatedLogs.length > 0" class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">相關資源的審計記錄</h2>
          <p class="mt-1 text-sm text-gray-500">
            此資源的其他操作記錄
          </p>
        </div>
        <div class="divide-y divide-gray-200">
          <div
            v-for="relatedLog in relatedLogs"
            :key="relatedLog.id"
            class="px-6 py-4 hover:bg-gray-50"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                    {{ formatAction(relatedLog.action) }}
                  </span>
                  <span class="text-sm text-gray-900">
                    {{ relatedLog.username || '系統' }}
                  </span>
                  <span class="text-sm text-gray-500">
                    {{ formatDateTime(relatedLog.created_at) }}
                  </span>
                </div>
              </div>
              <NuxtLink
                :to="`/audit-logs/${relatedLog.id}`"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                查看
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuditLogs } from '~/composables/useAuditLogs'
import type { AuditLog } from '~/composables/useAuditLogs'

definePageMeta({
  title: '審計記錄詳情',
})

const route = useRoute()
const {
  loading,
  error,
  getLogById,
  getResourceAuditTrail,
  formatAction,
  formatResourceType,
  formatDateTime,
} = useAuditLogs()

const logId = computed(() => parseInt(route.params.id as string))
const log = ref<AuditLog | null>(null)
const relatedLogs = ref<AuditLog[]>([])

/**
 * Check if details is JSON
 */
const isJsonDetails = computed(() => {
  return typeof log.value?.details === 'object' && log.value?.details !== null
})

/**
 * Structured details for display
 */
const structuredDetails = computed(() => {
  if (!isJsonDetails.value) return null
  return log.value?.details as Record<string, any>
})

/**
 * Resource link
 */
const resourceLink = computed(() => {
  if (!log.value?.resource_type || !log.value?.resource_id) return null

  const linkMap: Record<string, string> = {
    'role': `/roles/${log.value.resource_id}/edit`,
    'permission': `/permissions/${log.value.resource_id}`,
    'role_assignment': `/teams/manage`, // Could be enhanced to highlight specific assignment
    'user': `/users/${log.value.resource_id}`,
  }

  return linkMap[log.value.resource_type] || null
})

/**
 * Format JSON for display
 */
const formatJson = (data: any): string => {
  return JSON.stringify(data, null, 2)
}

/**
 * Load audit log
 */
const loadLog = async () => {
  try {
    log.value = await getLogById(logId.value)

    // Load related logs if resource info available
    if (log.value.resource_type && log.value.resource_id) {
      loadRelatedLogs()
    }
  } catch (err) {
    console.error('Failed to load audit log:', err)
  }
}

/**
 * Load related logs for the same resource
 */
const loadRelatedLogs = async () => {
  if (!log.value?.resource_type || !log.value?.resource_id) return

  try {
    const logs = await getResourceAuditTrail(log.value.resource_type, log.value.resource_id)
    // Exclude current log and limit to 10
    relatedLogs.value = logs
      .filter(l => l.id !== logId.value)
      .slice(0, 10)
  } catch (err) {
    console.error('Failed to load related logs:', err)
  }
}

// Load data on mount
onMounted(() => {
  loadLog()
})
</script>

<style scoped>
.audit-log-detail-page {
  @apply max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8;
}
</style>
