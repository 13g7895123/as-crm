<template>
  <div class="role-form">
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Role Name -->
      <div>
        <label for="role-name" class="block text-sm font-medium text-gray-700">
          角色名稱 <span class="text-red-500">*</span>
        </label>
        <input
          id="role-name"
          v-model="formData.name"
          type="text"
          data-testid="role-name-input"
          :disabled="isEdit"
          :class="[
            'mt-1 block w-full rounded-md border-gray-300 shadow-sm',
            'focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
            isEdit ? 'bg-gray-100 cursor-not-allowed' : '',
            errors.name ? 'border-red-300' : ''
          ]"
          placeholder="例如: regional_sales_manager"
        />
        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
        <p class="mt-1 text-xs text-gray-500">只能包含小寫字母、數字和底線,長度3-100字元</p>
      </div>

      <!-- Display Name -->
      <div>
        <label for="display-name" class="block text-sm font-medium text-gray-700">
          顯示名稱 <span class="text-red-500">*</span>
        </label>
        <input
          id="display-name"
          v-model="formData.display_name"
          type="text"
          data-testid="role-display-name-input"
          :class="[
            'mt-1 block w-full rounded-md border-gray-300 shadow-sm',
            'focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
            errors.display_name ? 'border-red-300' : ''
          ]"
          placeholder="例如: 區域業務主管"
        />
        <p v-if="errors.display_name" class="mt-1 text-sm text-red-600">{{ errors.display_name }}</p>
      </div>

      <!-- Description -->
      <div>
        <label for="description" class="block text-sm font-medium text-gray-700">
          描述
        </label>
        <textarea
          id="description"
          v-model="formData.description"
          data-testid="role-description-input"
          rows="3"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          placeholder="角色的詳細描述..."
        />
      </div>

      <!-- Is Active (for edit mode) -->
      <div v-if="isEdit" class="flex items-center">
        <input
          id="is-active"
          v-model="formData.is_active"
          type="checkbox"
          class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        />
        <label for="is-active" class="ml-2 block text-sm text-gray-900">
          啟用此角色
        </label>
      </div>

      <!-- Form Actions -->
      <div class="flex justify-end space-x-3 pt-4 border-t">
        <button
          type="button"
          @click="handleCancel"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          取消
        </button>
        <button
          type="submit"
          data-testid="submit-role-button"
          :disabled="loading"
          :class="[
            'px-4 py-2 text-sm font-medium text-white rounded-md',
            'focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
            loading ? 'bg-indigo-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700'
          ]"
        >
          <span v-if="loading">處理中...</span>
          <span v-else>{{ isEdit ? '更新' : '建立' }}</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import type { Role } from '~/stores/roles'

interface Props {
  role?: Role | null
  loading?: boolean
}

interface Emits {
  (e: 'submit', data: Partial<Role>): void
  (e: 'cancel'): void
}

const props = withDefaults(defineProps<Props>(), {
  role: null,
  loading: false,
})

const emit = defineEmits<Emits>()

const isEdit = computed(() => !!props.role)

// Form data
const formData = reactive({
  name: '',
  display_name: '',
  description: '',
  is_active: true,
})

// Errors
const errors = reactive({
  name: '',
  display_name: '',
})

// Watch for role changes (edit mode)
watch(
  () => props.role,
  (newRole) => {
    if (newRole) {
      formData.name = newRole.name || ''
      formData.display_name = newRole.display_name || ''
      formData.description = newRole.description || ''
      formData.is_active = !newRole.deleted_at // Check if not soft-deleted
    }
  },
  { immediate: true }
)

/**
 * Validate form
 */
const validateForm = (): boolean => {
  let isValid = true

  // Reset errors
  errors.name = ''
  errors.display_name = ''

  // Validate name
  if (!formData.name.trim()) {
    errors.name = '角色名稱為必填'
    isValid = false
  } else if (!/^[a-z0-9_]{3,100}$/.test(formData.name)) {
    errors.name = '角色名稱只能包含小寫字母、數字和底線,長度3-100字元'
    isValid = false
  }

  // Validate display name
  if (!formData.display_name.trim()) {
    errors.display_name = '顯示名稱為必填'
    isValid = false
  }

  return isValid
}

/**
 * Handle form submission
 */
const handleSubmit = () => {
  if (!validateForm()) {
    return
  }

  const data: Partial<Role> = {
    name: formData.name.trim(),
    display_name: formData.display_name.trim(),
    description: formData.description.trim() || undefined,
  }

  if (isEdit.value) {
    // In edit mode, include is_active status
    data.is_active = formData.is_active
  }

  emit('submit', data)
}

/**
 * Handle cancel
 */
const handleCancel = () => {
  emit('cancel')
}
</script>

<style scoped>
.role-form {
  @apply max-w-2xl;
}
</style>
