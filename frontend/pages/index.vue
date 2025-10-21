<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="text-center">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">
        {{ config.public.appName }}
      </h1>
      <p class="text-xl text-gray-600 mb-8">
        RBAC 權限管理系統
      </p>
      <div class="space-y-4">
        <p class="text-gray-500">
          Phase 1: Setup 已完成
        </p>
        <p class="text-sm text-gray-400">
          API Base URL: {{ config.public.apiBaseUrl }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// 首頁元件
const config = useRuntimeConfig()
</script>
