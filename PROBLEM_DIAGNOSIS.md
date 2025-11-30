# 問題診斷結果

## 問題原因
前端 `/roles` 頁面請求 API 時返回 **401 Unauthorized**，原因是：
- ✅ 後端 API 工作正常（已測試）
- ✅ Vite Proxy 配置正確
- ❌ **前端沒有登入，缺少 Authorization Token**

## 診斷過程

### 1. 檢查後端服務
```bash
docker compose ps
# ✅ 後端容器正常運行 (healthy)
```

### 2. 測試後端 API
```bash
curl http://localhost:9230/api/v1/roles
# ❌ 返回 401: "缺少身份驗證 token"
```

### 3. 使用正確認證測試
```bash
# 登入獲取 token
curl -X POST http://localhost:9230/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "admin123"}'
# ✅ 成功獲取 token

# 使用 token 請求 roles
curl http://localhost:9230/api/v1/roles \
  -H "Authorization: Bearer <token>"
# ✅ 成功獲取 4 個角色
```

## 解決方案

### 方法 1: 前端登入（推薦）
1. 打開瀏覽器訪問 `http://localhost:3000/login`
2. 使用以下認證資訊登入：
   - **使用者名稱**: `admin`
   - **密碼**: `admin123`
3. 登入成功後訪問 `/roles` 頁面

### 方法 2: 檢查認證狀態
如果已經登入但仍然失敗，檢查：
1. 打開瀏覽器開發者工具 Console
2. 執行以下代碼：
```javascript
// 檢查 localStorage 中的 token
console.log('Access Token:', localStorage.getItem('crm_access_token'))
console.log('Refresh Token:', localStorage.getItem('crm_refresh_token'))
```
3. 如果沒有 token 或 token 已過期，請重新登入

### 方法 3: 清除快取重新登入
如果 token 過期或損壞：
```javascript
// 在瀏覽器 Console 執行
localStorage.removeItem('crm_access_token')
localStorage.removeItem('crm_refresh_token')
// 然後重新登入
```

## 已做的修正

### 1. 更新 `nuxt.config.ts`
- ✅ 添加詳細的 Proxy 日誌
- ✅ 修正 TypeScript 類型錯誤

### 2. 更新 `useRoles.ts`
- ✅ 添加詳細的請求日誌
- ✅ 包含認證狀態檢查

### 3. 創建測試腳本
- ✅ `test-roles-with-auth.sh` - 完整的認證測試流程

## 測試步驟

### 使用測試腳本
```bash
./test-roles-with-auth.sh
```

### 手動測試前端
1. 確保服務運行：
   ```bash
   docker compose ps
   # 檢查 backend 和 database 狀態
   ```

2. 訪問前端：
   ```
   http://localhost:3000/login
   ```

3. 登入後查看 Console 日誌：
   - 應該看到 `[useRoles]` 開頭的詳細日誌
   - 確認 token 存在且有效

## 預設測試帳號

- **使用者名稱**: `admin`
- **密碼**: `admin123`
- **角色**: 系統管理員

## 常見問題

### Q: 為什麼 API 需要認證？
A: 這是 RBAC 權限管理系統，所有 API 都需要驗證使用者身份和權限。

### Q: Token 有效期多久？
A: 
- Access Token: 1 小時 (3600 秒)
- Refresh Token: 7 天 (604800 秒)

### Q: Token 存儲在哪裡？
A: 存儲在瀏覽器的 `localStorage` 中：
- Key: `crm_access_token`
- Key: `crm_refresh_token`

## 下一步

1. ✅ 後端 API 已確認正常
2. ✅ Proxy 配置已優化
3. ✅ 添加了詳細日誌
4. 📝 **請在瀏覽器中登入後再次測試 `/roles` 頁面**
