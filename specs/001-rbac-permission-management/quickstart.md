# 快速開始：RBAC 權限管理系統開發指南

**Date**: 2025-10-21
**Feature**: RBAC 權限管理系統
**Branch**: 001-rbac-permission-management

## 概述

本指南協助開發人員快速設定開發環境並開始實作 RBAC 權限管理系統。

## 前置需求

### 使用 Docker（推薦）

- Docker 20.10+
- Docker Compose 2.x
- Git

### 手動安裝

#### Backend (CodeIgniter 4)

- PHP 8.1 或更高版本
- Composer 2.x
- MariaDB 10.6+ 或 MySQL 8.0+
- Apache/Nginx web server (開發環境可使用 PHP built-in server)

#### Frontend (Nuxt 3)

- Node.js 18+ (建議使用 Node.js 20 LTS)
- npm 或 pnpm

### 開發工具

- Git
- Visual Studio Code (建議)
  - PHP Intelephense 擴充套件
  - Volar (Vue 3) 擴充套件
  - ESLint 擴充套件

## 環境設定

### 選項 A：使用 Docker（推薦）

這是最快速且最簡單的方式，無需手動安裝 PHP、Node.js 或 MariaDB。

#### 1. 克隆專案

```bash
git clone <repository-url>
cd crm
git checkout 001-rbac-permission-management
```

#### 2. 執行開發環境建置腳本

##### 選項 2a：完整環境啟動（推薦首次使用）

```bash
./build-dev.sh
```

建置腳本會自動完成以下工作：
- 建立必要的目錄結構
- 產生 `.env` 設定檔（所有 port 集中管理）
- 建置 Docker 映像檔
- 啟動所有服務（資料庫、後端、前端）
- 執行資料庫 migrations 和 seeders

##### 選項 2b：分離式啟動（推薦前端開發時使用）

當您頻繁修改前端程式碼時，可使用分離式腳本以提升開發效率：

```bash
# 步驟 1: 啟動後端服務（資料庫 + API）
./build-backend-dev.sh

# 步驟 2: 在另一個終端啟動前端
./build-frontend-dev.sh
```

**分離式啟動的優勢**：
- **前端重啟更快**: 修改前端程式碼後，只需重啟前端容器
- **後端穩定運行**: 前端頻繁重啟不影響後端服務
- **資源效率**: 避免重建整個環境

**使用情境**：
- `build-backend-dev.sh`: 後端開發、API 測試
- `build-frontend-dev.sh`: UI/UX 開發、前端組件調整
- `build-dev.sh`: 初次環境設定、全棧開發

#### 3. 訪問應用程式

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8080
- **Database**: localhost:3306

**自訂 Port 配置**:

所有服務 port 定義於專案根目錄的 `.env` 檔案：

```bash
# .env 檔案內容（port 在最上方 export）
export DB_PORT=3306
export BACKEND_PORT=8080
export FRONTEND_PORT=3000
export FRONTEND_HMR_PORT=24678
```

若要變更 port：
1. 編輯 `.env` 檔案，修改對應的 port 變數
2. 重新啟動服務：`docker compose down && docker compose up -d`

#### 常用 Docker 指令

```bash
# 查看所有容器狀態
docker compose ps

# 查看日誌
docker compose logs -f

# 查看特定服務日誌
docker compose logs -f backend
docker compose logs -f frontend

# 停止所有服務
docker compose down

# 重新啟動服務
docker compose restart

# 進入 backend 容器
docker compose exec backend bash

# 進入 frontend 容器
docker compose exec frontend sh

# 執行 migrations
docker compose exec backend php spark migrate

# 執行 seeders
docker compose exec backend php spark db:seed RoleSeeder
```

### 選項 B：手動安裝

如果您偏好手動安裝所有元件，請按照以下步驟操作。

#### 1. 克隆專案

```bash
git clone <repository-url>
cd crm
git checkout 001-rbac-permission-management
```

### 2. Backend 設定

#### 2.1 安裝相依套件

```bash
cd backend
composer install
```

#### 2.2 環境設定

複製 `.env` 範例檔案並設定資料庫連線：

```bash
cp env .env
```

編輯 `.env` 檔案：

