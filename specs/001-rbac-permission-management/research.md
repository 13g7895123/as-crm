# 技術研究：RBAC 權限管理系統

**Date**: 2025-10-21
**Feature**: RBAC 權限管理系統
**Purpose**: 研究技術選型、最佳實踐、和實作策略

## 技術堆疊決策

### 後端框架：CodeIgniter 4

**決策**：使用 CodeIgniter 4.4+ 作為後端 PHP 框架

**理由**：
- 輕量且高效能的 PHP 框架，適合 RESTful API 開發
- 內建 Query Builder 支援複雜的資料庫查詢
- CI4 Shield 提供完整的身份驗證和授權基礎
- 支援 PHP 8.1+ 特性（strict types, attributes, enums）
- 良好的 migration 和 seeding 支援
- 活躍的社群和持續維護

**考慮的替代方案**：
- Laravel: 功能更豐富但較重量級，對於純 API 開發可能過度設計
- Slim Framework: 更輕量但缺乏內建功能，需要更多第三方套件整合
- Symfony: 企業級框架但學習曲線較陡峭

### 前端框架：Nuxt 3

**決策**：使用 Nuxt 3 (Vue 3 + TypeScript) 作為前端框架

**理由**：
- 基於 Vue 3 Composition API，提供優異的開發體驗
- 內建 TypeScript 支援，提供型別安全
- 自動路由生成，簡化 SPA 開發
- SSR/SSG 支援，提升 SEO 和首屏載入速度
- Pinia 整合良好，狀態管理簡潔
- 豐富的 Nuxt 模組生態系統

**考慮的替代方案**：
- React + Next.js: 生態系統更大但學習曲線較陡峭
- Angular: 功能完整但過於龐大，不符合輕量化要求
- SvelteKit: 效能優異但生態系統相對較小

### 資料庫：MariaDB 10.6+

**決策**：使用 MariaDB 10.6+ 作為主要資料庫

**理由**：
- 完全相容 MySQL 且效能更優
- 支援 JSON 欄位，適合儲存條件限制規則
- 內建 query cache（雖然預設關閉，但可針對特定查詢啟用）
- 優異的索引效能，適合大量審計記錄查詢
- 開源且社群活躍

**替代 Redis 的快取策略**：
由於不使用 Redis，採用以下策略：
1. MariaDB 自身的 query cache（針對不常變動的查詢）
2. Application-level caching（PHP APCu 或檔案快取）
3. 使用者權限快取在 session 中
4. Database connection pooling

**考慮的替代方案**：
- PostgreSQL: 功能更強大但對於此專案需求 MariaDB 已足夠
- MySQL 8.0: 功能類似但 MariaDB 效能稍優

## RBAC 實作策略

### 權限檢查機制

**決策**：採用 Filter + Service 層架構

**實作方式**：
1. **PermissionFilter** (CI4 Filter): 在請求進入 controller 前進行權限檢查
2. **AuthorizationService**: 核心權限驗證邏輯
3. **PermissionChecker Library**: 可重用的權限檢查工具

**流程**：
```
Request → AuthFilter (驗證身份) → PermissionFilter (檢查權限) → Controller → Service → Model → Database
```

**權限快取策略**：
- 使用者登入時載入所有有效角色和權限到 session
- 權限變更時清除相關使用者的 session cache
- 定期（每 30 分鐘）重新驗證權限

### 條件式限制實作

**決策**：使用 JSON 欄位 + Dynamic Query Builder

**資料結構**：
```json
{
  "conditions": [
    {
      "field": "department",
      "operator": "equals",
      "value": "業務部"
    },
    {
      "field": "region",
      "operator": "in",
      "value": ["華東", "華南"]
    }
  ],
  "logic": "AND"
}
```

**查詢生成**：
- 動態解析 JSON 條件
- 生成對應的 SQL WHERE 子句
- 使用 Query Builder 的 where() 方法組合條件

### 時間性授權實作

**決策**：使用 Scheduled Task + Database triggers

**實作方式**：
1. **role_assignments** 表包含 `valid_from` 和 `valid_until` 欄位
2. 權限檢查時驗證當前時間是否在有效期內
3. CI4 Scheduled Task 每小時執行一次，清理過期的角色指派
4. 過期前通知：Cron job 每日檢查即將過期的角色（7天、3天、1天前）

