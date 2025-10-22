<template>
  <LayoutContentArea
    title="歡迎使用 CRM RBAC 系統"
    subtitle="權限管理與業務功能整合平台"
  >
      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-6 text-white">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm opacity-90">系統模組</p>
              <p class="text-3xl font-bold mt-2">6</p>
            </div>
            <svg class="w-12 h-12 opacity-80" fill="currentColor" viewBox="0 0 20 20">
              <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
            </svg>
          </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-md p-6 text-white">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm opacity-90">角色管理</p>
              <p class="text-3xl font-bold mt-2">RBAC</p>
            </div>
            <svg class="w-12 h-12 opacity-80" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-md p-6 text-white">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm opacity-90">審計記錄</p>
              <p class="text-3xl font-bold mt-2">追蹤</p>
            </div>
            <svg class="w-12 h-12 opacity-80" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-md p-6 text-white">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm opacity-90">系統狀態</p>
              <p class="text-3xl font-bold mt-2">運行中</p>
            </div>
            <svg class="w-12 h-12 opacity-80" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Main Features -->
      <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">主要功能</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Dashboard -->
          <NuxtLink
            to="/dashboard"
            class="block bg-white rounded-lg border border-gray-200 hover:border-indigo-500 hover:shadow-lg transition-all p-6 group"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center group-hover:bg-indigo-500 transition-colors">
                <svg class="w-6 h-6 text-indigo-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
              </div>
              <h3 class="ml-4 text-lg font-semibold text-gray-900">儀表板</h3>
            </div>
            <p class="text-sm text-gray-600">查看系統整體概況、關鍵指標和最新動態</p>
          </NuxtLink>

          <!-- Customers -->
          <NuxtLink
            to="/customers"
            class="block bg-white rounded-lg border border-gray-200 hover:border-green-500 hover:shadow-lg transition-all p-6 group"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-500 transition-colors">
                <svg class="w-6 h-6 text-green-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
              </div>
              <h3 class="ml-4 text-lg font-semibold text-gray-900">客戶管理</h3>
            </div>
            <p class="text-sm text-gray-600">管理客戶資料、查看客戶資訊和互動記錄</p>
          </NuxtLink>

          <!-- Orders -->
          <NuxtLink
            to="/orders"
            class="block bg-white rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-lg transition-all p-6 group"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-500 transition-colors">
                <svg class="w-6 h-6 text-blue-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
              </div>
              <h3 class="ml-4 text-lg font-semibold text-gray-900">訂單管理</h3>
            </div>
            <p class="text-sm text-gray-600">處理訂單、追蹤訂單狀態和管理物流資訊</p>
          </NuxtLink>

          <!-- Reports -->
          <NuxtLink
            to="/reports"
            class="block bg-white rounded-lg border border-gray-200 hover:border-yellow-500 hover:shadow-lg transition-all p-6 group"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center group-hover:bg-yellow-500 transition-colors">
                <svg class="w-6 h-6 text-yellow-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
              </div>
              <h3 class="ml-4 text-lg font-semibold text-gray-900">報表中心</h3>
            </div>
            <p class="text-sm text-gray-600">查看各類業務報表、數據分析和統計圖表</p>
          </NuxtLink>

          <!-- Roles -->
          <NuxtLink
            to="/roles"
            class="block bg-white rounded-lg border border-gray-200 hover:border-purple-500 hover:shadow-lg transition-all p-6 group"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-500 transition-colors">
                <svg class="w-6 h-6 text-purple-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
              </div>
              <h3 class="ml-4 text-lg font-semibold text-gray-900">角色管理</h3>
            </div>
            <p class="text-sm text-gray-600">管理系統角色、設定權限和角色層級關係</p>
          </NuxtLink>

          <!-- Audit Logs -->
          <NuxtLink
            to="/audit-logs"
            class="block bg-white rounded-lg border border-gray-200 hover:border-red-500 hover:shadow-lg transition-all p-6 group"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center group-hover:bg-red-500 transition-colors">
                <svg class="w-6 h-6 text-red-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>
              <h3 class="ml-4 text-lg font-semibold text-gray-900">審計記錄</h3>
            </div>
            <p class="text-sm text-gray-600">查看系統操作記錄、審計日誌和安全追蹤</p>
          </NuxtLink>
        </div>
      </div>

      <!-- Additional Features -->
      <div>
        <h2 class="text-xl font-semibold text-gray-900 mb-4">其他功能</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- My Permissions -->
          <NuxtLink
            to="/permissions/my-permissions"
            class="flex items-center bg-white rounded-lg border border-gray-200 hover:border-indigo-400 hover:shadow-md transition-all p-4 group"
          >
            <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center group-hover:bg-indigo-100">
              <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
              </svg>
            </div>
            <div class="ml-4 flex-1">
              <h3 class="font-medium text-gray-900">我的權限</h3>
              <p class="text-xs text-gray-500">查看個人角色和權限</p>
            </div>
          </NuxtLink>

          <!-- Role Hierarchy -->
          <NuxtLink
            to="/roles/hierarchy"
            class="flex items-center bg-white rounded-lg border border-gray-200 hover:border-purple-400 hover:shadow-md transition-all p-4 group"
          >
            <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center group-hover:bg-purple-100">
              <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
            </div>
            <div class="ml-4 flex-1">
              <h3 class="font-medium text-gray-900">角色層級</h3>
              <p class="text-xs text-gray-500">查看角色層級結構圖</p>
            </div>
          </NuxtLink>

          <!-- Audit Statistics -->
          <NuxtLink
            to="/audit-logs/statistics"
            class="flex items-center bg-white rounded-lg border border-gray-200 hover:border-red-400 hover:shadow-md transition-all p-4 group"
          >
            <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center group-hover:bg-red-100">
              <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="ml-4 flex-1">
              <h3 class="font-medium text-gray-900">審計統計</h3>
              <p class="text-xs text-gray-500">查看審計記錄統計數據</p>
            </div>
          </NuxtLink>

          <!-- Team Management -->
          <NuxtLink
            to="/teams/manage"
            class="flex items-center bg-white rounded-lg border border-gray-200 hover:border-green-400 hover:shadow-md transition-all p-4 group"
          >
            <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center group-hover:bg-green-100">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
            </div>
            <div class="ml-4 flex-1">
              <h3 class="font-medium text-gray-900">團隊管理</h3>
              <p class="text-xs text-gray-500">管理團隊成員和組織架構</p>
            </div>
          </NuxtLink>
        </div>
      </div>

      <!-- System Info -->
      <div class="mt-8 bg-gray-100 rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="flex items-center">
              <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2"></div>
              <span class="text-sm text-gray-600">系統運行中</span>
            </div>
            <span class="text-xs text-gray-400">API: {{ config.public.apiBaseUrl }}</span>
          </div>
          <span class="text-xs text-gray-400">{{ config.public.appName }} v1.0.0</span>
        </div>
      </div>
  </LayoutContentArea>
</template>

<script setup lang="ts">
// 首頁元件
const config = useRuntimeConfig()

definePageMeta({
  title: '首頁',
  layout: 'default',
})
</script>
