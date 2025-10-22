<template>
  <div class="app-layout h-screen overflow-hidden">
    <!-- Sidebar -->
    <LayoutSidebar ref="sidebarRef" />

    <!-- Main Content Area -->
    <div
      :class="[
        'main-content transition-all duration-300',
        sidebarCollapsed ? 'ml-16' : 'ml-64'
      ]"
    >
      <!-- Navbar -->
      <LayoutNavbar />

      <!-- Page Content -->
      <div class="content-wrapper mt-16 h-[calc(100vh-4rem)] overflow-y-auto">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'

const sidebarRef = ref<any>(null)
const sidebarCollapsed = ref(false)

/**
 * Watch for sidebar collapse state changes
 * This ensures the main content adjusts its margin accordingly
 */
onMounted(() => {
  if (sidebarRef.value) {
    // Watch the sidebar's isCollapsed state
    watch(
      () => sidebarRef.value?.isCollapsed,
      (collapsed) => {
        sidebarCollapsed.value = collapsed
      },
      { immediate: true }
    )
  }
})
</script>

<style scoped>
.app-layout {
  @apply bg-gray-50;
}

.main-content {
  /* Ensure smooth transition when sidebar collapses/expands */
  transition-property: margin-left;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

.content-wrapper {
  /* Ensure scrollable content area */
  @apply overflow-y-auto;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .main-content {
    /* On mobile, always use collapsed sidebar margin */
    margin-left: 4rem !important;
  }
}

/* Accessibility: Focus styles */
.app-layout :deep(*:focus-visible) {
  @apply outline-none ring-2 ring-indigo-500 ring-offset-2;
}

/* Ensure proper z-index stacking */
.app-layout :deep(.sidebar) {
  z-index: 30;
}

.app-layout :deep(.navbar) {
  z-index: 20;
}

.app-layout :deep(.content-wrapper) {
  z-index: 10;
}
</style>