### 角色階層繼承

**決策**：使用 Closure Table 模式

**資料結構**：
- `roles` 表：基本角色資訊
- `role_hierarchy` 表：儲存角色之間的所有祖先-後代關係（包含直接和間接）

**Closure Table 優點**：
- 查詢效率高（一次查詢即可取得所有繼承權限）
- 支援多層階層
- 更新相對簡單

**範例**：
```
roles: 系統管理員, 部門主管, 業務主管, 業務人員

role_hierarchy:
ancestor_id | descendant_id | depth
------------|---------------|------
1           | 1             | 0   (自己)
1           | 2             | 1   (直接子角色)
1           | 3             | 2   (間接子角色)
1           | 4             | 3   (間接子角色)
2           | 2             | 0
2           | 3             | 1
2           | 4             | 2
3           | 3             | 0
3           | 4             | 1
4           | 4             | 0
```

### 審計記錄策略

**決策**：使用 Model Events + Dedicated AuditLog Table

**實作方式**：
1. **Model Events**: 在 Model 的 afterInsert, afterUpdate, afterDelete 事件中記錄
2. **AuditService**: 統一的審計記錄服務
3. **audit_logs** 表設計：
   - 包含欄位：user_id, action, target_type, target_id, old_values (JSON), new_values (JSON), ip_address, created_at
   - 索引：user_id, created_at, target_type, action
4. **資料歸檔**：90 天後自動將審計記錄移至歸檔表 (audit_logs_archive)

**儲存修改前後值**：
```php
// Example audit log entry
{
  "user_id": 123,
  "action": "update",
  "target_type": "customer",
  "target_id": 456,
  "old_values": {"phone": "0912345678", "region": "華北"},
  "new_values": {"phone": "0987654321", "region": "華東"},
  "ip_address": "192.168.1.100",
  "created_at": "2025-10-21 14:30:00"
}
```

## 最佳實踐

### CodeIgniter 4 RBAC 最佳實踐

1. **使用 Filters 進行權限檢查**
   - 建立 `PermissionFilter` 檢查使用者是否有權限執行操作
   - 在路由層級設定所需權限

2. **Service 層處理業務邏輯**
   - RoleService, PermissionService 處理角色和權限的 CRUD
   - AuthorizationService 統一處理權限驗證邏輯

3. **使用 Migrations 管理資料庫結構**
   - 所有表結構變更透過 migration
   - 使用 seeder 建立預設角色和權限

4. **API 版本控制**
   - 路由前綴使用版本號：`/api/v1/roles`
   - 保留向後相容性

### Nuxt 3 權限管理最佳實踐

1. **使用 Composables 封裝權限邏輯**
   ```typescript
   // composables/usePermissions.ts
   export const usePermissions = () => {
     const can = (permission: string): boolean => {
       // Check if user has permission
     }
     const canAny = (permissions: string[]): boolean => {
       // Check if user has any of the permissions
     }
     return { can, canAny }
   }
   ```

2. **使用 Middleware 保護路由**
   ```typescript
   // middleware/permission.ts
   export default defineNuxtRouteMiddleware((to, from) => {
     const { can } = usePermissions()
     if (!can(to.meta.permission)) {
       return navigateTo('/forbidden')
     }
   })
   ```

3. **Pinia Store 快取權限**
   - 登入後載入使用者權限到 store
   - 提供 reactive 的權限狀態
   - 權限變更時更新 store

4. **UI 元件權限控制**
   ```vue
   <template>
     <button v-if="can('customer:edit')" @click="editCustomer">
       編輯客戶
     </button>
   </template>
   ```

### MariaDB 效能優化

1. **索引策略**
   - `role_assignments`: (user_id, valid_from, valid_until)
   - `audit_logs`: (user_id, created_at), (target_type, target_id, created_at)
   - `permissions`: (role_id, module, action)

2. **查詢優化**
   - 使用 eager loading 避免 N+1 queries
   - 條件限制查詢使用 prepared statements
   - 審計記錄查詢限制時間範圍

3. **資料分割**
   - 審計記錄按月份分割（partitioning by month）
   - 歸檔策略：90 天後移至歸檔表

## 安全性考量

### JWT Token 策略

**決策**：使用 JWT 進行 API 身份驗證

