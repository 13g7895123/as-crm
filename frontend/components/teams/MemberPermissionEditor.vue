<template>
  <div class="member-permission-editor">
    <!-- Member Info Header -->
    <div class="mb-6 border-b border-gray-200 pb-4">
      <h3 class="text-lg font-medium text-gray-900">{{ memberName }}</h3>
      <p class="mt-1 text-sm text-gray-500">管理團隊成員的角色和權限</p>
    </div>

    <!-- Loading State -->
    <div v-if="initialLoading" class="text-center py-8">
      <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
      <p class="mt-2 text-sm text-gray-500">載入中...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="loadError" class="rounded-md bg-red-50 p-4 mb-4">
      <p class="text-sm text-red-800">{{ loadError }}</p>
    </div>

    <!-- Current Roles -->
    <template v-else>
      <div class="mb-6">
        <h4 class="text-md font-medium text-gray-900 mb-3">目前角色</h4>

        <div v-if="currentRoles.length === 0" class="text-sm text-gray-500 italic">
          尚未指派任何角色
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="assignment in currentRoles"
            :key="assignment.id"
            class="flex items-center justify-between p-3 border rounded-lg"
            :class="{
              'border-red-200 bg-red-50': isExpired(assignment),
              'border-yellow-200 bg-yellow-50': !isExpired(assignment) && isExpiringSoon(assignment),
              'border-gray-200 bg-white': !isExpired(assignment) && !isExpiringSoon(assignment),
            }"
          >
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <span class="font-medium text-gray-900">
                  {{ assignment.display_name || assignment.role_display_name }}
                </span>

                <!-- Status Badge -->
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                  :class="{
                    'bg-red-100 text-red-800': getExpiryStatus(assignment).color === 'red',
                    'bg-yellow-100 text-yellow-800': getExpiryStatus(assignment).color === 'yellow',
                    'bg-green-100 text-green-800': getExpiryStatus(assignment).color === 'green',
                  }"
                >
                  {{ getExpiryStatus(assignment).label }}
                </span>
              </div>

              <div class="mt-1 text-sm text-gray-500">
                <span>指派時間: {{ formatDateTime(assignment.assigned_at) }}</span>
                <span class="mx-2">•</span>
                <span>有效期限: {{ formatExpiryDate(assignment) }}</span>
                <template v-if="getDaysUntilExpiry(assignment) !== null">
                  <span class="mx-2">•</span>
                  <span
                    :class="{
                      'text-red-600 font-medium': getDaysUntilExpiry(assignment)! <= 3,
                      'text-yellow-600': getDaysUntilExpiry(assignment)! > 3 && getDaysUntilExpiry(assignment)! <= 7,
                    }"
                  >
                    剩餘 {{ getDaysUntilExpiry(assignment) }} 天
                  </span>
                </template>
              </div>

              <!-- Scope Constraints -->
              <div v-if="assignment.scope_constraints" class="mt-2 text-xs text-gray-600">
                <span class="font-medium">範圍限制：</span>
                <span>{{ formatScopeConstraints(assignment.scope_constraints) }}</span>
              </div>
            </div>

            <div class="flex items-center gap-2 ml-4">
              <!-- Extend Button -->
              <button
                v-if="assignment.expires_at"
                type="button"
                @click="openExtendDialog(assignment)"
                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                title="延長有效期限"
              >
                延長
              </button>

              <!-- Revoke Button -->
              <button
                type="button"
                @click="confirmRevoke(assignment)"
                class="text-red-600 hover:text-red-800 text-sm font-medium"
                :disabled="loading"
              >
                撤銷
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Assign New Role -->
      <div class="border-t border-gray-200 pt-6">
        <h4 class="text-md font-medium text-gray-900 mb-3">指派新角色</h4>

        <div class="space-y-4">
          <!-- Role Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              選擇角色 <span class="text-red-500">*</span>
            </label>
            <select
              v-model="newAssignment.role_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option :value="null">請選擇角色</option>
              <option
                v-for="role in availableRoles"
                :key="role.id"
                :value="role.id"
              >
                {{ role.display_name }}
              </option>
            </select>
          </div>

          <!-- Expiry Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              過期時間 (選填)
            </label>
            <input
              v-model="newAssignment.expires_at"
              type="datetime-local"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p class="mt-1 text-xs text-gray-500">留空表示永久有效</p>
          </div>

          <!-- Scope Constraints -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              範圍限制 (選填)
            </label>
            <div class="space-y-2">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-gray-600 mb-1">部門</label>
                  <input
                    v-model="scopeConstraints.department"
                    type="text"
                    placeholder="例如：業務部"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-600 mb-1">區域</label>
                  <input
                    v-model="scopeConstraints.region"
                    type="text"
                    placeholder="例如：華東"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Assign Button -->
          <div class="flex justify-end">
            <button
              type="button"
              @click="handleAssignRole"
              :disabled="!newAssignment.role_id || loading"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ loading ? '指派中...' : '指派角色' }}
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- Extend Dialog -->
    <div
      v-if="showExtendDialog"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closeExtendDialog"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">延長角色有效期限</h3>

        <div class="mb-4">
          <p class="text-sm text-gray-600 mb-2">
            角色：<span class="font-medium">{{ extendingAssignment?.display_name }}</span>
          </p>
          <p class="text-sm text-gray-600 mb-4">
            目前過期時間：<span class="font-medium">{{ formatExpiryDate(extendingAssignment!) }}</span>
          </p>

          <label class="block text-sm font-medium text-gray-700 mb-1">
            新的過期時間 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="newExpiryDate"
            type="datetime-local"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="closeExtendDialog"
            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            取消
          </button>
          <button
            type="button"
            @click="handleExtendValidity"
            :disabled="!newExpiryDate || loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            {{ loading ? '更新中...' : '確認延長' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Revoke Confirmation Dialog -->
    <div
      v-if="showRevokeDialog"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closeRevokeDialog"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">確認撤銷角色</h3>

        <p class="text-sm text-gray-600 mb-4">
          確定要撤銷
          <span class="font-medium">{{ memberName }}</span>
          的
          <span class="font-medium">{{ revokingAssignment?.display_name }}</span>
          角色嗎？
        </p>

        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="closeRevokeDialog"
            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            取消
          </button>
          <button
            type="button"
            @click="handleRevokeRole"
            :disabled="loading"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50"
          >
            {{ loading ? '撤銷中...' : '確認撤銷' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoleAssignments } from '~/composables/useRoleAssignments'
import { useRoles } from '~/composables/useRoles'
import type { RoleAssignment } from '~/types/roleAssignment'
import type { Role } from '~/stores/roles'

interface Props {
  userId: number
  memberName: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'assigned'): void
  (e: 'revoked'): void
  (e: 'extended'): void
}>()

