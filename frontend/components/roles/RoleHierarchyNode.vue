<template>
  <div class="role-hierarchy-node">
    <div
      class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors"
      :class="{
        'bg-blue-50': level === 0,
        'ml-8': level > 0,
      }"
    >
      <!-- Expand/Collapse Button -->
      <button
        v-if="hasChildren"
        type="button"
        @click="expanded = !expanded"
        class="flex-shrink-0 w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600"
      >
        <svg
          v-if="expanded"
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
        <svg
          v-else
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
      <div v-else class="w-6"></div>

      <!-- Role Icon -->
      <div
        class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center"
        :class="{
          'bg-blue-500': level === 0,
          'bg-green-500': level === 1,
          'bg-yellow-500': level === 2,
          'bg-purple-500': level >= 3,
        }"
      >
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </div>

      <!-- Role Info -->
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <h4 class="text-sm font-medium text-gray-900">{{ role.display_name }}</h4>
          <span
            v-if="role.is_system"
            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
          >
            系統
          </span>
          <span
            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
          >
            層級 {{ level }}
          </span>
        </div>
        <p v-if="role.description" class="text-xs text-gray-500 mt-1">{{ role.description }}</p>
        <div v-if="hasChildren" class="text-xs text-gray-400 mt-1">
          {{ role.children!.length }} 個子角色
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <NuxtLink
          :to="`/roles/${role.id}/edit`"
          class="text-sm text-blue-600 hover:text-blue-800"
        >
          編輯
        </NuxtLink>
        <button
          type="button"
          @click="$emit('set-parent', role)"
          class="text-sm text-gray-600 hover:text-gray-800"
        >
          設定父角色
        </button>
      </div>
    </div>

    <!-- Children -->
    <div v-if="hasChildren && expanded" class="mt-1 space-y-1">
      <RoleHierarchyNode
        v-for="child in role.children"
        :key="child.id"
        :role="child"
        :level="level + 1"
        @edit="$emit('edit', $event)"
        @set-parent="$emit('set-parent', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { RoleWithChildren } from '~/composables/useRoleHierarchy'

interface Props {
  role: RoleWithChildren
  level: number
}

const props = defineProps<Props>()

defineEmits<{
  (e: 'edit', role: RoleWithChildren): void
  (e: 'set-parent', role: RoleWithChildren): void
}>()

const expanded = ref(true)

const hasChildren = computed(() => {
  return props.role.children && props.role.children.length > 0
})
</script>

<style scoped>
.role-hierarchy-node {
  @apply relative;
}
</style>
