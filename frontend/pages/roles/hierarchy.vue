<template>
  <LayoutContentArea title="角色階層管理" subtitle="管理角色的父子關係和權限繼承">
    <template #actions>
      <div class="flex items-center space-x-3">
        <NuxtLink
          to="/roles"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
          </svg>
          返回列表
        </NuxtLink>
        <button
          type="button"
          @click="loadHierarchy"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          重新載入
        </button>
      </div>
    </template>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">載入中...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="rounded-md bg-red-50 p-4">
      <p class="text-sm text-red-800">{{ error }}</p>
    </div>

    <!-- Hierarchy Tree -->
    <div v-else class="bg-white shadow rounded-lg p-4">
      <div v-if="hierarchyTree.length === 0" class="text-center py-8 text-gray-500">
        沒有找到角色階層資料
      </div>

      <div v-else class="space-y-2">
        <RoleHierarchyNode
          v-for="role in hierarchyTree"
          :key="role.id"
          :role="role"
          :level="0"
          @edit="handleEditRole"
          @set-parent="handleSetParent"
        />
      </div>
    </div>

    <!-- Edit Parent Dialog -->
    <div
      v-if="showEditDialog"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closeEditDialog"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">設定父角色</h3>

        <div class="mb-4">
          <p class="text-sm text-gray-600 mb-4">
            角色：<span class="font-medium">{{ editingRole?.display_name }}</span>
          </p>

          <label class="block text-sm font-medium text-gray-700 mb-2">
            選擇父角色
          </label>
          <select
            v-model="selectedParentId"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option :value="null">無 (設為根角色)</option>
            <option
              v-for="role in availableParents"
              :key="role.id"
              :value="role.id"
            >
              {{ role.display_name }}
            </option>
          </select>
        </div>

        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="closeEditDialog"
            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            取消
          </button>
          <button
            type="button"
            @click="handleUpdateParent"
            :disabled="saving"
            class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
          >
            {{ saving ? '儲存中...' : '確認' }}
          </button>
        </div>
      </div>
    </div>
  </LayoutContentArea>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoleHierarchy } from '~/composables/useRoleHierarchy'
import { useRoles } from '~/composables/useRoles'
import type { RoleWithChildren } from '~/composables/useRoleHierarchy'
import type { Role } from '~/stores/roles'
import RoleHierarchyNode from '~/components/roles/RoleHierarchyNode.vue'

definePageMeta({
  title: '角色階層',
})

const {
  hierarchyTree,
  loading,
  error,
  getHierarchyTree,
  updateParent,
} = useRoleHierarchy()

const { getRoles, roles } = useRoles()

const showEditDialog = ref(false)
const editingRole = ref<RoleWithChildren | null>(null)
const selectedParentId = ref<number | null>(null)
const saving = ref(false)

/**
 * Available parent roles (excluding the editing role and its descendants)
 */
const availableParents = computed(() => {
  if (!editingRole.value) return []

  // Get all descendants of editing role
  const descendants = new Set<number>()
  const collectDescendants = (role: RoleWithChildren) => {
    if (role.children) {
      role.children.forEach(child => {
        descendants.add(child.id)
        collectDescendants(child)
      })
    }
  }
  collectDescendants(editingRole.value)

  // Filter out editing role itself and its descendants
  return roles.value.filter(role =>
    role.id !== editingRole.value!.id &&
    !descendants.has(role.id)
  )
})

/**
 * Load hierarchy
 */
const loadHierarchy = async () => {
  try {
    await getHierarchyTree()
    // Also load all roles for parent selection
    await getRoles({ per_page: 100 })
  } catch (err) {
    console.error('Failed to load hierarchy:', err)
  }
}

/**
 * Handle edit role
 */
const handleEditRole = (role: RoleWithChildren) => {
  editingRole.value = role
  selectedParentId.value = null // Will be set by finding current parent
  showEditDialog.value = true
}

/**
 * Handle set parent
 */
const handleSetParent = (role: RoleWithChildren) => {
  handleEditRole(role)
}

/**
 * Handle update parent
 */
const handleUpdateParent = async () => {
  if (!editingRole.value) return

  saving.value = true

  try {
    await updateParent(editingRole.value.id, selectedParentId.value)

    closeEditDialog()
    await loadHierarchy()
  } catch (err) {
    alert('更新失敗: ' + (err as Error).message)
  } finally {
    saving.value = false
  }
}

/**
 * Close edit dialog
 */
const closeEditDialog = () => {
  showEditDialog.value = false
  editingRole.value = null
  selectedParentId.value = null
}

// Load hierarchy on mount
onMounted(() => {
  loadHierarchy()
})
</script>

<style scoped>
/* Removed custom width constraint to match other pages */
</style>
