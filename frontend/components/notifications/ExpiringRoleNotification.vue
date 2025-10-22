<template>
  <div v-if="expiringRoles.length > 0" class="expiring-role-notification">
    <!-- Notification Badge in Navbar (slot for parent) -->
    <slot name="badge" :count="expiringRoles.length">
      <span class="notification-badge">{{ expiringRoles.length }}</span>
    </slot>

    <!-- Notification List (dropdown content) -->
    <div class="notification-list">
      <div class="notification-header">
        <h4 class="text-sm font-semibold text-gray-900">角色即將過期</h4>
        <button
          v-if="expiringRoles.length > 0"
          @click="markAllAsRead"
          class="text-xs text-indigo-600 hover:text-indigo-500"
        >
          全部標記為已讀
        </button>
      </div>

      <div class="notification-items max-h-96 overflow-y-auto">
        <div
          v-for="role in expiringRoles"
          :key="role.id"
          class="notification-item"
          :class="{ 'unread': !role.is_read }"
        >
          <div class="flex items-start space-x-3">
            <!-- Icon -->
            <div class="flex-shrink-0">
              <svg
                class="w-5 h-5"
                :class="getUrgencyColor(role.days_remaining)"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">
                {{ role.role_display_name }}
              </p>
              <p class="mt-1 text-xs text-gray-600">
                {{ getExpiryMessage(role.days_remaining) }}
              </p>
              <p class="mt-1 text-xs text-gray-500">
                過期時間: {{ formatDateTime(role.expires_at) }}
              </p>
            </div>

            <!-- Actions -->
            <div class="flex-shrink-0 flex items-center space-x-2">
              <button
                @click="extendRole(role)"
                class="text-xs text-indigo-600 hover:text-indigo-500"
                title="延長期限"
              >
                延長
              </button>
              <button
                @click="dismissNotification(role.id)"
                class="text-xs text-gray-400 hover:text-gray-600"
                title="關閉通知"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- View All Link -->
      <div v-if="expiringRoles.length > 0" class="notification-footer">
        <NuxtLink
          to="/permissions/my-permissions"
          class="text-sm text-indigo-600 hover:text-indigo-500"
        >
          檢視我的所有權限 →
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoleAssignments } from '~/composables/useRoleAssignments'
import { useAuth } from '~/composables/useAuth'

interface ExpiringRole {
  id: number
  role_id: number
  role_name: string
  role_display_name: string
  expires_at: string
  days_remaining: number
  is_read: boolean
}

const { getUserRoles, extendRoleExpiry } = useRoleAssignments()
const { user } = useAuth()

const expiringRoles = ref<ExpiringRole[]>([])
const dismissedRoles = ref<number[]>([])

/**
 * Load expiring roles for current user
 */
const loadExpiringRoles = async () => {
  if (!user.value?.id) return

  try {
    const roles = await getUserRoles(user.value.id)

    // Filter roles expiring within 7 days
    const now = new Date().getTime()
    const sevenDaysLater = now + (7 * 24 * 60 * 60 * 1000)

    expiringRoles.value = roles
      .filter((role: any) => {
        if (!role.expires_at) return false
        const expiryTime = new Date(role.expires_at).getTime()
        return expiryTime > now && expiryTime <= sevenDaysLater
      })
      .map((role: any) => {
        const expiryTime = new Date(role.expires_at).getTime()
        const daysRemaining = Math.ceil((expiryTime - now) / (24 * 60 * 60 * 1000))

        return {
          id: role.id,
          role_id: role.role_id,
          role_name: role.role_name || role.name,
          role_display_name: role.role_display_name || role.display_name,
          expires_at: role.expires_at,
          days_remaining: daysRemaining,
          is_read: dismissedRoles.value.includes(role.id),
        }
      })
      .sort((a, b) => a.days_remaining - b.days_remaining)
  } catch (error) {
    console.error('Failed to load expiring roles:', error)
  }
}

/**
 * Get urgency color based on days remaining
 */
const getUrgencyColor = (daysRemaining: number): string => {
  if (daysRemaining <= 1) return 'text-red-600'
  if (daysRemaining <= 3) return 'text-orange-600'
  return 'text-yellow-600'
}

/**
 * Get expiry message based on days remaining
 */
const getExpiryMessage = (daysRemaining: number): string => {
  if (daysRemaining === 0) return '今天過期'
  if (daysRemaining === 1) return '明天過期'
  if (daysRemaining <= 3) return `${daysRemaining} 天後過期`
  return `${daysRemaining} 天後過期`
}

/**
 * Format date time
 */
const formatDateTime = (dateString: string): string => {
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
 * Extend role expiry period
 */
const extendRole = async (role: ExpiringRole) => {
  try {
    // Calculate new expiry date (extend by 30 days)
    const currentExpiry = new Date(role.expires_at)
    const newExpiry = new Date(currentExpiry.getTime() + (30 * 24 * 60 * 60 * 1000))

    await extendRoleExpiry(role.id, newExpiry.toISOString())

    // Reload roles
    await loadExpiringRoles()

    // Show success message (you can integrate with a toast notification library)
    console.log(`角色「${role.role_display_name}」已延長 30 天`)
  } catch (error) {
    console.error('Failed to extend role:', error)
  }
}

/**
 * Dismiss notification for a specific role
 */
const dismissNotification = (roleId: number) => {
  dismissedRoles.value.push(roleId)

  // Update is_read status
  const role = expiringRoles.value.find(r => r.id === roleId)
  if (role) {
    role.is_read = true
  }

  // Save to localStorage
  localStorage.setItem('dismissed_role_notifications', JSON.stringify(dismissedRoles.value))
}

/**
 * Mark all notifications as read
 */
const markAllAsRead = () => {
  expiringRoles.value.forEach(role => {
    role.is_read = true
    if (!dismissedRoles.value.includes(role.id)) {
      dismissedRoles.value.push(role.id)
    }
  })

  // Save to localStorage
  localStorage.setItem('dismissed_role_notifications', JSON.stringify(dismissedRoles.value))
}

// Load on mount
onMounted(() => {
  // Load dismissed roles from localStorage
  const dismissed = localStorage.getItem('dismissed_role_notifications')
  if (dismissed) {
    try {
      dismissedRoles.value = JSON.parse(dismissed)
    } catch (error) {
      console.error('Failed to parse dismissed notifications:', error)
    }
  }

  // Load expiring roles
  loadExpiringRoles()

  // Refresh every 5 minutes
  setInterval(loadExpiringRoles, 5 * 60 * 1000)
})
</script>

<style scoped>
.expiring-role-notification {
  @apply relative;
}

.notification-badge {
  @apply absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full;
}

.notification-list {
  @apply bg-white rounded-lg border border-gray-200 shadow-lg overflow-hidden;
}

.notification-header {
  @apply flex items-center justify-between px-4 py-3 border-b border-gray-200;
}

.notification-items {
  @apply divide-y divide-gray-100;
}

.notification-item {
  @apply px-4 py-3 hover:bg-gray-50 transition-colors;
}

.notification-item.unread {
  @apply bg-blue-50;
}

.notification-footer {
  @apply px-4 py-3 border-t border-gray-200 text-center;
}
</style>
