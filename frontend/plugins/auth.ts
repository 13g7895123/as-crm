/**
 * Auth Plugin
 *
 * 在應用程式啟動時初始化認證狀態
 * - 從 localStorage 恢復認證資料
 * - 檢查 token 是否過期
 * - 如果過期則嘗試刷新
 */

export default defineNuxtPlugin(() => {
  const { initAuth } = useAuth()

  // 初始化認證狀態
  initAuth()
})