const {
  getUserRoles,
  assignRole,
  revokeAssignment,
  extendRoleValidity,
  isExpired,
  isExpiringSoon,
  getDaysUntilExpiry,
  formatExpiryDate,
  getExpiryStatus,
  loading,
} = useRoleAssignments()

const { getRoles, roles } = useRoles()

const currentRoles = ref<RoleAssignment[]>([])
const initialLoading = ref(true)
const loadError = ref<string | null>(null)

const newAssignment = ref<{
  role_id: number | null
  expires_at: string
}>({
  role_id: null,
  expires_at: '',
})

const scopeConstraints = ref<Record<string, string>>({
  department: '',
  region: '',
})

const showExtendDialog = ref(false)
const extendingAssignment = ref<RoleAssignment | null>(null)
const newExpiryDate = ref('')

const showRevokeDialog = ref(false)
const revokingAssignment = ref<RoleAssignment | null>(null)

/**
 * Available roles (excluding already assigned)
 */
const availableRoles = computed((): Role[] => {
  const assignedRoleIds = currentRoles.value
    .filter(a => !isExpired(a))
    .map(a => a.role_id)
  return roles.value.filter(r => !assignedRoleIds.includes(r.id))
})

/**
 * Load user's current roles
 */
const loadUserRoles = async () => {
  initialLoading.value = true
  loadError.value = null

  try {
    currentRoles.value = await getUserRoles(props.userId, false)
  } catch (err) {
    loadError.value = (err as Error).message
  } finally {
    initialLoading.value = false
  }
}

