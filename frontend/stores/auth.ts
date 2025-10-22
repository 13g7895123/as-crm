/**
 * Auth Store
 *
 * Pinia auth store
 * 管理 JWT token 和使用者狀態
 */

import { defineStore } from 'pinia'

export interface User {
  id: number
  username: string
  email: string
  full_name?: string
  department?: string
  region?: string
  is_active: boolean
  roles?: Array<{ id: number; name: string; display_name?: string }>
}

export interface AuthState {
  user: User | null
  accessToken: string | null
  refreshToken: string | null
  isAuthenticated: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    accessToken: null,
    refreshToken: null,
    isAuthenticated: false,
  }),

  getters: {
    /**
     * 取得當前使用者
     */
    currentUser: (state): User | null => state.user,

    /**
     * 檢查是否已登入
     */
    isLoggedIn: (state): boolean => state.isAuthenticated && !!state.accessToken,

    /**
     * 取得 access token
     */
    getAccessToken: (state): string | null => state.accessToken,

    /**
     * 取得 refresh token
     */
    getRefreshToken: (state): string | null => state.refreshToken,
  },

  actions: {
    /**
     * 設定認證資料
     */
    setAuth(user: User, accessToken: string, refreshToken: string) {
      this.user = user
      this.accessToken = accessToken
      this.refreshToken = refreshToken
      this.isAuthenticated = true

      // 儲存到 localStorage
      if (process.client) {
        const config = useRuntimeConfig()
        localStorage.setItem(config.public.jwtTokenKey, accessToken)
        localStorage.setItem(config.public.jwtRefreshTokenKey, refreshToken)
        localStorage.setItem('user', JSON.stringify(user))
      }
    },

    /**
     * 設定使用者資料
     */
    setUser(user: User) {
      this.user = user

      // 儲存到 localStorage
      if (process.client) {
        localStorage.setItem('user', JSON.stringify(user))
      }
    },

    /**
     * 更新 access token
     */
    updateAccessToken(accessToken: string) {
      this.accessToken = accessToken

      // 儲存到 localStorage
      if (process.client) {
        const config = useRuntimeConfig()
        localStorage.setItem(config.public.jwtTokenKey, accessToken)
      }
    },

    /**
     * 清除認證資料
     */
    clearAuth() {
      this.user = null
      this.accessToken = null
      this.refreshToken = null
      this.isAuthenticated = false

      // 清除 localStorage
      if (process.client) {
        const config = useRuntimeConfig()
        localStorage.removeItem(config.public.jwtTokenKey)
        localStorage.removeItem(config.public.jwtRefreshTokenKey)
        localStorage.removeItem('user')
      }
    },

    /**
     * 從 localStorage 恢復認證狀態
     */
    restoreAuth() {
      if (process.client) {
        const config = useRuntimeConfig()
        const accessToken = localStorage.getItem(config.public.jwtTokenKey)
        const refreshToken = localStorage.getItem(config.public.jwtRefreshTokenKey)
        const userStr = localStorage.getItem('user')

        if (accessToken && refreshToken && userStr) {
          try {
            const user = JSON.parse(userStr)
            this.user = user
            this.accessToken = accessToken
            this.refreshToken = refreshToken
            this.isAuthenticated = true
          } catch (error) {
            console.error('解析使用者資料失敗:', error)
            this.clearAuth()
          }
        }
      }
    },

    /**
     * 檢查 token 是否過期
     */
    isTokenExpired(): boolean {
      if (!this.accessToken) {
        return true
      }

      try {
        // 解析 JWT token (簡單版本,不驗證簽章)
        const payload = JSON.parse(atob(this.accessToken.split('.')[1]))
        const exp = payload.exp

        if (!exp) {
          return true
        }

        // 檢查是否過期 (提前 5 分鐘)
        return Date.now() >= (exp * 1000) - (5 * 60 * 1000)
      } catch (error) {
        console.error('解析 token 失敗:', error)
        return true
      }
    },
  },
})
