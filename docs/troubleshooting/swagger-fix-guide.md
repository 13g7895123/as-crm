# Swagger UI 只顯示一個連結 - 修復指南

## 問題描述

訪問 http://localhost:9230/swagger 時，只看到頂部的 "CRM RBAC Permission Management API" 連結，而沒有看到完整的 Swagger UI 介面。

## 已完成的修復

我已經改進了 Swagger UI 的實現，添加了以下功能：

### 1. 錯誤處理和診斷

- ✅ 添加了 "Loading..." 載入提示
- ✅ 添加了 JavaScript 錯誤捕獲和顯示
- ✅ 添加了 CDN 資源載入失敗檢測
- ✅ 添加了詳細的錯誤訊息顯示

### 2. 診斷工具

創建了一個診斷頁面，可以幫助您找出問題：

**訪問診斷頁面**: http://localhost:9230/swagger/diag

這個頁面會自動檢查：
- OpenAPI 規範文件是否存在
- 服務器配置信息
- 端點是否可訪問
- CDN 資源是否可載入

## 立即解決步驟

### 步驟 1: 訪問診斷頁面

在瀏覽器中打開：http://localhost:9230/swagger/diag

這會告訴您具體問題所在。

### 步驟 2: 清除瀏覽器緩存

**這是最重要的步驟！**

舊版本的 JavaScript 可能被瀏覽器緩存了。

**Windows/Linux**:
- 按 `Ctrl + Shift + R` 進行硬性重新整理
- 或 `Ctrl + F5`

**Mac**:
- 按 `Cmd + Shift + R`
- 或 `Cmd + Option + E`（清除緩存）然後 `Cmd + R`

**手動清除**:
1. 按 `F12` 打開開發者工具
2. 右鍵點擊瀏覽器的重新整理按鈕
3. 選擇 "清空緩存並硬性重新載入"

### 步驟 3: 檢查瀏覽器控制台

1. 訪問 http://localhost:9230/swagger
2. 按 `F12` 打開開發者工具
3. 切換到 "Console" (控制台) 標籤
4. 查看是否有紅色錯誤訊息

### 常見錯誤和解決方法

#### 錯誤 1: "Failed to load Swagger UI libraries"
```
❌ Failed to load Swagger UI libraries
CDN resources may be blocked
```

**原因**: CDN (unpkg.com) 被防火牆或網絡阻擋

**解決方法**:
1. 檢查網絡連接
2. 確認可以訪問 https://unpkg.com
3. 如果在公司網絡，檢查代理設置
4. 嘗試使用手機熱點測試

**測試 CDN**:
```bash
curl -I https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui.css
# 應該返回 HTTP/2 200
```

#### 錯誤 2: "Failed to fetch /swagger/spec"
```
❌ Failed to fetch
❌ Resolver error: Error downloading /swagger/spec
```

**原因**: OpenAPI 規範文件無法載入

**解決方法**:
```bash
# 測試規範端點
curl http://localhost:9230/swagger/spec | head -10

# 應該看到:
# openapi: 3.0.3
# info:
#   title: CRM RBAC Permission Management API
```

如果返回 404，運行：
```bash
docker compose restart backend
```

#### 錯誤 3: "SwaggerUIBundle is not defined"
```
❌ SwaggerUIBundle is not defined
```

**原因**: Swagger UI JavaScript 庫未載入

**解決方法**:
1. 清除瀏覽器緩存
2. 確認網絡連接正常
3. 檢查瀏覽器是否阻擋了外部 script

#### 錯誤 4: 只看到 "Loading API Documentation..."
**原因**: JavaScript 可能正在執行但很慢，或有錯誤

**解決方法**:
1. 等待 10-20 秒
2. 檢查控制台是否有錯誤
3. 檢查 Network 標籤，查看 `/swagger/spec` 請求狀態

## 驗證修復

### 正常的 Swagger UI 應該顯示：

1. ✅ 頂部黑色欄位: "CRM RBAC Permission Management API"
2. ✅ API 標題和描述
3. ✅ 伺服器選擇下拉菜單
4. ✅ "Authorize" 按鈕（右上角綠色鎖圖示）
5. ✅ 7 個 API 分類標籤：
   - Authentication
   - Roles
   - Permissions
   - Role Assignments
   - Audit Logs
   - User Permissions
   - CORS Debug
6. ✅ 每個標籤可以展開查看端點
7. ✅ 每個端點可以 "Try it out" 測試

### 使用自動化測試腳本

```bash
cd /home/jarvis/project/idea/as/crm
./scripts/test-swagger.sh
```