```ini
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = development

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = localhost
database.default.database = crm_db
database.default.username = root
database.default.password = your_password
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306

#--------------------------------------------------------------------
# JWT Authentication
#--------------------------------------------------------------------
JWT_SECRET_KEY = your-256-bit-secret-key-here
JWT_TIME_TO_LIVE = 3600  # 1 hour
JWT_REFRESH_TIME_TO_LIVE = 604800  # 7 days

#--------------------------------------------------------------------
# CORS
#--------------------------------------------------------------------
CORS_ALLOWED_ORIGINS = http://localhost:3000
```

#### 2.3 建立資料庫

```bash
# 連線到 MariaDB/MySQL
mysql -u root -p

# 建立資料庫
CREATE DATABASE crm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

#### 2.4 執行 Migrations

```bash
php spark migrate

# 執行 seeders（建立預設角色和權限）
php spark db:seed RoleSeeder
php spark db:seed PermissionSeeder
php spark db:seed RolePermissionSeeder
```

#### 2.5 啟動開發伺服器

```bash
php spark serve
```

Backend API 將運行於 `http://localhost:8080`

### 3. Frontend 設定

#### 3.1 安裝相依套件

```bash
cd frontend
npm install
# 或使用 pnpm
pnpm install
```

#### 3.2 環境設定

建立 `.env` 檔案：

```bash
# API Base URL
NUXT_PUBLIC_API_BASE_URL=http://localhost:8080/api/v1

# App Configuration
NUXT_PUBLIC_APP_NAME=CRM 系統
```

#### 3.3 啟動開發伺服器

```bash
npm run dev
# 或
pnpm dev
```

Frontend 將運行於 `http://localhost:3000`

## 專案結構

### Backend 結構

```
backend/
├── app/
│   ├── Controllers/
│   │   └── API/
│   │       ├── RoleController.php          # 角色 CRUD API
│   │       ├── PermissionController.php    # 權限查詢 API
│   │       ├── RoleAssignmentController.php # 角色指派 API
│   │       └── AuditLogController.php      # 審計記錄查詢 API
│   ├── Models/
│   │   ├── RoleModel.php                   # 角色資料模型
│   │   ├── PermissionModel.php             # 權限資料模型
│   │   ├── RoleAssignmentModel.php         # 角色指派資料模型
│   │   ├── AuditLogModel.php               # 審計記錄資料模型
│   │   └── ConditionRuleModel.php          # 條件限制規則資料模型
│   ├── Services/
│   │   ├── RoleService.php                 # 角色業務邏輯
│   │   ├── PermissionService.php           # 權限業務邏輯
│   │   ├── AuthorizationService.php        # 權限驗證邏輯
│   │   └── AuditService.php                # 審計記錄服務
│   ├── Filters/
│   │   ├── AuthFilter.php                  # JWT 身份驗證
│   │   └── PermissionFilter.php            # 權限檢查
│   ├── Libraries/
│   │   └── PermissionChecker.php           # 權限檢查工具類別
│   └── Database/
│       ├── Migrations/                     # 資料庫遷移檔案
│       └── Seeds/                          # 種子資料
└── tests/
    ├── unit/                               # 單元測試
    ├── integration/                        # 整合測試
    └── contract/                           # 合約測試
```

### Frontend 結構

```
frontend/
├── pages/
│   ├── roles/
│   │   ├── index.vue                       # 角色清單頁面
│   │   ├── create.vue                      # 建立角色頁面
│   │   └── [id]/
│   │       └── edit.vue                    # 編輯角色頁面
│   ├── permissions/
│   │   └── my-permissions.vue              # 我的權限頁面
│   ├── teams/
│   │   └── manage.vue                      # 團隊成員權限管理
│   └── audit/
│       └── logs.vue                        # 審計記錄查詢頁面
├── components/
│   ├── roles/
│   │   ├── RoleForm.vue                    # 角色表單元件
│   │   ├── PermissionSelector.vue          # 權限選擇器元件
│   │   └── ConditionBuilder.vue            # 條件建構器元件
│   ├── permissions/
│   │   └── PermissionMatrix.vue            # 權限矩陣顯示元件
│   └── audit/
│       └── AuditLogTable.vue               # 審計記錄表格元件
├── composables/
│   ├── useRoles.ts                         # 角色相關 composable
│   ├── usePermissions.ts                   # 權限檢查 composable
│   └── useAuth.ts                          # 身份驗證 composable
├── stores/
│   ├── auth.ts                             # 使用者身份驗證 store
│   ├── permissions.ts                      # 權限快取 store
│   └── roles.ts                            # 角色資料 store
└── tests/
    ├── unit/                               # Vitest 單元測試
    └── e2e/                                # Playwright E2E 測試
```

