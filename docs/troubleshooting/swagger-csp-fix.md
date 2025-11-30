# Swagger UI CSP 錯誤修復

## 問題描述

訪問 Swagger UI (http://localhost:9230/swagger) 時，瀏覽器控制台顯示大量 Content Security Policy (CSP) 錯誤：

### 錯誤列表

1. **Permissions-Policy 警告**:
   ```
   Error with Permissions-Policy header: Unrecognized feature: 'ambient-light-sensor'.
   ```

2. **CSP 重複指令警告**:
   ```
   Ignoring duplicate Content-Security-Policy directive 'script-src'.
   Ignoring duplicate Content-Security-Policy directive 'style-src'.
   ```

3. **CSS 載入被阻擋**:
   ```
   Refused to load the stylesheet 'https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui.css'
   because it violates the following Content Security Policy directive: "style-src 'self'".
   ```

4. **內聯樣式被阻擋**:
   ```
   Refused to apply inline style because it violates the following Content Security Policy
   directive: "style-src 'self'". Either the 'unsafe-inline' keyword, a hash, or a nonce
   is required to enable inline execution.
   ```

5. **JavaScript 載入被阻擋**:
   ```
   Refused to load the script 'https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-bundle.js'
   because it violates the following Content Security Policy directive: "script-src 'self'".

   Refused to load the script 'https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js'
   because it violates the following Content Security Policy directive: "script-src 'self'".
   ```

6. **內聯腳本被阻擋**:
   ```
   Refused to execute inline script because it violates the following Content Security Policy
   directive: "script-src 'self'". Either the 'unsafe-inline' keyword, a hash, or a nonce
   is required to enable inline execution.
   ```

## 根本原因

**SecurityHeadersFilter** 設置了非常嚴格的 Content Security Policy，只允許載入來自 `'self'` 的資源。這阻止了：

1. **外部 CDN 資源** - Swagger UI 從 `unpkg.com` 載入 CSS 和 JavaScript
2. **內聯樣式** - HTML 中的 `<style>` 標籤
3. **內聯腳本** - HTML 中的 `<script>` 標籤和內聯 JavaScript

### 原始 CSP 策略
```
script-src 'self'
style-src 'self'
```

這個策略太嚴格，無法支持 Swagger UI。

## 解決方案

### 修改的文件
`backend/app/Filters/SecurityHeadersFilter.php`

### 實施的改進

#### 1. 路徑檢測
添加了對 `/swagger` 路徑的檢測：

```php
// Get request URI
$uri = $request->getUri();
$path = $uri->getPath();

// Check if this is a Swagger UI request
$isSwaggerUI = strpos($path, '/swagger') === 0;
```

#### 2. Swagger UI 專用 CSP 策略
為 Swagger UI 創建了寬鬆的 CSP 策略：

```php
// Special CSP for Swagger UI - needs to load external resources
if ($isSwaggerUI) {
    return implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://unpkg.com",  // ✅ 允許 unpkg.com 和內聯腳本
        "style-src 'self' 'unsafe-inline' https://unpkg.com",   // ✅ 允許 unpkg.com 和內聯樣式
        "img-src 'self' data: https://unpkg.com",               // ✅ 允許圖片
        "font-src 'self' https://unpkg.com",                    // ✅ 允許字體
        "connect-src 'self'",                                   // ✅ 允許 fetch /swagger/spec
        "frame-ancestors 'none'",                               // 🔒 仍然防止 clickjacking
        "base-uri 'self'",                                      // 🔒 安全限制
        "form-action 'self'",                                   // 🔒 安全限制
    ]);
}
```

#### 3. 修復 Permissions-Policy
移除了不被所有瀏覽器識別的 `ambient-light-sensor`：

```php
// 'ambient-light-sensor=()', // Removed - not recognized in all browsers
```

### 安全考量

雖然為 Swagger UI 放寬了 CSP 策略，但仍然保持了重要的安全限制：

#### ✅ 保持的安全措施
- ✅ **僅允許特定 CDN** - 只允許 `https://unpkg.com`，不是所有外部源
- ✅ **限制連接** - `connect-src 'self'` 確保只能連接到本站 API
- ✅ **防止點擊劫持** - `frame-ancestors 'none'` 防止 iframe 嵌入
- ✅ **基礎 URI 限制** - `base-uri 'self'` 防止基礎 URL 攻擊
- ✅ **表單提交限制** - `form-action 'self'` 防止表單提交到外部網站
- ✅ **路徑限制** - 只對 `/swagger` 路徑放寬策略，其他 API 路徑保持嚴格

#### ⚠️ 權衡
- `'unsafe-inline'` - 允許內聯腳本和樣式
  - **風險**: 可能受到 XSS 攻擊
  - **緩解**: Swagger UI 頁面不接受用戶輸入，風險較低
  - **替代方案**: 可以使用 nonce 或 hash，但會使實現更複雜

## 驗證修復

### 檢查 CSP 標頭

```bash
curl -s -D - http://localhost:9230/swagger -o /dev/null | grep -i "content-security-policy"
```

**預期輸出**:
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com; style-src 'self' 'unsafe-inline' https://unpkg.com; img-src 'self' data: https://unpkg.com; font-src 'self' https://unpkg.com; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'
```

### 檢查 Permissions-Policy

```bash
curl -s -D - http://localhost:9230/swagger -o /dev/null | grep -i "permissions-policy"
```

**預期輸出** (不再包含 ambient-light-sensor):
```
Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=(), autoplay=(), encrypted-media=(), picture-in-picture=()
```

### 瀏覽器驗證

1. 清除瀏覽器緩存: `Ctrl+Shift+R` (或 `Cmd+Shift+R`)
2. 訪問 http://localhost:9230/swagger
3. 按 `F12` 打開開發者工具
4. 查看 Console 標籤

**應該看到**:
- ✅ 沒有 CSP 錯誤
- ✅ Swagger UI 完整載入
- ✅ 可以看到所有 API 端點
- ✅ "Swagger UI loaded successfully" 訊息（如果有）

## API 端點的 CSP 策略保持不變

**重要**: 只有 `/swagger` 路徑使用寬鬆的 CSP。所有 API 端點 (`/api/v1/*`) 仍然使用嚴格的 CSP 策略：

```
script-src 'self'
style-src 'self'
```

這確保了 API 的安全性不受影響。

## 錯誤解決對照表

| 原始錯誤 | 解決方法 | 狀態 |
|---------|---------|------|
| `Permissions-Policy: ambient-light-sensor` | 移除不支持的策略 | ✅ 已修復 |
| `Duplicate script-src directive` | 修正 CSP 生成邏輯 | ✅ 已修復 |
| `Refused to load CSS from unpkg.com` | 添加 `https://unpkg.com` 到 `style-src` | ✅ 已修復 |
| `Refused to apply inline style` | 添加 `'unsafe-inline'` 到 `style-src` | ✅ 已修復 |
| `Refused to load script from unpkg.com` | 添加 `https://unpkg.com` 到 `script-src` | ✅ 已修復 |
| `Refused to execute inline script` | 添加 `'unsafe-inline'` 到 `script-src` | ✅ 已修復 |

## 最佳實踐建議

### 當前實現（良好）
- ✅ 為不同路徑使用不同的 CSP 策略
- ✅ 保持 API 端點的嚴格安全策略
- ✅ 明確列出允許的外部源
- ✅ 記錄安全決策

### 未來改進（可選）

#### 1. 使用 Nonce 替代 unsafe-inline
```php
$nonce = base64_encode(random_bytes(16));
$response->setHeader('Content-Security-Policy',
    "script-src 'self' 'nonce-{$nonce}' https://unpkg.com"
);

// 在 HTML 中
<script nonce="<?= $nonce ?>">
    // JavaScript 代碼
</script>
```

#### 2. 本地託管 Swagger UI
```bash
# 下載 Swagger UI 資源到 public/assets/swagger-ui/
# 不再依賴外部 CDN
```

#### 3. 使用 CSP Report-Only 模式測試
```php
$response->setHeader('Content-Security-Policy-Report-Only', $csp);
```

#### 4. 添加 CSP 違規報告端點
```php
"report-uri /api/v1/csp-report"
```

## 故障排除

### 問題: 清除緩存後仍有 CSP 錯誤

**解決**:
1. 確認修改已部署:
   ```bash
   docker compose restart backend
   ```

2. 驗證 CSP 標頭:
   ```bash
   curl -I http://localhost:9230/swagger 2>&1 | grep CSP
   ```

3. 檢查路徑匹配:
   ```bash
   # 確認訪問的是 /swagger 而不是其他路徑
   ```

### 問題: 其他頁面受影響

**確認**: CSP 放寬只應用於 `/swagger` 路徑。檢查：
```bash
# API 端點應該仍有嚴格 CSP
curl -I http://localhost:9230/api/v1/auth/login 2>&1 | grep CSP
```

## 技術細節

### CSP 指令說明

| 指令 | 用途 | Swagger 設置 |
|-----|------|-------------|
| `default-src` | 所有資源的默認策略 | `'self'` |
| `script-src` | JavaScript 源 | `'self' 'unsafe-inline' https://unpkg.com` |
| `style-src` | CSS 源 | `'self' 'unsafe-inline' https://unpkg.com` |
| `img-src` | 圖片源 | `'self' data: https://unpkg.com` |
| `font-src` | 字體源 | `'self' https://unpkg.com` |
| `connect-src` | AJAX/Fetch 源 | `'self'` |
| `frame-ancestors` | 可嵌入此頁面的源 | `'none'` |
| `base-uri` | <base> 標籤允許的 URL | `'self'` |
| `form-action` | 表單提交目標 | `'self'` |

### CSP 關鍵字

- `'self'` - 同源資源
- `'unsafe-inline'` - 允許內聯代碼（不推薦用於生產環境）
- `'unsafe-eval'` - 允許 eval() 等動態代碼執行
- `'nonce-xxx'` - 使用一次性令牌驗證內聯代碼
- `'sha256-xxx'` - 使用 hash 驗證內聯代碼

---

**修復日期**: 2025-10-23
**狀態**: ✅ 已完全修復
**影響範圍**: 僅 /swagger 路徑
**安全影響**: 最小（保持了核心安全控制）

**下一步**: 清除瀏覽器緩存並重新載入 Swagger UI 頁面即可看到完整的 API 文檔！
