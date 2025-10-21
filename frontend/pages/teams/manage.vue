<template>
  <div class="manage-team-page">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">團隊權限管理</h1>
      <p class="mt-1 text-sm text-gray-500">管理團隊成員的角色和權限指派</p>
    </div>

    <!-- Expiring Roles Alert -->
    <div v-if="expiringRoles.length > 0" class="mb-6 rounded-md bg-yellow-50 p-4 border border-yellow-200">
      <div class="flex">
        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd"
          />
        </svg>
        <div class="ml-3 flex-1">
          <h3 class="text-sm font-medium text-yellow-800">
            {{ expiringRoles.length }} 個角色即將過期
          </h3>
          <div class="mt-2 text-sm text-yellow-700">
            <button
              type="button"
              @click="showExpiringRoles = !showExpiringRoles"
              class="font-medium underline hover:text-yellow-600"
            >
              {{ showExpiringRoles ? '隱藏' : '查看詳情' }}
            </button>
          </div>

          <!-- Expiring Roles List -->
          <div v-if="showExpiringRoles" class="mt-3 space-y-2">
            <div
              v-for="role in expiringRoles"
              :key="role.id"
              class="text-sm bg-white rounded p-2 border border-yellow-100"
            >
              <div class="font-medium">
                {{ role.username }} - {{ role.display_name }}
              </div>
              <div class="text-gray-600 mt-1">
                過期時間: {{ formatExpiryDate(role) }}
                (剩餘 {{ getDaysUntilExpiry(role) }} 天)
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Team Members List -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow">
          <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">團隊成員</h2>
          </div>

          <!-- Search -->
          <div class="p-4 border-b border-gray-200">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="搜尋成員..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Loading State -->
          <div v-if="loadingMembers" class="p-4 text-center">
            <div class="inline-block h-6 w-6 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
            <p class="mt-2 text-sm text-gray-500">載入中...</p>
          </div>

          <!-- Members List -->
          <div v-else class="divide-y divide-gray-200 max-h-[600px] overflow-y-auto">
            <button
              v-for="member in filteredMembers"
              :key="member.id"
              type="button"
              @click="selectMember(member)"
              class="w-full px-4 py-3 text-left hover:bg-gray-50 transition-colors"
              :class="{
                'bg-blue-50 border-l-4 border-blue-500': selectedMember?.id === member.id,
              }"
            >
              <div class="font-medium text-gray-900">{{ member.full_name || member.username }}</div>
              <div class="text-sm text-gray-500">{{ member.email }}</div>
              <div class="mt-1 text-xs text-gray-400">
                {{ member.roleCount || 0 }} 個角色
              </div>
            </button>

            <div v-if="filteredMembers.length === 0" class="p-4 text-center text-sm text-gray-500">
              沒有找到符合的成員
            </div>
          </div>
        </div>
      </div>

      <!-- Permission Editor -->
      <div class="lg:col-span-2">
        <div v-if="!selectedMember" class="bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
          <svg
            class="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
            />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">未選擇成員</h3>
          <p class="mt-1 text-sm text-gray-500">請從左側列表選擇一個團隊成員以管理其權限</p>
        </div>

        <MemberPermissionEditor
          v-else
          :key="selectedMember.id"
          :user-id="selectedMember.id"
          :member-name="selectedMember.full_name || selectedMember.username"
          @assigned="handleRoleChange"
          @revoked="handleRoleChange"
          @extended="handleRoleChange"
        />
      </div>
    </div>

    <!-- Statistics -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm font-medium text-gray-500">總成員數</div>
        <div class="mt-2 text-2xl font-bold text-gray-900">{{ teamMembers.length }}</div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm font-medium text-gray-500">已指派角色</div>
        <div class="mt-2 text-2xl font-bold text-blue-600">{{ totalAssignments }}</div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm font-medium text-gray-500">即將過期</div>
        <div class="mt-2 text-2xl font-bold text-yellow-600">{{ expiringRoles.length }}</div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm font-medium text-gray-500">已過期</div>
        <div class="mt-2 text-2xl font-bold text-red-600">{{ expiredCount }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoleAssignments } from '~/composables/useRoleAssignments'
import MemberPermissionEditor from '~/components/teams/MemberPermissionEditor.vue'
import type { RoleAssignment } from '~/types/roleAssignment'

definePageMeta({
  title: '團隊權限管理',
})

interface TeamMember {
  id: number
  username: string
  email: string
  full_name: string
  roleCount?: number
}

const {
  getExpiringRoles,
  getDaysUntilExpiry,
  formatExpiryDate,
  isExpired,
} = useRoleAssignments()

const teamMembers = ref<TeamMember[]>([])
const loadingMembers = ref(true)
const selectedMember = ref<TeamMember | null>(null)
const searchQuery = ref('')
const expiringRoles = ref<RoleAssignment[]>([])
const showExpiringRoles = ref(false)
const totalAssignments = ref(0)
const expiredCount = ref(0)

/**
 * Filtered members based on search query
 */
const filteredMembers = computed(() => {
  if (!searchQuery.value) return teamMembers.value

  const query = searchQuery.value.toLowerCase()
  return teamMembers.value.filter(member => {
    return (
      member.username.toLowerCase().includes(query) ||
      member.email.toLowerCase().includes(query) ||
      (member.full_name && member.full_name.toLowerCase().includes(query))
    )
  })
})

/**
 * Load team members
 * TODO: Replace with actual API call to get team members
 */
const loadTeamMembers = async () => {
  loadingMembers.value = true

  try {
    // Mock data - replace with actual API call
    // const response = await fetch('/api/v1/users?team_id=1')
    // const data = await response.json()
    // teamMembers.value = data.data

    // For now, use mock data
    teamMembers.value = [
      {
        id: 1,
        username: 'john_doe',
        email: 'john@example.com',
        full_name: '約翰',
        roleCount: 2,
      },
      {
        id: 2,
        username: 'jane_smith',
        email: 'jane@example.com',
        full_name: '珍妮',
        roleCount: 1,
      },
      {
        id: 3,
        username: 'bob_wilson',
        email: 'bob@example.com',
        full_name: '鮑伯',
        roleCount: 3,
      },
    ]
  } catch (err) {
    console.error('Failed to load team members:', err)
  } finally {
    loadingMembers.value = false
  }
}

/**
 * Load expiring roles
 */
const loadExpiringRoles = async () => {
  try {
    expiringRoles.value = await getExpiringRoles(7)
  } catch (err) {
    console.error('Failed to load expiring roles:', err)
  }
}

/**
 * Load statistics
 * TODO: Replace with actual API call
 */
const loadStatistics = async () => {
  try {
    // Mock data - replace with actual API call
    totalAssignments.value = 15
    expiredCount.value = 2
  } catch (err) {
    console.error('Failed to load statistics:', err)
  }
}

/**
 * Select a member
 */
const selectMember = (member: TeamMember) => {
  selectedMember.value = member
}

/**
 * Handle role change (assigned/revoked/extended)
 */
const handleRoleChange = async () => {
  // Reload expiring roles and statistics
  await Promise.all([
    loadExpiringRoles(),
    loadStatistics(),
  ])

  // Update role count for selected member if needed
  // In a real app, you might want to reload the member's role count
}

// Load data on mount
onMounted(async () => {
  await Promise.all([
    loadTeamMembers(),
    loadExpiringRoles(),
    loadStatistics(),
  ])

  // Auto-select first member if available
  if (teamMembers.value.length > 0) {
    selectedMember.value = teamMembers.value[0]
  }
})
</script>

<style scoped>
.manage-team-page {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8;
}
</style>
