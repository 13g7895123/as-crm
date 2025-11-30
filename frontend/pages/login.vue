<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-8">
    <div class="w-full max-w-md">
      <!-- 登入卡片 -->
      <div class="bg-white rounded-lg shadow-md border border-gray-200 px-8 py-10">
        <!-- Logo / 標題 -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-semibold text-gray-900 mb-2">CRM 管理系統</h1>
          <p class="text-gray-600">登入您的帳號</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- 使用者名稱 -->
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
              使用者名稱
            </label>
            <input
              id="username"
              v-model="credentials.username"
              type="text"
              name="username"
              autocomplete="username"
              required
              :disabled="loading"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow disabled:bg-gray-50 disabled:text-gray-500"
              placeholder="請輸入使用者名稱"
            >
          </div>

          <!-- 密碼 -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              密碼
            </label>
            <input
              id="password"
              v-model="credentials.password"
              type="password"
              name="password"
              autocomplete="current-password"
              required
              :disabled="loading"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow disabled:bg-gray-50 disabled:text-gray-500"
              placeholder="請輸入密碼"
              @keyup.enter="handleLogin"
            >
          </div>

          <!-- 錯誤訊息 -->
          <div
            v-if="errorMessage"
            class="p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-700"
          >
            {{ errorMessage }}
          </div>

          <!-- 登入按鈕 -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
          >
            <span v-if="!loading">登入</span>
            <span v-else class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              登入中...
            </span>
          </button>
        </form>

        <!-- 測試帳號提示 -->
        <div class="mt-6 pt-6 border-t border-gray-200 text-center">
          <p class="text-sm text-gray-600">
            測試帳號：<span class="font-medium text-gray-900">admin</span> /
            <span class="font-medium text-gray-900">admin123</span>
          </p>
        </div>
      </div>

      <!-- Footer -->
      <p class="mt-8 text-center text-sm text-gray-500">
        © 2025 CRM 管理系統
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '~/composables/useAuth'

definePageMeta({
  layout: false, // 不使用 default layout
  title: '登入',
})

const router = useRouter()
const route = useRoute()
const { login } = useAuth()

// Form state
const credentials = ref({
  username: '',
  password: '',
})

const loading = ref(false)
const errorMessage = ref('')

/**
 * Handle login submission
 */
const handleLogin = async () => {
  // Reset error message
  errorMessage.value = ''

  // Validate inputs
  if (!credentials.value.username || !credentials.value.password) {
    errorMessage.value = '請輸入使用者名稱和密碼'
    return
  }

  loading.value = true

  try {
    // Call login API
    const success = await login(credentials.value)

    if (success) {
      // Get redirect path from query or default to home
      const redirect = (route.query.redirect as string) || '/'

      // Redirect to the target page
      await router.push(redirect)
    } else {
      errorMessage.value = '登入失敗，請檢查您的帳號密碼'
    }
  } catch (error: any) {
    console.error('Login error:', error)
    errorMessage.value = error.message || '登入失敗，請稍後再試'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* 簡單的樣式，不需要複雜的動畫 */
</style>