## 開發流程

### 1. 遵循 TDD 原則（P1 功能）

對於 P1 優先級的核心功能（角色和權限管理），採用 TDD 開發流程：

#### Backend TDD 範例

```bash
# 1. 先寫測試
vim tests/unit/RoleServiceTest.php

# 2. 執行測試（預期失敗）
vendor/bin/phpunit tests/unit/RoleServiceTest.php

# 3. 實作功能
vim app/Services/RoleService.php

# 4. 再次執行測試（預期成功）
vendor/bin/phpunit tests/unit/RoleServiceTest.php

# 5. 重構（如需要）
```

#### Frontend TDD 範例

```bash
# 1. 先寫測試
vim tests/unit/usePermissions.test.ts

# 2. 執行測試（預期失敗）
npm run test:unit

# 3. 實作功能
vim composables/usePermissions.ts

# 4. 再次執行測試（預期成功）
npm run test:unit
```

### 2. 實作順序（依 User Story 優先級）

#### Phase 1: P1 - 系統管理員建立自訂角色與權限

**Backend 開發順序**:

1. **Database Migrations**
   ```bash
   php spark migrate
   php spark db:seed RoleSeeder
   php spark db:seed PermissionSeeder
   ```

2. **Models** (`app/Models/`)
   - RoleModel.php
   - PermissionModel.php
   - RolePermissionModel.php
   - ConditionRuleModel.php

3. **Services** (`app/Services/`)
   - RoleService.php (CRUD logic)
   - PermissionService.php (query logic)

4. **Controllers** (`app/Controllers/API/`)
   - RoleController.php
   - PermissionController.php

5. **Tests**
   - Unit tests for Services
   - Integration tests for API endpoints
   - Contract tests for API schemas

**Frontend 開發順序**:

1. **Stores** (`stores/`)
   - roles.ts
   - permissions.ts

2. **Composables** (`composables/`)
   - useRoles.ts
   - usePermissions.ts

3. **Components** (`components/roles/`)
   - RoleForm.vue
   - PermissionSelector.vue
   - ConditionBuilder.vue

4. **Pages** (`pages/roles/`)
   - index.vue (角色清單)
   - create.vue (建立角色)
   - [id]/edit.vue (編輯角色)

5. **Tests**
   - Unit tests for composables
   - E2E tests for role management flow

#### Phase 2: P2 - 業務主管管理團隊成員權限

**Backend**:
- RoleAssignmentModel.php
- RoleAssignmentService.php
- RoleAssignmentController.php
- PermissionFilter.php (權限檢查)

**Frontend**:
- pages/teams/manage.vue
- components/teams/MemberPermissionEditor.vue

#### Phase 3: P2 - 業務人員在權限範圍內執行操作

**Backend**:
- PermissionChecker.php (library)
- AuthorizationService.php
- 整合權限檢查到各個 Controller

**Frontend**:
- usePermissions.ts (權限檢查 composable)
- 在 components 中整合權限控制

#### Phase 4: P3 - 稽核人員檢視操作審計記錄

**Backend**:
- AuditLogModel.php
- AuditService.php (自動記錄)
- AuditLogController.php (查詢 API)

**Frontend**:
- pages/audit/logs.vue
- components/audit/AuditLogTable.vue

#### Phase 5: P3 - 系統管理員設定角色階層與繼承

**Backend**:
- RoleHierarchyModel.php
- 更新 RoleService.php (支援階層)
- 更新權限查詢邏輯（含繼承）

**Frontend**:
- components/roles/RoleHierarchyTree.vue

### 3. API 測試

使用 Postman 或 HTTPie 測試 API：

