<template>
  <div class="permission-selector">
    <div class="mb-4">
      <h3 class="text-lg font-medium text-gray-900">權限設定</h3>
      <p class="mt-1 text-sm text-gray-500">選擇此角色應具備的權限</p>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
      <p class="mt-2 text-sm text-gray-500">載入權限中...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="rounded-md bg-red-50 p-4">
      <p class="text-sm text-red-800">{{ error }}</p>
    </div>

    <!-- Permission groups -->
    <div v-else class="space-y-6">
      <div
        v-for="group in permissionGroups"
        :key="group.resource"
        :data-testid="`permission-group-${group.resource}`"
        class="border border-gray-200 rounded-lg p-4"
      >
        <!-- Group header -->
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-sm font-medium text-gray-900">
            {{ group.resource_name }}
          </h4>
          <button
            type="button"
            @click="toggleGroupSelection(group.resource)"
            class="text-xs text-indigo-600 hover:text-indigo-500"
          >
            {{ isGroupFullySelected(group.resource) ? '取消全選' : '全選' }}
          </button>
        </div>

        <!-- Permission checkboxes -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="permission in group.permissions"
            :key="permission.id"
            class="flex items-start"
          >
            <div class="flex h-5 items-center">
              <input
                :id="`permission-${permission.id}`"
                v-model="selectedPermissionIds"
                :value="permission.id"
                type="checkbox"
                :data-testid="`permission-${permission.name}`"
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
              />
            </div>
            <div class="ml-3">
              <label
                :for="`permission-${permission.id}`"
                class="text-sm font-medium text-gray-700 cursor-pointer"
              >
                {{ getActionDisplayName(permission.action) }}
              </label>
              <p v-if="permission.description" class="text-xs text-gray-500">
                {{ permission.description }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Selected count -->
      <div class="mt-4 text-sm text-gray-500">
        已選擇 <span class="font-medium text-gray-900">{{ selectedPermissionIds.length }}</span> 個權限
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { usePermissions } from '~/composables/usePermissions'
import type { PermissionGroup } from '~/stores/permissions'

interface Props {
  modelValue: number[]
}

interface Emits {
  (e: 'update:modelValue', value: number[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { getPermissions, permissions, loading, error } = usePermissions()

const selectedPermissionIds = ref<number[]>([])
const permissionGroups = ref<PermissionGroup[]>([])

// Load permissions on mount
onMounted(async () => {
  try {
    permissionGroups.value = await getPermissions()
  } catch (err) {
    console.error('Failed to load permissions:', err)
  }
})

// Watch for external changes to modelValue
watch(
  () => props.modelValue,
  (newValue) => {
    selectedPermissionIds.value = [...newValue]
  },
  { immediate: true }
)

// Emit changes
watch(selectedPermissionIds, (newValue) => {
  emit('update:modelValue', newValue)
})

/**
 * Check if all permissions in a group are selected
 */
const isGroupFullySelected = (resource: string): boolean => {
  const group = permissionGroups.value.find(g => g.resource === resource)
  if (!group) return false

  return group.permissions.every(p =>
    selectedPermissionIds.value.includes(p.id)
  )
}

/**
 * Toggle selection of all permissions in a group
 */
const toggleGroupSelection = (resource: string) => {
  const group = permissionGroups.value.find(g => g.resource === resource)
  if (!group) return

  const groupPermissionIds = group.permissions.map(p => p.id)

  if (isGroupFullySelected(resource)) {
    // Deselect all in group
    selectedPermissionIds.value = selectedPermissionIds.value.filter(
      id => !groupPermissionIds.includes(id)
    )
  } else {
    // Select all in group
    const newIds = groupPermissionIds.filter(
      id => !selectedPermissionIds.value.includes(id)
    )
    selectedPermissionIds.value = [...selectedPermissionIds.value, ...newIds]
  }
}

/**
 * Get display name for action
 */
const getActionDisplayName = (action: string): string => {
  const names: Record<string, string> = {
    view: '檢視',
    edit: '編輯',
    export: '匯出',
    assign: '指派',
  }
  return names[action] || action
}
</script>

<style scoped>
.permission-selector {
  @apply w-full;
}
</style>
