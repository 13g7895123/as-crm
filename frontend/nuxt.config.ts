// ============================================
// Nuxt 3 配置檔案
// ============================================
// CRM RBAC 權限管理系統前端配置

export default defineNuxtConfig({
  // SSR 配置 - 關閉以使用純客戶端渲染模式
  ssr: false,

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
      // API Base URL
      // 開發環境：使用相對路徑，透過 nitro.config.ts 的 devProxy 代理到後端
      // 生產環境：使用完整 URL，需要後端配置 CORS
      apiBaseUrl: process.env.NODE_ENV === 'production'
        ? (process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:9230/api/v1')
        : '/api/v1',
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
  css: [
    '~/assets/css/main.css',
  ],

  // Vite 配置
  vite: {
    server: {
      // API Proxy 配置 - 用於開發環境避免 CORS 問題
      proxy: {
        '/api': {
          target: 'http://localhost:9230',
          changeOrigin: true,
          secure: false,
          rewrite: (path) => path,
          configure: (proxy, options) => {
            proxy.on('error', (err, req, res) => {
              console.log('❌ Proxy error:', err);
            });
            proxy.on('proxyReq', (proxyReq, req, res) => {
              const target = options.target || 'unknown';
              const url = req.url || '';
              console.log('🔄 Proxying:', req.method, url, '→', target + url);
            });
            proxy.on('proxyRes', (proxyRes, req, res) => {
              console.log('✅ Proxy response:', proxyRes.statusCode, req.url || '');
            });
          },
        }
      },
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