```bash
# 登入取得 JWT token
http POST http://localhost:8080/api/v1/auth/login username=admin password=admin123

# 使用 token 呼叫 API
http GET http://localhost:8080/api/v1/roles "Authorization: Bearer <your-jwt-token>"

# 建立角色
http POST http://localhost:8080/api/v1/roles \
  "Authorization: Bearer <token>" \
  name=regional_manager \
  display_name="區域主管" \
  permissions:='[1,2,3]'
```

### 4. 執行測試

#### Backend 測試

```bash
# 執行所有測試
vendor/bin/phpunit

# 執行特定測試
vendor/bin/phpunit tests/unit/RoleServiceTest.php

# 執行測試並產生覆蓋率報告
vendor/bin/phpunit --coverage-html coverage/
```

#### Frontend 測試

```bash
# 單元測試
npm run test:unit

# E2E 測試
npm run test:e2e

# 覆蓋率報告
npm run test:coverage
```

## 常見開發情境

### 情境 1：新增一個權限

1. 在 `PermissionSeeder.php` 中新增權限資料
2. 執行 seeder: `php spark db:seed PermissionSeeder`
3. 更新前端權限列表

### 情境 2：新增條件限制類型

1. 在 `ConditionRuleModel.php` 中定義新的 condition_type
2. 在 `AuthorizationService.php` 中實作驗證邏輯
3. 更新前端 `ConditionBuilder.vue` 元件

### 情境 3：Debug 權限檢查問題

1. 檢查 `audit_logs` 表的 `permission_check_failed` 記錄
2. 使用 `PermissionChecker` library 的 debug mode
3. 檢查 session 中快取的權限資料

## 效能優化建議

### Backend 優化

1. **使用 APCu 快取權限資料**
   ```php
   // 快取角色權限 30 分鐘
   $cache = \Config\Services::cache();
   $permissions = $cache->remember("role_{$roleId}_permissions", 1800, function() {
       return $this->getPermissions($roleId);
   });
   ```

2. **使用 Eager Loading 避免 N+1 問題**
   ```php
   $roles = $this->roleModel
       ->with(['permissions', 'conditionRules'])
       ->findAll();
   ```

3. **資料庫索引優化**
   - 確保所有 foreign keys 都有索引
   - 為常用查詢欄位建立複合索引

### Frontend 優化

1. **使用 Lazy Loading**
   ```typescript
   const RoleForm = defineAsyncComponent(() => import('~/components/roles/RoleForm.vue'))
   ```

2. **權限快取在 Pinia Store**
   ```typescript
   // stores/permissions.ts
   export const usePermissionsStore = defineStore('permissions', () => {
     const permissions = ref<Permission[]>([])
     const loadedAt = ref<Date | null>(null)
     const TTL = 30 * 60 * 1000 // 30 minutes

     const needsRefresh = computed(() => {
       if (!loadedAt.value) return true
       return Date.now() - loadedAt.value.getTime() > TTL
     })
   })
   ```

## 除錯技巧

### Backend 除錯

1. **啟用 Debug Toolbar**
   ```php
   // .env
   CI_DEBUG = true
   ```

2. **查看 SQL 查詢**
   ```php
   $db = \Config\Database::connect();
   $db->enableQueryLog();
   // ... your queries
   $queries = $db->getQueries();
   log_message('debug', json_encode($queries));
   ```

3. **使用 Xdebug**
   設定 VS Code 的 `launch.json` 用於 PHP debugging

### Frontend 除錯

1. **使用 Vue Devtools**
   安裝 Vue Devtools 瀏覽器擴充套件

2. **Console Logging**
   ```typescript
   // 在 composables 中
   console.log('[usePermissions] Checking permission:', permission)
   ```

3. **Network 檢查**
   使用瀏覽器的 Network tab 檢查 API 請求和回應

## 生產環境部署

### 使用 Docker 部署到生產環境

#### 1. 準備 .env.prod 檔案

在專案根目錄建立 `.env.prod` 檔案，包含生產環境的設定：

