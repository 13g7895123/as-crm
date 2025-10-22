<template>
  <header class="navbar fixed top-0 right-0 h-16 bg-white border-b border-gray-200 z-20" :style="navbarStyle">
    <div class="flex items-center justify-between h-full px-4">
      <!-- Left Section: Logo & Breadcrumb -->
      <div class="flex items-center space-x-4">
        <!-- Logo (visible on mobile when sidebar is collapsed) -->
        <div class="flex items-center md:hidden">
          <span class="text-lg font-semibold text-gray-900">{{ appName }}</span>
        </div>

        <!-- Breadcrumb -->
        <nav aria-label="麵包屑" class="hidden md:block">
          <ol class="flex items-center space-x-2 text-sm">
            <li v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center">
              <NuxtLink
                v-if="crumb.path && index < breadcrumbs.length - 1"
                :to="crumb.path"
                class="text-gray-500 hover:text-gray-900 transition-colors"
              >
                {{ crumb.label }}
              </NuxtLink>
              <span v-else class="text-gray-900 font-medium">{{ crumb.label }}</span>
              <svg
                v-if="index < breadcrumbs.length - 1"
                class="w-4 h-4 mx-2 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
          </ol>
        </nav>
      </div>

      <!-- Right Section: Search, Notifications, User Menu -->
      <div class="flex items-center space-x-3">
        <!-- Search (hidden on small screens) -->
        <div class="hidden lg:block">
          <div class="relative">
            <input
              type="search"
              placeholder="快速搜尋..."
              class="w-64 px-3 py-2 pl-10 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              aria-label="快速搜尋"
            />
            <svg
              class="absolute left-3 top-2.5 w-4 h-4 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <!-- Notifications -->
        <button
          @click="toggleNotifications"
          class="relative p-2 text-gray-700 hover:bg-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
          aria-label="通知中心"
          :aria-expanded="showNotifications"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <!-- Notification Badge -->
          <span
            v-if="unreadNotificationCount > 0"
            class="absolute top-1 right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full"
          >
            {{ unreadNotificationCount > 9 ? '9+' : unreadNotificationCount }}
          </span>
        </button>

        <!-- Notification Dropdown -->
        <div
          v-if="showNotifications"
          class="absolute top-full right-4 mt-2 w-80"
        >
          <NotificationsExpiringRoleNotification />
        </div>

        <!-- User Menu -->
        <div class="relative">
          <button
            @click="toggleUserMenu"
            class="flex items-center space-x-2 p-2 text-gray-700 hover:bg-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            aria-label="使用者選單"
            :aria-expanded="showUserMenu"
          >
            <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
              {{ userInitials }}
            </div>
            <span class="hidden md:block text-sm font-medium">{{ userName }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- User Dropdown -->
          <div
            v-if="showUserMenu"
            class="absolute top-full right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden"
          >
            <div class="px-4 py-3 border-b border-gray-200">
              <p class="text-sm font-medium text-gray-900">{{ userName }}</p>
              <p class="mt-1 text-xs text-gray-500">{{ userEmail }}</p>
            </div>
            <nav class="py-2">
              <NuxtLink
                to="/permissions/my-permissions"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                @click="closeUserMenu"
              >
                我的權限
              </NuxtLink>
              <NuxtLink
                to="/profile"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                @click="closeUserMenu"
              >
                個人設定
              </NuxtLink>
              <button
                @click="handleLogout"
                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
              >
                登出
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRuntimeConfig } from '#app'
import { useAuth } from '~/composables/useAuth'
import { useRoleAssignments } from '~/composables/useRoleAssignments'

interface Breadcrumb {
  label: string
  path?: string
}

interface Notification {
  id: string
  message: string
  time: string
  read: boolean
}

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const { logout, user } = useAuth()
const { getUserRoles } = useRoleAssignments()

const appName = computed(() => config.public.appName || 'CRM')

