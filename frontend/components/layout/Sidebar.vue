<template>
  <aside
    :class="[
      'sidebar fixed left-0 top-0 h-screen transition-all duration-300 z-30',
      isCollapsed ? 'w-16' : 'w-64',
      'bg-white border-r border-gray-200'
    ]"
  >
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
      <div v-if="!isCollapsed" class="flex items-center">
        <span class="text-xl font-semibold text-gray-900">{{ appName }}</span>
      </div>
      <button
        @click="toggleCollapse"
        class="p-2 rounded-md hover:bg-gray-200 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        :aria-label="isCollapsed ? '展開側邊欄' : '收合側邊欄'"
      >
        <svg
          v-if="!isCollapsed"
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
        </svg>
        <svg
          v-else
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
        </svg>
      </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-4" role="navigation" aria-label="主選單">
      <ul class="space-y-1 px-2">
        <li v-for="item in visibleMenuItems" :key="item.id">
          <NuxtLink
            :to="item.path"
            :class="[
              'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors',
              'text-gray-700 hover:bg-gray-100',
              'focus:outline-none focus:ring-2 focus:ring-indigo-500',
              isActivePath(item.path) && 'bg-indigo-50 text-indigo-600'
            ]"
            :aria-label="item.label"
          >
            <!-- Icon -->
            <component
              :is="item.icon"
              :class="[
                'flex-shrink-0',
                isCollapsed ? 'w-5 h-5' : 'w-4 h-4 mr-3'
              ]"
            />
            <!-- Label (hidden when collapsed) -->
            <span v-if="!isCollapsed" class="truncate">{{ item.label }}</span>
          </NuxtLink>
        </li>
      </ul>
    </nav>
  </aside>

  <!-- Overlay for mobile -->
  <div
    v-if="!isCollapsed && isMobile"
    @click="toggleCollapse"
    class="fixed inset-0 bg-black bg-opacity-50 z-20"
    aria-hidden="true"
  />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, h } from 'vue'
import { useRoute } from 'vue-router'
import { useRuntimeConfig } from '#app'

interface MenuItem {
  id: string
  label: string
  path: string
  icon: any
  permission?: string
}

const route = useRoute()
const config = useRuntimeConfig()
const appName = computed(() => config.public.appName || 'CRM')

const isCollapsed = ref(false)
const isMobile = ref(false)

// Define menu items (filtered by permissions)
const menuItems = ref<MenuItem[]>([
  {
    id: 'dashboard',
    label: '儀表板',
    path: '/dashboard',
    icon: {
      render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })
      ])
    },
  },
  {
    id: 'customers',
    label: '客戶管理',
    path: '/customers',
    icon: {
      render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' })
      ])
    },
    permission: 'customer:view',
  },
  {
    id: 'orders',
    label: '訂單管理',
    path: '/orders',
    icon: {
      render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' })
      ])
    },
    permission: 'order:view',
  },
  {
    id: 'reports',
    label: '報表中心',
    path: '/reports',
    icon: {
      render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' })
      ])
    },
    permission: 'report:view',
  },
  {
    id: 'roles',
    label: '角色管理',
    path: '/roles',
    icon: {
      render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' })
      ])
    },
    permission: 'role:view',
  },
  {
    id: 'audit-logs',
    label: '審計記錄',
    path: '/audit-logs',
    icon: {
      render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })
      ])
    },
    permission: 'audit_log:view',
  },
])

/**
 * Filter menu items based on user permissions
 * TODO: Implement actual permission checking via usePermissions composable
 */
const visibleMenuItems = computed(() => {
  // For now, show all items
  // In production, filter based on user permissions:
  // return menuItems.value.filter(item => !item.permission || can(item.permission))
  return menuItems.value
})

/**
 * Check if the given path is active
 */
const isActivePath = (path: string): boolean => {
  return route.path === path || route.path.startsWith(path + '/')
}

/**
 * Toggle sidebar collapse state
 */
const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}

/**
 * Check if screen is mobile size
 */
const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
  if (isMobile.value) {
    isCollapsed.value = true
  }
}

// Mobile detection
onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

// Expose state for parent components
defineExpose({
  isCollapsed
})
</script>

<style scoped>
.sidebar {
  /* Ensure sidebar is above content but below modals */
  z-index: 30;
}

/* Smooth transitions */
.sidebar * {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
</style>
