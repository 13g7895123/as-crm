# CORS 問題排查指南

本文件說明如何診斷和修復 CORS（跨域資源共享）問題。

## 目錄

1. [快速診斷](#快速診斷)
2. [使用 CORS 除錯工具](#使用-cors-除錯工具)
3. [常見問題與解決方案](#常見問題與解決方案)
4. [自動化測試](#自動化測試)
5. [深入了解 CORS 運作機制](#深入了解-cors-運作機制)

---

## 快速診斷

### 1. 健康檢查

首先檢查 CORS 設定是否正確：

```bash
curl http://localhost:9230/api/v1/cors/health | jq
```

**預期結果：**
```json
{
  "status": "healthy",
  "message": "CORS 設定正常",
  "checks": {
    "cors_filter_registered": true,
    "cors_filter_in_globals_after": true,
    "has_allowed_origins": true,
    "allows_all_origins": false
  },
  "issues": []
}
```

### 2. 測試特定 Origin

測試您的前端 origin 是否被允許：

```bash
curl -H "Origin: http://localhost:3003" \
     http://localhost:9230/api/v1/cors/debug | jq
```

查看 `data.request_info.is_origin_allowed` 欄位：
- `true` = Origin 被允許 ✅
- `false` = Origin 被封鎖 ❌

---

## 使用 CORS 除錯工具

### 可用的除錯端點

#### 1. GET /api/v1/cors/debug

顯示當前請求的 CORS 診斷資訊。

**使用方式：**

```bash
curl -H "Origin: http://localhost:3003" \
     http://localhost:9230/api/v1/cors/debug | jq
```

**回應說明：**

```json
{
  "data": {
    "request_info": {
      "origin": "http://localhost:3003",      // 請求的 origin
      "is_origin_allowed": true,              // 是否被允許
      "method": "GET",
      "uri": "http://localhost:9230/api/v1/cors/debug"
    },
    "cors_config": {
      "allowed_origins": [...],               // 所有允許的 origins
      "allowed_methods": [...],               // 允許的 HTTP 方法
      "allowed_headers": [...],               // 允許的標頭
      "supports_credentials": true,
      "max_age": 86400
    },
    "applied_headers": {                      // 會套用到回應的 CORS 標頭
      "Access-Control-Allow-Origin": "http://localhost:3003",
      "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, PATCH, OPTIONS",
      ...
    },
    "recommendations": [                      // 建議事項
      "✅ Origin 'http://localhost:3003' 已被允許"
    ]
  }
}
```

#### 2. POST /api/v1/cors/test

測試特定 origin 而不需要從瀏覽器發送請求。

**使用方式：**

```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"origin": "http://localhost:3003"}' \
     http://localhost:9230/api/v1/cors/test | jq
```

**回應範例：**

```json
{
  "data": {
    "test_origin": "http://localhost:3003",
    "is_allowed": true,
    "would_apply_headers": {
      "Access-Control-Allow-Origin": "http://localhost:3003",
      ...
    },
    "result": "PASS - 此來源會被允許"
  }
}
```

#### 3. GET /api/v1/cors/health

檢查 CORS 設定的整體健康狀態。

**使用方式：**

```bash
curl http://localhost:9230/api/v1/cors/health | jq
```

---

## 常見問題與解決方案

### 問題 1：CORS 錯誤「已封鎖跨來源請求」

**症狀：**
瀏覽器控制台顯示：
```
已封鎖跨來源請求: 同源政策不允許讀取 http://localhost:9230/api/v1/... 的遠端資源
```

**診斷步驟：**

1. **檢查 origin 是否正確**

   打開瀏覽器開發者工具（F12） → Network 標籤，查看請求的 Request Headers：

   ```
   Origin: http://localhost:3003
   ```

   記下這個 origin 值。

2. **使用除錯端點檢查**

   ```bash
   curl -H "Origin: http://localhost:3003" \
        http://localhost:9230/api/v1/cors/debug | jq '.data.request_info'
   ```

   查看 `is_origin_allowed` 是否為 `true`。

3. **如果 `is_origin_allowed` 為 `false`**

   a. 編輯 `backend/app/Config/Cors.php`

   b. 在 `allowedOrigins` 陣列中加入您的 origin：

   ```php
   public array $allowedOrigins = [
       'http://localhost:3003',      // 您的前端 origin
       'http://127.0.0.1:3003',      // 同時加入替代位址
       // ... 其他 origins
   ];
   ```

   c. 重啟後端容器：

   ```bash
   docker compose restart backend
   ```

4. **清除瀏覽器快取**

   - 硬重新整理：`Ctrl + Shift + R` (Windows/Linux) 或 `Cmd + Shift + R` (Mac)
   - 或開啟無痕視窗測試

### 問題 2：localhost vs 127.0.0.1

**症狀：**
在某些瀏覽器中，`http://localhost:3003` 和 `http://127.0.0.1:3003` 被視為不同的 origin。

**解決方案：**

同時加入兩個版本到 `allowedOrigins`：

```php
public array $allowedOrigins = [
    'http://localhost:3003',
    'http://127.0.0.1:3003',
];
```

### 問題 3：OPTIONS Preflight 失敗

**症狀：**
瀏覽器發送 OPTIONS 請求但收到 404 或其他錯誤。

**診斷：**

```bash
curl -X OPTIONS \
     -H "Origin: http://localhost:3003" \
     -H "Access-Control-Request-Method: GET" \
     -i http://localhost:9230/api/v1/roles
```

**預期回應：**
- HTTP 狀態碼：204 No Content
- 包含 `Access-Control-Allow-Origin` 標頭

**解決方案：**

1. 檢查 `backend/app/Config/Routes.php` 中是否有 OPTIONS 路由：

   ```php
   $routes->options('(:any)', function () {
       return response()->setStatusCode(204);
   });
   ```

2. 確認 CorsFilter 在 `before` filter 中執行以處理 OPTIONS 請求

### 問題 4：修改 CORS 設定後未生效

**原因：**
- 後端容器未重啟
- 瀏覽器快取了 preflight 回應（max-age: 86400 秒 = 24 小時）

**解決方案：**

1. 重啟後端：
   ```bash
   docker compose restart backend
   ```

2. 清除瀏覽器快取（強制重新整理）

3. 使用無痕視窗測試

### 問題 5：帶認證的請求（Credentials）失敗

**症狀：**
前端設定了 `credentials: 'include'` 但請求被拒絕。

**診斷：**

檢查 `backend/app/Config/Cors.php`：

```php
public bool $supportsCredentials = true;  // 必須為 true
```

**注意：**
當 `supportsCredentials = true` 時，`allowedOrigins` 不能使用 `'*'`，必須明確指定允許的 origins。

---

## 自動化測試

使用提供的測試腳本來驗證 CORS 設定：

```bash
./scripts/test-cors.sh
```

**測試項目：**
1. CORS 健康檢查
2. OPTIONS Preflight 請求
3. 實際 API 請求的 CORS 標頭
4. 除錯端點功能

**自訂測試：**

```bash
# 測試特定後端 URL 和 origins
./scripts/test-cors.sh http://localhost:9230 \
    http://localhost:3003 \
    http://127.0.0.1:3003 \
    http://localhost:3000
```

**測試輸出範例：**

```
======================================
CORS 自動化測試腳本
======================================

後端 URL: http://localhost:9230
測試的 Origins:
  - http://localhost:3003
  - http://127.0.0.1:3003

[測試] CORS 健康檢查
✓ PASS - CORS 設定健康

--- 測試 Origin: http://localhost:3003 ---
[測試] OPTIONS Preflight - http://localhost:3003
✓ PASS - Preflight 請求成功，CORS 標頭正確

[測試] 實際 GET 請求 - http://localhost:3003
✓ PASS - 實際請求包含正確的 CORS 標頭

======================================
測試結果摘要
======================================
總測試數: 7
通過: 7
失敗: 0

✓ 所有測試通過！CORS 設定正常運作。
```

---

## 深入了解 CORS 運作機制

### CORS 請求流程

1. **簡單請求**（不觸發 preflight）
   - 方法：GET, HEAD, POST
   - 標頭僅包含簡單標頭（Accept, Content-Type 等）

   ```
   瀏覽器 → [GET + Origin] → 後端
   後端 → [Response + CORS Headers] → 瀏覽器
   ```

2. **需要 Preflight 的請求**
   - 使用 PUT, DELETE, PATCH 等方法
   - 包含自訂標頭（如 Authorization）
   - Content-Type 為 application/json

   ```
   瀏覽器 → [OPTIONS Preflight] → 後端
   後端 → [204 + CORS Headers] → 瀏覽器
   瀏覽器 → [實際請求] → 後端
   後端 → [Response + CORS Headers] → 瀏覽器
   ```

### CORS 標頭說明

| 標頭 | 說明 | 範例值 |
|------|------|--------|
| `Access-Control-Allow-Origin` | 允許的 origin | `http://localhost:3003` |
| `Access-Control-Allow-Methods` | 允許的 HTTP 方法 | `GET, POST, PUT, DELETE` |
| `Access-Control-Allow-Headers` | 允許的請求標頭 | `Content-Type, Authorization` |
| `Access-Control-Expose-Headers` | 瀏覽器可存取的回應標頭 | `X-Total-Count` |
| `Access-Control-Allow-Credentials` | 是否允許帶認證 | `true` |
| `Access-Control-Max-Age` | Preflight 快取時間（秒） | `86400` |

### 我們的 CORS 實作架構

```
請求進入
  ↓
CorsFilter (before)
  ├─ 檢查是否為 OPTIONS preflight
  ├─ 如果是：回傳 204 + CORS headers
  └─ 如果不是：繼續處理
  ↓
Routes + Controller 處理
  ↓
CorsFilter (after)
  ├─ 取得請求的 Origin
  ├─ 檢查是否在 allowedOrigins 中
  ├─ 如果允許：加入 CORS headers 到回應
  └─ 回傳帶 CORS headers 的回應
  ↓
回應返回給瀏覽器
```

---

## 檢查清單

使用此清單確保 CORS 設定正確：

- [ ] `backend/app/Config/Cors.php` 中的 `allowedOrigins` 包含前端 origin
- [ ] 同時包含 `localhost` 和 `127.0.0.1` 版本
- [ ] `backend/app/Config/Filters.php` 中 `corsFilter` 在 `globals.after` 陣列中
- [ ] `backend/app/Config/Routes.php` 中有 OPTIONS 路由
- [ ] 修改後已重啟後端容器
- [ ] 瀏覽器快取已清除
- [ ] 使用除錯端點驗證設定
- [ ] 自動化測試通過

---

## 取得協助

如果問題仍然存在，請：

1. 執行完整診斷：
   ```bash
   curl -H "Origin: http://localhost:3003" \
        http://localhost:9230/api/v1/cors/debug | jq > cors-debug.json
   ```

2. 執行自動化測試：
   ```bash
   ./scripts/test-cors.sh > cors-test-results.txt 2>&1
   ```

3. 檢查後端日誌：
   ```bash
   docker compose logs backend --tail 100
   ```

4. 提供以上資訊以協助診斷問題。