**實作**：
1. 登入成功後發送 JWT access token (有效期 1 小時) 和 refresh token (有效期 7 天)
2. Access token 包含 user_id 和基本權限資訊
3. Refresh token 用於更新 access token
4. Token 儲存在 HttpOnly cookie (防止 XSS)

### CORS 設定

- 僅允許特定 origin (frontend domain)
- 允許 credentials (cookies)
- 設定允許的 headers 和 methods

### SQL Injection 防護

- 全面使用 Query Builder 或 Prepared Statements
- 不使用字串拼接 SQL

### XSS 防護

- Nuxt 3 預設轉義輸出
- 使用 DOMPurify 清理使用者輸入的 HTML（如有需要）

## 效能目標驗證策略

### 權限檢查效能

**目標**：<100ms

**策略**：
1. Session cache 權限資料
2. 資料庫索引優化
3. 使用 APCu cache 快取角色-權限對應

**驗證**：使用 PHPUnit + benchmark 測試

### API 回應時間

**目標**：p95 <200ms (read), <500ms (write)

**策略**：
1. 資料庫查詢優化
2. 減少不必要的關聯查詢
3. 使用 database query caching

**驗證**：使用 Apache JMeter 或 k6 進行負載測試

### 前端載入時間

**目標**：TTI <3s on 3G

**策略**：
1. Code splitting (Nuxt 自動)
2. Lazy loading components
3. 圖片優化
4. 使用 CDN

**驗證**：使用 Lighthouse CI

## 開發工具和環境

### 開發環境設定

**Backend**:
- PHP 8.1+ with Composer
- MariaDB 10.6+ or MySQL 8.0+
- Xdebug for debugging
- PHPUnit for testing

**Frontend**:
- Node.js 18+ with npm/pnpm
- TypeScript 5.0+
- Vite (Nuxt 3 內建)
- Vitest for testing
- Playwright for e2e testing

### CI/CD 工具

- Git for version control
- GitHub Actions / GitLab CI for CI/CD
- PHPStan for static analysis
- ESLint + Prettier for code formatting

## Docker 容器化策略

### 決策：使用 Docker Compose 進行環境管理

**理由**：
- 簡化開發環境設定，新成員可快速啟動
- 確保開發、測試、生產環境一致性
- 容易進行水平擴展（scale out）
- 隔離服務依賴，避免版本衝突
- 支援容器健康檢查和自動重啟

### 容器架構設計

**開發環境 (docker-compose.yml)**：
```yaml
services:
  - database (MariaDB 10.6)
  - backend (PHP 8.1-cli with CodeIgniter 4)
  - frontend (Node 18 with Nuxt 3)
```

**生產環境 (docker-compose.prod.yml)**：
```yaml
services:
  - database (MariaDB 10.6, resource limits)
  - backend (PHP 8.1-fpm with OPcache)
  - frontend (Node 18, optimized build)
  - nginx (reverse proxy, SSL termination)
```

### Dockerfile 最佳實踐

**Backend Dockerfile**：
- 多階段構建：開發環境使用 `php:8.1-cli`，生產環境使用 `php:8.1-fpm`
- OPcache 優化：生產環境啟用 OPcache 以提升 PHP 效能
- APCu 快取：用於應用程式層級的資料快取
- Composer 優化：生產環境使用 `--no-dev --optimize-autoloader`
- 適當的檔案權限：`writable/` 目錄設定為 777，`public/uploads/` 允許寫入

**Frontend Dockerfile**：
- 多階段構建：Builder stage 編譯，Production stage 僅包含 `.output`
- 生產環境：使用 `node .output/server/index.mjs` 執行預先編譯的 Nuxt 應用
- 開發環境：掛載 volumes 以支援 HMR (Hot Module Replacement)
- 資源限制：限制 CPU 和記憶體使用，符合效能約束

**Nginx Configuration**：
- HTTP 自動重導向至 HTTPS
- FastCGI 配置用於 PHP-FPM
- 反向代理用於 Nuxt SSR
- 靜態檔案快取（uploads, assets）
- CORS headers 設定
- SSL/TLS 最佳實踐（TLS 1.2+, 安全加密套件）

### Build Scripts 策略

**build-dev.sh (開發環境)**：
- 自動建立必要目錄結構
- 產生 `.env` 檔案（如果不存在）
- 建立 Dockerfiles（如果不存在）
- 執行 `docker compose up -d --build`
- 自動執行 migrations 和 seeders
- 提供詳細的輸出訊息和除錯指引

