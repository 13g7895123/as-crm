/**
 * useAuth Composable
 *
 * 身份驗證 composable
 * 提供 login/logout/refresh token/getToken 方法
 */

import { useAuthStore } from '~/stores/auth'
import type { User } from '~/stores/auth'

export interface LoginCredentials {
  username: string
  password: string
}

export interface LoginResponse {
  status: string
  message: string
  data: {
    user: User
    access_token: string
    refresh_token: string
    expires_in: number
  }
}

export interface RefreshTokenResponse {
  status: string
  message: string
  data: {
    access_token: string
    expires_in: number
  }
}

export const useAuth = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()

  /**
   * 登入
   */
  const login = async (credentials: LoginCredentials): Promise<boolean> => {
    try {
      const response = await $fetch<LoginResponse>(`${config.public.apiBaseUrl}/auth/login`, {
        method: 'POST',
        body: credentials,
      })

      if (response.status === 'success' && response.data) {
        // 儲存認證資料到 store
        authStore.setAuth(
          response.data.user,
          response.data.access_token,
          response.data.refresh_token
        )

        return true
      }

      return false
    } catch (error: any) {
      console.error('登入失敗:', error)
      throw new Error(error.data?.message || '登入失敗,請稍後再試')
    }
  }

  /**
   * 登出
   */
  const logout = async (): Promise<void> => {
    try {
      const token = authStore.getAccessToken

      if (token) {
        // 呼叫登出 API
        await $fetch(`${config.public.apiBaseUrl}/auth/logout`, {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        })
      }
    } catch (error) {
      console.error('登出 API 呼叫失敗:', error)
      // 即使 API 失敗,仍然清除本地認證資料
    } finally {
      // 清除認證資料
      authStore.clearAuth()
    }
  }

  /**
   * 刷新 token
   */
  const refreshToken = async (): Promise<boolean> => {
    try {
      const refreshTokenValue = authStore.getRefreshToken

      if (!refreshTokenValue) {
        return false
      }

      const response = await $fetch<RefreshTokenResponse>(`${config.public.apiBaseUrl}/auth/refresh`, {
        method: 'POST',
        body: {
          refresh_token: refreshTokenValue,
        },
      })

      if (response.status === 'success' && response.data) {
        // 更新 access token
        authStore.updateAccessToken(response.data.access_token)
        return true
      }

      return false
    } catch (error) {
      console.error('刷新 token 失敗:', error)
      // Token 刷新失敗,清除認證資料
      authStore.clearAuth()
      return false
    }
  }

  /**
   * 取得 access token
   */
  const getToken = (): string | null => {
    return authStore.getAccessToken
  }

  /**
   * 檢查使用者是否已登入
   */
  const isAuthenticated = computed(() => authStore.isLoggedIn)

  /**
   * 取得當前使用者
   */
  const currentUser = computed(() => authStore.currentUser)

  /**
   * 初始化認證狀態
   */
  const initAuth = () => {
    // 從 localStorage 恢復認證狀態
    authStore.restoreAuth()

    // 檢查 token 是否過期
    if (authStore.isTokenExpired()) {
      // 嘗試刷新 token
      refreshToken().catch(() => {
        // 刷新失敗,清除認證資料
        authStore.clearAuth()
      })
    }
  }

  /**
   * 取得當前使用者資料
   */
  const fetchCurrentUser = async (): Promise<User | null> => {
    try {
      const token = authStore.getAccessToken

      if (!token) {
        return null
      }

      const response = await $fetch<{ status: string; data: { user: User } }>(
        `${config.public.apiBaseUrl}/auth/me`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      )

      if (response.status === 'success' && response.data) {
        authStore.setUser(response.data.user)
        return response.data.user
      }

      return null
    } catch (error) {
      console.error('取得使用者資料失敗:', error)
      return null
    }
  }

  return {
    login,
    logout,
    refreshToken,
    getToken,
    isAuthenticated,
    currentUser,
    initAuth,
    fetchCurrentUser,
  }
}
