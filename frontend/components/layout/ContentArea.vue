<template>
  <main
    :class="[
      'content-area',
      'bg-gray-50 min-h-screen',
      padding && 'p-4 md:p-6 lg:p-8'
    ]"
    role="main"
  >
    <!-- Page Header (optional) -->
    <div v-if="title || $slots.header" class="mb-6">
      <slot name="header">
        <div v-if="title" class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm text-gray-600">{{ subtitle }}</p>
          </div>
          <div v-if="$slots.actions">
            <slot name="actions" />
          </div>
        </div>
      </slot>
    </div>

    <!-- Main Content -->
    <div :class="cardContainer && 'bg-white rounded-lg border border-gray-200 shadow-sm'">
      <slot />
    </div>
  </main>
</template>

<script setup lang="ts">
interface Props {
  /**
   * Page title displayed at the top
   */
  title?: string

  /**
   * Page subtitle/description
   */
  subtitle?: string

  /**
   * Whether to apply padding to the content area
   * @default true
   */
  padding?: boolean

  /**
   * Whether to wrap content in a card container
   * @default false
   */
  cardContainer?: boolean
}

withDefaults(defineProps<Props>(), {
  padding: true,
  cardContainer: false,
})
</script>

<style scoped>
.content-area {
  /* Ensure consistent spacing and typography */
  @apply text-gray-900;
}

/* Card-style content blocks */
.content-area :deep(.card) {
  @apply bg-white rounded-lg border border-gray-200 shadow-sm;
}

/* Consistent spacing system */
.content-area :deep(.section-spacing) {
  @apply space-y-6;
}

/* Primary text color */
.content-area :deep(.text-primary) {
  @apply text-gray-900;
}

/* Secondary text color */
.content-area :deep(.text-secondary) {
  @apply text-gray-600;
}

/* Muted text color */
.content-area :deep(.text-muted) {
  @apply text-gray-500;
}

/* Form spacing */
.content-area :deep(.form-group) {
  @apply space-y-4;
}

/* Table container with card styling */
.content-area :deep(.table-container) {
  @apply bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden;
}

/* Responsive grid layouts */
.content-area :deep(.grid-responsive) {
  @apply grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3;
}

/* Button groups */
.content-area :deep(.button-group) {
  @apply flex items-center space-x-2;
}

/* Alert/notification styles */
.content-area :deep(.alert) {
  @apply rounded-md p-4 border;
}

.content-area :deep(.alert-info) {
  @apply bg-blue-50 border-blue-200 text-blue-800;
}

.content-area :deep(.alert-success) {
  @apply bg-green-50 border-green-200 text-green-800;
}

.content-area :deep(.alert-warning) {
  @apply bg-yellow-50 border-yellow-200 text-yellow-800;
}

.content-area :deep(.alert-error) {
  @apply bg-red-50 border-red-200 text-red-800;
}

/* Loading states */
.content-area :deep(.skeleton) {
  @apply animate-pulse bg-gray-200 rounded;
}

/* Empty states */
.content-area :deep(.empty-state) {
  @apply text-center py-12;
}

.content-area :deep(.empty-state-icon) {
  @apply w-12 h-12 mx-auto text-gray-400;
}

.content-area :deep(.empty-state-title) {
  @apply mt-4 text-lg font-medium text-gray-900;
}

.content-area :deep(.empty-state-description) {
  @apply mt-2 text-sm text-gray-600;
}
</style>
