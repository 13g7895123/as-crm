// ============================================
// Nuxt 3 配置檔案
// ============================================
// CRM RBAC 權限管理系統前端配置

export default defineNuxtConfig({
  // 開發工具
  devtools: { enabled: true },

  // TypeScript 配置
  typescript: {
    strict: true,
    typeCheck: true,
  },

  // Modules
  modules: [
    '@nuxt/ui',
    '@pinia/nuxt',
    '@vueuse/nuxt',
  ],

  // 執行時配置
  runtimeConfig: {
    // 私有配置（僅伺服器端可用）
    // apiSecret: '',

    // 公開配置（客戶端和伺服器端都可用）
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8080/api/v1',
      appName: process.env.NUXT_PUBLIC_APP_NAME || 'CRM 權限管理系統',
      appVersion: process.env.NUXT_PUBLIC_APP_VERSION || '1.0.0',
      // JWT 相關配置
      jwtTokenKey: 'crm_access_token',
      jwtRefreshTokenKey: 'crm_refresh_token',
      jwtTokenExpiry: 3600, // 1 小時 (秒)
      jwtRefreshTokenExpiry: 604800, // 7 天 (秒)
    },
  },

  // Nuxt UI 配置
  ui: {},

  // CSS 配置
  css: [],

  // Vite 配置
  vite: {
    server: {
      hmr: {
        protocol: 'ws',
        host: '0.0.0.0',
        port: 24678,
      },
    },
  },

  // 應用程式配置
  app: {
    head: {
      title: 'CRM 權限管理系統',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'CRM RBAC 權限管理系統 - 提供細緻的角色和權限管理功能' },
      ],
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
      ],
    },
  },

  // Pinia 配置
  pinia: {
    storesDirs: ['./stores/**'],
  },

  // 路由配置
  router: {
    options: {
      strict: true,
    },
  },

  // 實驗性功能
  experimental: {
    payloadExtraction: false,
  },

  // 相容性
  compatibilityDate: '2025-01-01',
})
