<template>
  <div class="edit-role-page">
    <!-- Page header -->
    <div class="mb-6">
      <NuxtLink to="/roles" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        返回角色列表
      </NuxtLink>
      <h1 class="text-2xl font-bold text-gray-900">編輯角色</h1>
      <p class="mt-1 text-sm text-gray-500">修改角色資訊和權限設定</p>
    </div>

    <!-- Loading state -->
    <div v-if="initialLoading" class="text-center py-12">
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
      <p class="mt-4 text-sm text-gray-500">載入中...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="loadError" class="rounded-md bg-red-50 p-4">
      <h3 class="text-sm font-medium text-red-800">載入角色失敗</h3>
      <p class="mt-2 text-sm text-red-700">{{ loadError }}</p>
      <div class="mt-4">
        <button
          type="button"
          @click="loadRole"
          class="text-sm font-medium text-red-800 hover:text-red-700"
        >
          重試
        </button>
      </div>
    </div>

    <!-- Edit form -->
    <template v-else-if="role">
      <!-- System role warning -->
      <div v-if="role.is_system" class="mb-6 rounded-md bg-yellow-50 p-4">
        <div class="flex">
          <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <div class="ml-3">
            <p class="text-sm text-yellow-700">
              這是系統角色，只能修改描述欄位。
            </p>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">基本資訊</h2>
        </div>
        <div class="p-6">
          <RoleForm
            :role="role"
            :loading="loading"
            @submit="handleUpdateRole"
            @cancel="handleCancel"
          />
        </div>
      </div>

      <!-- Permissions Section (not editable for system roles) -->
      <div v-if="!role.is_system" class="mt-6 bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">權限設定</h2>
        </div>
        <div class="p-6">
          <PermissionSelector v-model="selectedPermissions" />
        </div>
      </div>

      <!-- Conditions Section (not editable for system roles) -->
      <div v-if="!role.is_system" class="mt-6 bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">條件限制</h2>
        </div>
        <div class="p-6">
          <ConditionBuilder v-model="conditionRules" />
        </div>
      </div>
    </template>

    <!-- Success notification -->
    <div
      v-if="showSuccess"
      class="fixed bottom-4 right-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 shadow-lg"
    >
      <div class="flex items-center">
        <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <p class="text-sm font-medium text-green-800">角色更新成功！</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRoles } from '~/composables/useRoles'
import type { Role, ConditionRule } from '~/stores/roles'
import RoleForm from '~/components/roles/RoleForm.vue'
import PermissionSelector from '~/components/roles/PermissionSelector.vue'
import ConditionBuilder from '~/components/roles/ConditionBuilder.vue'

definePageMeta({
  title: '編輯角色',
})

const route = useRoute()
const router = useRouter()
const { getRoleById, updateRole, loading } = useRoles()

const roleId = computed(() => parseInt(route.params.id as string))

const role = ref<Role | null>(null)
const initialLoading = ref(true)
const loadError = ref<string | null>(null)
const selectedPermissions = ref<number[]>([])
const conditionRules = ref<ConditionRule[]>([])
const showSuccess = ref(false)

/**
 * Load role data
 */
const loadRole = async () => {
  initialLoading.value = true
  loadError.value = null

  try {
    const roleData = await getRoleById(roleId.value)
    role.value = roleData

    // Set permissions
    if (roleData.permissions) {
      selectedPermissions.value = roleData.permissions.map(p => p.id)
    }

    // Set condition rules
    if (roleData.condition_rules) {
      conditionRules.value = roleData.condition_rules
    }
  } catch (err) {
    console.error('Failed to load role:', err)
    loadError.value = (err as Error).message
  } finally {
    initialLoading.value = false
  }
}

/**
 * Handle role update
 */
const handleUpdateRole = async (roleData: Partial<Role>) => {
  try {
    const data: Partial<Role> = {
      ...roleData,
    }

    // Only include permissions and conditions for non-system roles
    if (!role.value?.is_system) {
      data.permissions = selectedPermissions.value as any
      data.condition_rules = conditionRules.value as any
    }

    await updateRole(roleId.value, data)

    // Show success message
    showSuccess.value = true

    // Redirect after a short delay
    setTimeout(() => {
      router.push('/roles')
    }, 1500)
  } catch (err) {
    console.error('Failed to update role:', err)
    alert('更新角色失敗: ' + (err as Error).message)
  }
}

/**
 * Handle cancel
 */
const handleCancel = () => {
  router.push('/roles')
}

// Load role on mount
onMounted(() => {
  loadRole()
})
</script>

<style scoped>
.edit-role-page {
  @apply max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8;
}
</style>