/**
 * Load available roles
 */
const loadAvailableRoles = async () => {
  try {
    await getRoles({ per_page: 100 })
  } catch (err) {
    console.error('Failed to load roles:', err)
  }
}

/**
 * Handle assigning a role
 */
const handleAssignRole = async () => {
  if (!newAssignment.value.role_id) return

  try {
    const constraints: Record<string, any> = {}
    if (scopeConstraints.value.department) {
      constraints.department = scopeConstraints.value.department
    }
    if (scopeConstraints.value.region) {
      constraints.region = scopeConstraints.value.region
    }

    await assignRole({
      user_id: props.userId,
      role_id: newAssignment.value.role_id,
      expires_at: newAssignment.value.expires_at || null,
      scope_constraints: Object.keys(constraints).length > 0 ? constraints : undefined,
    })

    // Reset form
    newAssignment.value.role_id = null
    newAssignment.value.expires_at = ''
    scopeConstraints.value.department = ''
    scopeConstraints.value.region = ''

    // Reload roles
    await loadUserRoles()

    emit('assigned')
  } catch (err) {
    alert('指派失敗: ' + (err as Error).message)
  }
}

/**
 * Open extend dialog
 */
const openExtendDialog = (assignment: RoleAssignment) => {
  extendingAssignment.value = assignment
  newExpiryDate.value = assignment.expires_at
    ? new Date(assignment.expires_at).toISOString().slice(0, 16)
    : ''
  showExtendDialog.value = true
}

/**
 * Close extend dialog
 */
const closeExtendDialog = () => {
  showExtendDialog.value = false
  extendingAssignment.value = null
  newExpiryDate.value = ''
}

/**
 * Handle extending validity
 */
const handleExtendValidity = async () => {
  if (!extendingAssignment.value || !newExpiryDate.value) return

  try {
    await extendRoleValidity(
      extendingAssignment.value.id,
      newExpiryDate.value.replace('T', ' ') + ':00'
    )

    closeExtendDialog()
    await loadUserRoles()

    emit('extended')
  } catch (err) {
    alert('延長失敗: ' + (err as Error).message)
  }
}

/**
 * Confirm revoke dialog
 */
const confirmRevoke = (assignment: RoleAssignment) => {
  revokingAssignment.value = assignment
  showRevokeDialog.value = true
}

/**
 * Close revoke dialog
 */
const closeRevokeDialog = () => {
  showRevokeDialog.value = false
  revokingAssignment.value = null
}

/**
 * Handle revoking a role
 */
const handleRevokeRole = async () => {
  if (!revokingAssignment.value) return

  try {
    await revokeAssignment(revokingAssignment.value.id)

    closeRevokeDialog()
    await loadUserRoles()

    emit('revoked')
  } catch (err) {
    alert('撤銷失敗: ' + (err as Error).message)
  }
}

/**
 * Format datetime for display
 */
const formatDateTime = (datetime: string): string => {
  const date = new Date(datetime)
  const options: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }
  return date.toLocaleDateString('zh-TW', options)
}

/**
 * Format scope constraints for display
 */
const formatScopeConstraints = (constraints: Record<string, any>): string => {
  const parts: string[] = []
  if (constraints.department) parts.push(`部門: ${constraints.department}`)
  if (constraints.region) parts.push(`區域: ${constraints.region}`)
  return parts.join(', ') || '無'
}

// Load data on mount
onMounted(async () => {
  await Promise.all([loadUserRoles(), loadAvailableRoles()])
})
</script>

<style scoped>
.member-permission-editor {
  @apply bg-white rounded-lg shadow p-6;
}
</style>
