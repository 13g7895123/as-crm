<template>
  <div class="audit-log-statistics-page">
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <NuxtLink to="/audit-logs" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            返回審計記錄列表
          </NuxtLink>
          <h1 class="text-2xl font-bold text-gray-900">審計記錄統計分析</h1>
          <p class="mt-1 text-sm text-gray-500">查看系統操作統計和活動趨勢</p>
        </div>
      </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            開始日期
          </label>
          <input
            v-model="filters.start_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            結束日期
          </label>
          <input
            v-model="filters.end_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="flex items-end">
          <button
            type="button"
            @click="loadStatistics"
            :disabled="loading"
            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            {{ loading ? '載入中...' : '套用' }}
          </button>
        </div>
      </div>

      <!-- Quick Filters -->
      <div class="mt-4 flex flex-wrap gap-2">
        <button
          v-for="range in quickRanges"
          :key="range.key"
          type="button"
          @click="setQuickRange(range.key)"
          class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-md hover:bg-gray-50"
        >
          {{ range.label }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">載入統計資料中...</p>
    </div>

    <!-- Statistics Content -->
    <div v-else-if="summary" class="space-y-6">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">總記錄數</p>
              <p class="text-2xl font-bold text-gray-900">{{ summary.total_logs.toLocaleString() }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">活躍使用者</p>
              <p class="text-2xl font-bold text-gray-900">{{ summary.top_users.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-purple-100 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">操作類型</p>
              <p class="text-2xl font-bold text-gray-900">{{ summary.top_actions.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-yellow-100 rounded-lg">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">最常見操作</p>
              <p class="text-sm font-bold text-gray-900">
                {{ summary.top_actions[0] ? formatAction(summary.top_actions[0].action) : '-' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Actions Chart -->
      <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">操作類型統計 (前 10 名)</h2>
        <div class="space-y-3">
          <div
            v-for="(stat, index) in topActions"
            :key="stat.action"
            class="flex items-center"
          >
            <div class="w-24 text-sm text-gray-500 flex-shrink-0">
              {{ formatAction(stat.action) }}
            </div>
            <div class="flex-1 ml-4">
              <div class="flex items-center">
                <div class="flex-1 bg-gray-200 rounded-full h-6 relative overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="getBarColor(index)"
                    :style="{ width: getPercentage(stat.count, maxActionCount) + '%' }"
                  ></div>
                </div>
                <div class="ml-3 text-sm font-medium text-gray-900 w-16 text-right">
                  {{ stat.count.toLocaleString() }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Users Table -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">最活躍使用者 (前 10 名)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  排名
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  使用者
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  操作次數
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  比例
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  操作
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(user, index) in topUsers" :key="user.user_id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div class="flex items-center">
                    <span
                      v-if="index < 3"
                      class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold text-white"
                      :class="{
                        'bg-yellow-500': index === 0,
                        'bg-gray-400': index === 1,
                        'bg-orange-600': index === 2,
                      }"
                    >
                      {{ index + 1 }}
                    </span>
                    <span v-else class="text-gray-500">{{ index + 1 }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ user.username }}</div>
                  <div class="text-sm text-gray-500">{{ user.full_name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ user.action_count.toLocaleString() }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ getPercentage(user.action_count, summary.total_logs).toFixed(1) }}%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <NuxtLink
                    :to="`/audit-logs?user_id=${user.user_id}`"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    查看記錄
                  </NuxtLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuditLogs } from '~/composables/useAuditLogs'
import type { ActionStatistic, UserActivityStatistic } from '~/composables/useAuditLogs'

definePageMeta({
  title: '審計記錄統計',
})

const { loading, getSummary, formatAction } = useAuditLogs()

const filters = ref({
  start_date: '',
  end_date: '',
})

const summary = ref<any>(null)

const quickRanges = [
  { key: 'today', label: '今天' },
  { key: 'last7days', label: '最近 7 天' },
  { key: 'last30days', label: '最近 30 天' },
  { key: 'thisMonth', label: '本月' },
  { key: 'lastMonth', label: '上個月' },
]

/**
 * Top actions (limit to 10)
 */
const topActions = computed(() => {
  if (!summary.value?.top_actions) return []
  return summary.value.top_actions.slice(0, 10)
})

/**
 * Top users (limit to 10)
 */
const topUsers = computed(() => {
  if (!summary.value?.top_users) return []
  return summary.value.top_users.slice(0, 10)
})

/**
 * Max action count for chart scaling
 */
const maxActionCount = computed(() => {
  if (topActions.value.length === 0) return 1
  return Math.max(...topActions.value.map((s: ActionStatistic) => s.count))
})

/**
 * Get percentage
 */
const getPercentage = (value: number, total: number): number => {
  if (total === 0) return 0
  return (value / total) * 100
}

/**
 * Get bar color for chart
 */
const getBarColor = (index: number): string => {
  const colors = [
    'bg-blue-500',
    'bg-green-500',
    'bg-yellow-500',
    'bg-purple-500',
    'bg-pink-500',
    'bg-indigo-500',
    'bg-red-500',
    'bg-orange-500',
    'bg-teal-500',
    'bg-cyan-500',
  ]
  return colors[index % colors.length]
}

/**
 * Set quick date range
 */
const setQuickRange = (range: string) => {
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())

  let startDate: Date
  let endDate: Date = new Date()

  switch (range) {
    case 'today':
      startDate = today
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
      endDate = new Date(now.getFullYear(), now.getMonth(), 0)
      break
    default:
      return
  }

  filters.value.start_date = formatDateInput(startDate)
  filters.value.end_date = formatDateInput(endDate)

  loadStatistics()
}

/**
 * Format date for date input
 */
const formatDateInput = (date: Date): string => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

/**
 * Load statistics
 */
const loadStatistics = async () => {
  try {
    const queryFilters: any = {}

    if (filters.value.start_date) {
      queryFilters.start_date = filters.value.start_date + ' 00:00:00'
    }

    if (filters.value.end_date) {
      queryFilters.end_date = filters.value.end_date + ' 23:59:59'
    }

    summary.value = await getSummary(queryFilters)
  } catch (err) {
    console.error('Failed to load statistics:', err)
  }
}

// Load statistics on mount (last 30 days)
onMounted(() => {
  setQuickRange('last30days')
})
</script>

<style scoped>
.audit-log-statistics-page {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8;
}
</style>