```bash
# 資料庫設定
DB_ROOT_PASSWORD=your_secure_root_password
DB_NAME=crm_db
DB_USER=crm_user
DB_PASSWORD=your_secure_database_password

# JWT 設定
JWT_SECRET_KEY=your_256_bit_secret_key_here_change_this
JWT_TIME_TO_LIVE=3600
JWT_REFRESH_TIME_TO_LIVE=604800

# CORS 設定
CORS_ALLOWED_ORIGINS=https://your-domain.com

# API 設定
API_BASE_URL=https://your-domain.com/api/v1
APP_NAME=CRM 系統
```

#### 2. 準備 SSL 憑證

將您的 SSL 憑證放置在 `docker/nginx/ssl/` 目錄：

```bash
# 使用 Let's Encrypt
sudo certbot certonly --standalone -d your-domain.com
sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem docker/nginx/ssl/cert.pem
sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem docker/nginx/ssl/key.pem

# 或使用自己的憑證
cp your-cert.pem docker/nginx/ssl/cert.pem
cp your-key.pem docker/nginx/ssl/key.pem
```

#### 3. 執行生產環境建置腳本

```bash
./build.sh
```

建置腳本會自動完成：
- 建立生產環境 `.env` 檔案
- 建置最佳化的 Docker 映像檔
- 啟動所有服務（含 Nginx 反向代理）
- 執行資料庫 migrations
- 執行初始 seeders

#### 4. 驗證部署

```bash
# 檢查容器狀態
docker compose -f docker-compose.prod.yml ps

# 檢查日誌
docker compose -f docker-compose.prod.yml logs -f

# 測試 API
curl https://your-domain.com/api/v1/health

# 測試前端
curl https://your-domain.com
```

#### 5. 生產環境維護

```bash
# 查看日誌
docker compose -f docker-compose.prod.yml logs -f [service_name]

# 重新啟動服務
docker compose -f docker-compose.prod.yml restart

# 更新應用程式
git pull
docker compose -f docker-compose.prod.yml build --no-cache
docker compose -f docker-compose.prod.yml up -d

# 資料庫備份
docker compose -f docker-compose.prod.yml exec database \
  mysqldump -u root -p${DB_ROOT_PASSWORD} crm_db > backup-$(date +%Y%m%d).sql

# 資料庫還原
docker compose -f docker-compose.prod.yml exec -T database \
  mysql -u root -p${DB_ROOT_PASSWORD} crm_db < backup-20251021.sql
```

#### 生產環境監控

建議設定以下監控：

1. **容器健康檢查**
   ```bash
   # 設定 cron job 每 5 分鐘檢查
   */5 * * * * docker compose -f /path/to/docker-compose.prod.yml ps | grep -q "unhealthy" && /path/to/alert.sh
   ```

2. **日誌輪替**
   ```bash
   # 在 docker-compose.prod.yml 中已設定
   logging:
     driver: "json-file"
     options:
       max-size: "10m"
       max-file: "3"
   ```

3. **資源使用監控**
   ```bash
   # 查看資源使用
   docker stats
   ```

## 部署前檢查清單

- [ ] 所有測試通過（backend + frontend）
- [ ] 測試覆蓋率 >80%
- [ ] API 文件（OpenAPI specs）已更新
- [ ] 資料庫 migrations 已測試
- [ ] 效能測試通過（權限檢查 <100ms）
- [ ] 安全性檢查（SQL Injection, XSS, CSRF）
- [ ] CORS 設定正確
- [ ] 環境變數已設定（production）
- [ ] SSL 憑證已安裝且有效
- [ ] 資料庫備份機制已設定
- [ ] 審計記錄功能正常運作
- [ ] 繁體中文翻譯完整
- [ ] Docker 映像檔安全掃描通過

## 參考資源

- [CodeIgniter 4 文件](https://codeigniter.com/user_guide/)
- [Nuxt 3 文件](https://nuxt.com/)
- [Vue 3 文件](https://vuejs.org/)
- [OpenAPI 規格](./contracts/)
- [資料模型設計](./data-model.md)
- [技術研究文件](./research.md)

## 取得協助

如遇到問題：

1. 檢查此 quickstart.md 文件
2. 參考 API contracts (OpenAPI specs)
3. 查看 research.md 瞭解技術決策
4. 聯絡團隊成員

Happy coding! 🚀
