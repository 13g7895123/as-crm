# Swagger UI 故障排除指南 (Troubleshooting Guide)

## 問題已解決 ✅

**問題**: Swagger UI 無法正常顯示
**原因**: 配置中的端口號不匹配 (使用了 8080 而不是 9230)
**解決方案**: 已將 Swagger UI 配置改為使用相對路徑 `/swagger/spec`

## 驗證修復

運行診斷腳本來驗證一切正常：

```bash
./scripts/test-swagger.sh
```

如果所有測試都通過，Swagger UI 現在應該可以正常工作了！

## 訪問 Swagger UI

### 正確的 URL
✅ **使用這個**: http://localhost:9230/swagger
❌ **不要使用**: http://localhost:8080/swagger (錯誤的端口)

## 如果仍然無法顯示

### 1. 清除瀏覽器緩存

瀏覽器可能緩存了舊版本的 JavaScript：

- **Chrome/Edge**: `Ctrl+Shift+R` (Windows/Linux) 或 `Cmd+Shift+R` (Mac)
- **Firefox**: `Ctrl+F5` (Windows/Linux) 或 `Cmd+Shift+R` (Mac)
- **Safari**: `Cmd+Option+R`

或者手動清除：
1. 打開開發者工具 (F12)
2. 右鍵點擊刷新按鈕
3. 選擇 "清空緩存並硬性重新載入"

### 2. 檢查瀏覽器控制台錯誤

1. 打開 Swagger UI 頁面: http://localhost:9230/swagger
2. 按 `F12` 打開開發者工具
3. 切換到 "Console" (控制台) 標籤
4. 查看是否有紅色錯誤訊息

**常見錯誤及解決方法**:

#### 錯誤: `Failed to fetch` 或 `Network Error`
```
❌ Failed to fetch http://localhost:8080/swagger/spec
```

**原因**: 仍在使用舊的錯誤端口
**解決**:
- 清除瀏覽器緩存
- 確認訪問的是 http://localhost:9230/swagger 而不是 :8080

#### 錯誤: `CORS policy` 相關
```
❌ Access to fetch at '...' has been blocked by CORS policy
```

**原因**: CORS 配置問題
**解決**:
```bash
# 檢查 CORS 配置
curl http://localhost:9230/api/v1/cors/debug

# 重啟後端服務
docker compose restart backend
```

#### 錯誤: `Invalid API definition`
```
❌ Resolver error: Error downloading ...
```

**原因**: OpenAPI 規範文件無法加載
**解決**:
```bash
# 測試規範文件是否可訪問
curl http://localhost:9230/swagger/spec

# 驗證 YAML 格式
curl http://localhost:9230/swagger/spec | head -20
```

### 3. 檢查後端服務狀態

```bash
# 檢查容器是否運行
docker compose ps

# 應該看到:
# NAME              STATUS
# crm_backend_dev   Up X minutes (healthy)

# 如果未運行，啟動它
docker compose up -d backend

# 檢查後端日誌
docker compose logs backend --tail 50
```

### 4. 驗證端點可訪問性

手動測試各個端點：

```bash
# 測試 Swagger UI 頁面
curl -I http://localhost:9230/swagger
# 期望: HTTP/1.1 200 OK

# 測試 OpenAPI 規範
curl -I http://localhost:9230/swagger/spec
# 期望: HTTP/1.1 200 OK
# 期望: Content-Type: text/yaml

# 測試 API 基礎路徑
curl http://localhost:9230/api/v1/cors/health
# 期望: JSON 響應
```

### 5. 檢查防火牆或代理

如果使用公司網絡或 VPN：

1. 確認 9230 端口未被防火牆阻擋
2. 確認代理設置不會干擾 localhost 請求
3. 嘗試使用 `127.0.0.1:9230` 而不是 `localhost:9230`

### 6. 瀏覽器兼容性

Swagger UI 支持的瀏覽器：
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Edge 90+
- ✅ Safari 14+

如果使用舊版本瀏覽器，請升級到最新版本。

### 7. 端口被佔用

檢查 9230 端口是否被其他應用佔用：

```bash
# Linux/Mac
lsof -i :9230

# Windows
netstat -ano | findstr :9230

# 如果被佔用，停止其他服務或在 docker-compose.yml 中更改端口
```

## 完整重置流程

如果以上都不行，執行完整重置：

```bash
# 1. 停止所有服務
docker compose down

# 2. 清除 Docker 緩存（可選）
docker system prune -f

# 3. 重新啟動服務
docker compose up -d

# 4. 等待服務就緒（約 30 秒）
sleep 30

# 5. 檢查健康狀態
docker compose ps

# 6. 運行診斷腳本
./scripts/test-swagger.sh

# 7. 清除瀏覽器緩存並訪問
# http://localhost:9230/swagger
```

## 已修復的問題總結

### 修復前
```javascript
// SwaggerController.php 生成的 HTML 中：
url: "http://localhost:8080/swagger/spec"  // ❌ 錯誤的端口
```

### 修復後
```javascript
// SwaggerController.php 生成的 HTML 中：
url: "/swagger/spec"  // ✅ 使用相對路徑，自動適配端口
```

### 其他改進
1. ✅ 改進了 CORS 響應頭
2. ✅ 修正了 Content-Type 為 `text/yaml`
3. ✅ 創建了自動診斷腳本
4. ✅ 添加了詳細的故障排除文檔

## 驗證一切正常

如果你看到這樣的畫面，說明 Swagger UI 正常工作：

```
✓ Swagger UI 頁面加載完成
✓ 頂部顯示 "CRM RBAC Permission Management API"
✓ 可以看到 7 個標籤：
  - Authentication
  - Roles
  - Permissions
  - Role Assignments
  - Audit Logs
  - User Permissions
  - CORS Debug
✓ 可以展開和測試各個端點
```

## 需要更多幫助？

1. 運行診斷腳本查看詳細信息:
   ```bash
   ./scripts/test-swagger.sh
   ```

2. 查看後端日誌:
   ```bash
   docker compose logs backend --tail 100
   ```

3. 檢查 API 是否正常工作:
   ```bash
   curl http://localhost:9230/api/v1/cors/health
   ```

## 技術細節

### 文件位置
- **OpenAPI Spec**: `/home/jarvis/project/idea/as/crm/backend/docs/openapi.yaml`
- **Swagger Controller**: `/home/jarvis/project/idea/as/crm/backend/app/Controllers/SwaggerController.php`
- **Routes**: `/home/jarvis/project/idea/as/crm/backend/app/Config/Routes.php`

### 端點映射
- `GET /swagger` → SwaggerController::index() → Swagger UI HTML
- `GET /swagger/spec` → SwaggerController::openapi() → OpenAPI YAML
- `GET /swagger/spec/json` → SwaggerController::openapiJson() → OpenAPI JSON

---

**最後更新**: 2025-10-23
**狀態**: ✅ 已解決
