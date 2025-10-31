<template>
  <div class="create-role-page">
    <!-- Page header -->
    <div class="mb-6">
      <NuxtLink to="/roles" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        返回角色列表
      </NuxtLink>
      <h1 class="text-2xl font-bold text-gray-900">建立角色</h1>
      <p class="mt-1 text-sm text-gray-500">建立新的自訂角色並設定權限</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
      <div class="px-4 py-3 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">基本資訊</h2>
      </div>
      <div class="p-4">
        <RoleForm
          :loading="loading"
          @submit="handleCreateRole"
          @cancel="handleCancel"
        />
      </div>
    </div>

    <!-- Permissions Section -->
    <div class="mt-4 bg-white rounded-lg shadow">
      <div class="px-4 py-3 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">權限設定</h2>
      </div>
      <div class="p-4">
        <PermissionSelector v-model="selectedPermissions" />
      </div>
    </div>

    <!-- Conditions Section -->
    <div class="mt-4 bg-white rounded-lg shadow">
      <div class="px-4 py-3 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">條件限制</h2>
      </div>
      <div class="p-4">
        <ConditionBuilder v-model="conditionRules" />
      </div>
    </div>

    <!-- Success notification -->
    <div
      v-if="showSuccess"
      class="fixed bottom-4 right-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 shadow-lg"
    >
      <div class="flex items-center">
        <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <p class="text-sm font-medium text-green-800">角色建立成功！</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useRoles } from '~/composables/useRoles'
import type { Role, ConditionRule } from '~/stores/roles'
import RoleForm from '~/components/roles/RoleForm.vue'
import PermissionSelector from '~/components/roles/PermissionSelector.vue'
import ConditionBuilder from '~/components/roles/ConditionBuilder.vue'

definePageMeta({
  title: '建立角色',
})

const router = useRouter()
const { createRole, loading } = useRoles()

const selectedPermissions = ref<number[]>([])
const conditionRules = ref<ConditionRule[]>([])
const showSuccess = ref(false)

/**
 * Handle role creation
 */
const handleCreateRole = async (roleData: Partial<Role>) => {
  try {
    const data = {
      ...roleData,
      permissions: selectedPermissions.value,
      condition_rules: conditionRules.value,
    }

    await createRole(data)

    // Show success message
    showSuccess.value = true

    // Redirect after a short delay
    setTimeout(() => {
      router.push('/roles')
    }, 1500)
  } catch (err) {
    console.error('Failed to create role:', err)
    alert('建立角色失敗: ' + (err as Error).message)
  }
}

/**
 * Handle cancel
 */
const handleCancel = () => {
  router.push('/roles')
}
</script>

<style scoped>
.create-role-page {
  @apply max-w-6xl mx-auto py-6;
}
</style>
