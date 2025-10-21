# CRM 系統 - RBAC 權限管理

一個基於 CodeIgniter 4 和 Nuxt 3 的 CRM 系統，具備完整的角色基礎存取控制（RBAC）權限管理功能。

## 快速開始

### 使用 Docker（推薦）

最快速的方式，無需手動安裝 PHP、Node.js 或 MariaDB。

#### 完整環境啟動

```bash
# 克隆專案
git clone <repository-url>
cd crm

# 切換到功能分支
git checkout 001-rbac-permission-management

# 執行開發環境建置腳本（啟動所有服務）
./build-dev.sh
```

#### 分離式啟動（推薦給前端開發）

當您頻繁修改前端程式碼時，可使用分離式腳本以避免重啟整個環境：

```bash
# 1. 首次啟動後端（資料庫 + API）
./build-backend-dev.sh

# 2. 在另一個終端啟動前端
./build-frontend-dev.sh

# 3. 前端修改後，僅重啟前端
docker compose restart frontend
# 或重新執行
./build-frontend-dev.sh
```

服務將自動啟動於：
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8080
- **Database**: localhost:3306

**Port 配置**: 所有 port 定義於 `.env` 檔案，可根據需求調整

### 手動安裝

請參考 [快速開始指南](./specs/001-rbac-permission-management/quickstart.md) 進行手動安裝。

## 技術堆疊

- **Backend**: PHP 8.1+ with CodeIgniter 4.4+
- **Frontend**: TypeScript 5.0+ with Nuxt 3
- **Database**: MariaDB 10.6+
- **Container**: Docker Compose
- **Testing**: PHPUnit (backend), Vitest + Playwright (frontend)

## 專案結構

```
crm/
├── backend/              # CodeIgniter 4 後端 API
├── frontend/             # Nuxt 3 前端應用
├── specs/                # 功能規格和文件
│   └── 001-rbac-permission-management/
│       ├── spec.md       # 功能規格
│       ├── plan.md       # 實作計畫
│       ├── data-model.md # 資料模型
│       ├── quickstart.md # 開發指南
│       ├── research.md   # 技術研究
│       └── contracts/    # API 合約
├── docker-compose.yml    # 開發環境
├── docker-compose.prod.yml  # 生產環境
├── build-dev.sh          # 開發環境建置腳本
└── build.sh              # 生產環境建置腳本
```

## 核心功能

### RBAC 權限管理系統

- ✅ 多層級角色管理（系統管理員、業務主管、業務人員、客服）
- ✅ 動態建立自訂角色與權限組合
- ✅ 細緻權限控制（檢視/編輯/匯出/指派）
- ✅ 條件式限制（部門、區域、客戶分群）
- ✅ 時間性授權
- ✅ 角色階層與繼承
- ✅ 完整審計記錄與回溯

## 開發指南

### 常用 Docker 指令

```bash
# 查看容器狀態
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

### 執行測試

```bash
# Backend 測試
docker compose exec backend vendor/bin/phpunit

# Frontend 單元測試
docker compose exec frontend npm run test:unit

# Frontend E2E 測試
docker compose exec frontend npm run test:e2e
```

## 文件

- [功能規格](./specs/001-rbac-permission-management/spec.md) - 詳細的功能需求和使用者故事
- [實作計畫](./specs/001-rbac-permission-management/plan.md) - 技術架構和實作策略
- [資料模型](./specs/001-rbac-permission-management/data-model.md) - 完整的資料庫設計
- [開發指南](./specs/001-rbac-permission-management/quickstart.md) - 環境設定和開發流程
- [技術研究](./specs/001-rbac-permission-management/research.md) - 技術選型和最佳實踐
- [API 合約](./specs/001-rbac-permission-management/contracts/) - OpenAPI 規格

## 效能目標

- API endpoints: p95 latency <200ms (read), <500ms (write)
- Page load: TTI <3s on 3G
- 支援 1000 concurrent users
- Permission check: <100ms

## 部署

### 生產環境部署

詳細的生產環境部署指南，請參考 [quickstart.md](./specs/001-rbac-permission-management/quickstart.md#生產環境部署)。

簡要步驟：

1. 準備 `.env.prod` 檔案
2. 準備 SSL 憑證
3. 執行 `./build.sh`
4. 驗證部署

## 授權

Copyright © 2025. All rights reserved.

## 支援

如遇到問題，請參考：
1. [快速開始指南](./specs/001-rbac-permission-management/quickstart.md)
2. [API 合約](./specs/001-rbac-permission-management/contracts/)
3. [技術研究文件](./specs/001-rbac-permission-management/research.md)