const showNotifications = ref(false)
const showUserMenu = ref(false)
const expiringRolesCount = ref(0)

// User data from auth store
const userName = computed(() => user.value?.name || user.value?.username || '管理員')
const userEmail = computed(() => user.value?.email || 'admin@example.com')
const userInitials = computed(() => {
  return userName.value.substring(0, 2).toUpperCase()
})

// Unread notification count (from expiring roles)
const unreadNotificationCount = computed(() => {
  return expiringRolesCount.value
})

/**
 * Load expiring roles count
 */
const loadExpiringRolesCount = async () => {
  if (!user.value?.id) return

  try {
    const roles = await getUserRoles(user.value.id)

    // Count roles expiring within 7 days
    const now = new Date().getTime()
    const sevenDaysLater = now + (7 * 24 * 60 * 60 * 1000)

    expiringRolesCount.value = roles.filter((role: any) => {
      if (!role.expires_at) return false
      const expiryTime = new Date(role.expires_at).getTime()
      return expiryTime > now && expiryTime <= sevenDaysLater
    }).length
  } catch (error) {
    console.error('Failed to load expiring roles count:', error)
  }
}

/**
 * Generate breadcrumbs from current route
 */
const breadcrumbs = computed<Breadcrumb[]>(() => {
  const pathSegments = route.path.split('/').filter(Boolean)
  const crumbs: Breadcrumb[] = [{ label: '首頁', path: '/' }]

  let currentPath = ''
  pathSegments.forEach((segment, index) => {
    currentPath += `/${segment}`
    const label = getBreadcrumbLabel(segment, index, pathSegments)
    crumbs.push({
      label,
      path: index < pathSegments.length - 1 ? currentPath : undefined,
    })
  })

  return crumbs.length > 1 ? crumbs : []
})

/**
 * Get breadcrumb label from route segment
 */
const getBreadcrumbLabel = (segment: string, index: number, segments: string[]): string => {
  const labels: Record<string, string> = {
    dashboard: '儀表板',
    customers: '客戶管理',
    orders: '訂單管理',
    reports: '報表中心',
    roles: '角色管理',
    audit: '審計記錄',
    logs: '記錄查詢',
    permissions: '權限',
    'my-permissions': '我的權限',
    create: '新增',
    edit: '編輯',
  }

  return labels[segment] || segment
}

/**
 * Calculate navbar left margin based on sidebar width
 */
const navbarStyle = computed(() => {
  // Adjust based on sidebar width (64px collapsed, 256px expanded)
  // This will be controlled by parent layout
  return {}
})

/**
 * Toggle notifications dropdown
 */
const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value) {
    showUserMenu.value = false
  }
}

/**
 * Toggle user menu dropdown
 */
const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
  if (showUserMenu.value) {
    showNotifications.value = false
  }
}

/**
 * Close user menu
 */
const closeUserMenu = () => {
  showUserMenu.value = false
}

/**
 * Handle user logout
 */
const handleLogout = async () => {
  try {
    await logout()
    router.push('/login')
  } catch (error) {
    console.error('Logout failed:', error)
  }
}

// Load expiring roles count on mount and refresh periodically
onMounted(() => {
  loadExpiringRolesCount()

  // Refresh every 5 minutes
  setInterval(loadExpiringRolesCount, 5 * 60 * 1000)
})

// Close dropdowns when clicking outside
watch([showNotifications, showUserMenu], () => {
  if (showNotifications.value || showUserMenu.value) {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as HTMLElement
      if (!target.closest('.navbar')) {
        showNotifications.value = false
        showUserMenu.value = false
        document.removeEventListener('click', handleClickOutside)
      }
    }
    setTimeout(() => {
      document.addEventListener('click', handleClickOutside)
    }, 0)
  }
})
</script>

<style scoped>
.navbar {
  /* Ensure navbar is above content but below modals */
  z-index: 20;
}
</style>