應該看到所有測試通過：
```
✓ Swagger UI HTML page
✓ OpenAPI Specification (YAML)
✓ Valid OpenAPI 3.0.3 spec
✓ Correct relative URL
✓ Backend is responding
```

## 進階診斷

### 檢查實際 HTML 輸出

```bash
# 查看返回的 HTML
curl http://localhost:9230/swagger | head -100

# 應該看到:
# <!DOCTYPE html>
# <html lang="en">
# ...
# <div class="loading">Loading API Documentation...</div>
```

### 檢查 JavaScript 配置

```bash
# 查看 Swagger UI 配置
curl -s http://localhost:9230/swagger | grep -A 5 "SwaggerUIBundle"

# 應該看到:
# const ui = SwaggerUIBundle({
#     url: "/swagger/spec",
#     dom_id: '#swagger-ui',
```

### 檢查後端日誌

```bash
# 查看最近的請求日誌
docker compose logs backend --tail 50 | grep swagger

# 應該看到成功的請求:
# [200]: GET /swagger
# [200]: GET /swagger/spec
```

## 不同瀏覽器測試

有時候問題特定於某個瀏覽器：

### Chrome / Edge
1. 開啟無痕模式: `Ctrl+Shift+N`
2. 訪問 http://localhost:9230/swagger
3. 如果正常工作，問題是緩存或擴展

### Firefox
1. 開啟隱私瀏覽: `Ctrl+Shift+P`
2. 訪問 http://localhost:9230/swagger

### Safari (Mac)
1. 清除歷史記錄: `Cmd+Option+E`
2. 訪問 http://localhost:9230/swagger

## 如果仍然無法解決

### 完整重置流程

```bash
# 1. 停止所有服務
docker compose down

# 2. 重新啟動
docker compose up -d

# 3. 等待服務就緒
sleep 30

# 4. 檢查狀態
docker compose ps

# 5. 測試端點
curl http://localhost:9230/swagger/spec | head -5

# 6. 清除所有瀏覽器數據
# 在瀏覽器中: 設置 > 隱私和安全 > 清除瀏覽數據
# 選擇: 緩存圖像和文件、Cookie 和其他網站數據

# 7. 訪問診斷頁面
# http://localhost:9230/swagger/diag

# 8. 訪問 Swagger UI
# http://localhost:9230/swagger
```

### 替代方案：使用 OpenAPI Spec 文件

如果 Swagger UI 仍然無法工作，您可以：

1. **下載 OpenAPI 規範**:
   ```bash
   curl http://localhost:9230/swagger/spec > openapi.yaml
   ```

2. **使用在線 Swagger Editor**:
   - 訪問 https://editor.swagger.io
   - 將 openapi.yaml 內容貼上

3. **使用本地 Swagger UI**:
   ```bash
   # 下載 Swagger UI
   git clone https://github.com/swagger-api/swagger-ui.git
   cd swagger-ui/dist

   # 修改 index.html 中的 url 為您的 spec 地址
   # 使用簡單的 HTTP server
   python3 -m http.server 8000

   # 訪問 http://localhost:8000
   ```

## 改進內容總結

我已經對 SwaggerController.php 做了以下改進：

### 1. 增強的錯誤處理
```javascript
try {
    const ui = SwaggerUIBundle({...});
} catch (error) {
    // 顯示錯誤訊息給用戶
}
```

### 2. CDN 載入檢測
```javascript
if (typeof SwaggerUIBundle === 'undefined') {
    // 告訴用戶 CDN 被阻擋
}
```

### 3. 載入狀態提示
```html
<div class="loading">Loading API Documentation...</div>
```

### 4. 回調函數
```javascript
onComplete: function() {
    console.log("Swagger UI loaded successfully");
},
onFailure: function(error) {
    // 顯示失敗訊息
}
```

## 獲取幫助

如果以上步驟都無法解決問題：

1. **訪問診斷頁面**: http://localhost:9230/swagger/diag
2. **截圖錯誤訊息**: 從瀏覽器控制台 (F12)
3. **收集日誌**:
   ```bash
   docker compose logs backend --tail 100 > backend-logs.txt
   ```
4. **提供信息**:
   - 瀏覽器版本
   - 操作系統
   - 錯誤截圖
   - 控制台錯誤訊息

---

**更新時間**: 2025-10-23
**狀態**: 已修復並增強錯誤處理
**相關文件**:
- `backend/app/Controllers/SwaggerController.php` - 主控制器
- `backend/app/Controllers/SwaggerDiagController.php` - 診斷工具
- `scripts/test-swagger.sh` - 自動測試腳本