**build.sh (生產環境)**：
- 驗證 `.env.prod` 檔案存在
- 建立生產環境最佳化的 Docker 映像檔
- 設定資源限制（CPU, Memory）
- 啟用健康檢查和自動重啟
- 提供部署驗證步驟
- 包含 SSL 憑證檢查和警告

### 資源管理

**記憶體限制**：
- Backend: 512MB (limit), 256MB (reservation)
- Frontend: 512MB (limit), 256MB (reservation)
- Database: 1GB (limit), 512MB (reservation)
- Nginx: 256MB (limit)

**CPU 限制**：
- Backend: 1 core (limit), 0.5 core (reservation)
- Frontend: 1 core (limit), 0.5 core (reservation)
- Database: 2 cores (limit), 1 core (reservation)
- Nginx: 0.5 core (limit)

### 健康檢查策略

**Database**：
```yaml
healthcheck:
  test: mysqladmin ping -h localhost
  interval: 30s (production), 10s (development)
  timeout: 10s
  retries: 3
```

**服務依賴**：
- Backend depends on: `database` (condition: service_healthy)
- Frontend depends on: `backend`
- Nginx depends on: `backend`, `frontend`

### 資料持久化

**Volumes**：
- `db_data`: MariaDB 資料目錄
- `./backend/writable`: 日誌和快取（開發環境掛載）
- `./backend/public/uploads`: 使用者上傳檔案

**備份策略**：
- 每日自動備份資料庫（使用 cron job）
- 保留最近 7 天的備份
- 上傳檔案定期同步至雲端儲存

### 網路設定

**開發環境**：
- Bridge network：`crm_network`
- 容器間通訊：使用 service name (e.g., `database`, `backend`)
- Port mapping：直接 expose 到 host (3306, 8080, 3000)

**生產環境**：
- Bridge network：`crm_network`
- 僅 Nginx expose ports (80, 443)
- 內部服務使用 internal network

### 日誌管理

**日誌驅動**：
```yaml
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"
```

**日誌收集**：
- Backend: CodeIgniter logs -> `writable/logs/`
- Frontend: Nuxt logs -> stdout (由 Docker 收集)
- Nginx: access.log, error.log -> stdout/stderr
- Database: slow query log, error log -> `/var/lib/mysql/`

### 安全性考量

**Docker Security**：
- 不使用 `privileged` mode
- 限制容器權限（drop unnecessary capabilities）
- 定期更新 base images
- 掃描映像檔漏洞（使用 Docker Scout 或 Trivy）
- 使用非 root user 執行應用（生產環境）

**Secrets 管理**：
- 敏感資訊使用 `.env.prod`（不提交至 Git）
- 生產環境考慮使用 Docker Secrets 或外部 secrets manager
- JWT keys 使用環境變數傳遞

**Network Security**：
- 內部服務不直接 expose 到外部
- 僅透過 Nginx 反向代理存取
- 使用 SSL/TLS 加密通訊

### 監控與除錯

**容器監控**：
```bash
# 資源使用
docker stats

# 健康狀態
docker compose ps

# 日誌查看
docker compose logs -f [service]
```

**效能監控**：
- 使用 `docker stats` 監控資源使用
- 設定警報當記憶體或 CPU 超過閾值
- 定期檢查容器健康狀態

## 總結

本研究確定了以下技術選型和實作策略：

1. **技術堆疊**：CodeIgniter 4 + Nuxt 3 + MariaDB
2. **容器化部署**：Docker Compose + Build scripts（開發/生產環境）
3. **權限檢查**：Filter + Service 層，session cache 權限
4. **條件限制**：JSON 欄位 + Dynamic Query Builder
5. **時間性授權**：Database fields + Scheduled tasks
6. **角色階層**：Closure Table 模式
7. **審計記錄**：Model Events + Dedicated table + 自動歸檔
8. **快取策略**：無 Redis，使用 Session + APCu + Database cache
9. **安全性**：JWT + CORS + SQL Injection防護 + XSS 防護 + Docker security best practices
10. **資源管理**：容器資源限制 + 健康檢查 + 自動重啟

所有決策皆考量效能目標、可擴展性、和維護性，並符合 Constitution 要求。
