# CRM RBAC 專案架構與部署指南

> 完整版技術文件 | 最後更新：2025-11-30

---

## 目錄

1. [專案概述](#1-專案概述)
2. [專案架構](#2-專案架構)
3. [環境配置](#3-環境配置)
4. [開發環境](#4-開發環境)
5. [生產環境部署](#5-生產環境部署)
6. [藍綠部署](#6-藍綠部署)
7. [腳本參考](#7-腳本參考)
8. [故障排除](#8-故障排除)
9. [安全注意事項](#9-安全注意事項)

---

## 1. 專案概述

### 1.1 系統簡介

CRM RBAC Permission Management System 是一套完整的角色權限管理系統，具備：

- **角色管理**：階層式角色結構、角色繼承
- **權限管理**：模組化權限、細粒度控制
- **用戶角色指派**：時效性指派、批次操作
- **稽核日誌**：完整的操作追蹤

### 1.2 技術棧

| 層級 | 技術 | 版本 |
|------|------|------|
| **後端** | CodeIgniter 4 + PHP | 4.4.8 / 8.1+ |
| **前端** | Nuxt 3 + Vue 3 + TypeScript | 3.x |
| **資料庫** | MariaDB | 10.6+ |
| **容器化** | Docker + Docker Compose | 最新版 |
| **反向代理** | Nginx | Alpine |

---

## 2. 專案架構

### 2.1 目錄結構

```
crm/
│
├── 📄 根目錄配置檔案
│   ├── README.md                    # 專案說明
│   ├── CLAUDE.md                    # AI 開發指引
│   ├── .env.example                 # 環境變數範本
│   ├── .env.dev                     # 開發環境配置 ✅ Git
│   ├── .env.prod                    # 生產環境配置 ❌ Git
│   ├── .env                         # 當前環境 (symlink)
│   ├── develop.sh                   # 開發腳本 (symlink)
│   ├── production.sh                # 生產腳本 (symlink)
│   ├── docker-compose.yml           # 標準 Docker 配置
│   ├── docker-compose.prod.yml      # 生產環境配置
│   └── docker-compose.blue-green.yml # 藍綠部署配置
│
├── 📁 docs/                         # 📖 文檔中心
│   ├── api/                         # API 文檔
│   │   ├── openapi.yaml            # OpenAPI 規格
│   │   └── API_DOCUMENTATION.md    # API 說明
│   ├── database/                    # 資料庫文檔
│   │   └── database-indexing-strategy.md
│   ├── development/                 # 開發文檔
│   │   ├── DEPLOYMENT.md           # 部署指南
│   │   ├── TESTING.md              # 測試指南
│   │   └── PROBLEM_DIAGNOSIS.md    # 問題診斷
│   └── troubleshooting/             # 疑難排解
│       ├── CORS-TROUBLESHOOTING.md
│       └── swagger-*.md
│
├── 📁 scripts/                      # 🔧 腳本中心
│   ├── dev/                         # 開發腳本
│   │   └── develop.sh              # 啟動開發環境
│   ├── deploy/                      # 部署腳本
│   │   ├── production.sh           # 生產環境部署
│   │   ├── blue-green-deploy.sh    # 藍綠部署
│   │   ├── generate-ssl-cert.sh    # SSL 憑證生成
│   │   └── run-migrations.sh       # 資料庫遷移
│   ├── db/                          # 資料庫腳本
│   │   └── backup-database.sh      # 資料庫備份
│   └── test/                        # 測試腳本
│       ├── health-check.sh         # 健康檢查
│       ├── test-cors.sh
│       └── test-*.sh
│
├── 📁 docker/                       # 🐳 Docker 配置
│   ├── nginx/
│   │   ├── nginx.conf              # Nginx 主配置
│   │   ├── default.conf            # 預設站點配置
│   │   ├── conf.d/
│   │   │   └── blue-green.conf     # 藍綠部署配置
│   │   └── ssl/                    # SSL 憑證目錄
│   │       ├── cert.pem            # ❌ Git
│   │       └── key.pem             # ❌ Git
│   ├── mariadb/
│   └── mysql-init/
│
├── 📁 backend/                      # ⚙️ 後端 (CodeIgniter 4)
│   ├── app/
│   │   ├── Config/                 # 配置檔
│   │   ├── Controllers/            # API 控制器
│   │   ├── Models/                 # 資料模型
│   │   ├── Services/               # 業務邏輯
│   │   ├── Filters/                # 中介層
│   │   ├── Helpers/                # 輔助函數
│   │   └── Database/
│   │       ├── Migrations/         # 資料庫遷移
│   │       └── Seeds/              # 資料填充
│   ├── tests/                      # PHPUnit 測試
│   ├── public/                     # 公開目錄
│   ├── writable/                   # 可寫目錄
│   │   ├── cache/
│   │   ├── logs/
│   │   └── session/
│   ├── Dockerfile                  # 生產用
│   ├── Dockerfile.dev              # 開發用
│   └── composer.json
│
├── 📁 frontend/                     # 🎨 前端 (Nuxt 3)
│   ├── pages/                      # 頁面路由
│   │   ├── index.vue
│   │   ├── login.vue
│   │   ├── roles/
│   │   ├── permissions/
│   │   └── audit-logs/
│   ├── components/                 # 可重用組件
│   │   ├── layout/
│   │   ├── roles/
│   │   └── ...
│   ├── composables/                # 組合函數
│   ├── stores/                     # Pinia 狀態管理
│   ├── middleware/                 # 路由中介層
│   ├── assets/                     # 靜態資源
│   ├── tests/                      # 測試
│   ├── Dockerfile
│   ├── Dockerfile.dev
│   ├── nuxt.config.ts
│   └── package.json
│
├── 📁 specs/                        # 📋 功能規格
│   └── 001-rbac-permission-management/
│       ├── spec.md
│       ├── plan.md
│       ├── tasks.md
│       └── ...
│
└── 📁 .github/                      # GitHub 配置
    └── workflows/                  # CI/CD 工作流程
```

### 2.2 架構圖

```
┌─────────────────────────────────────────────────────────────────┐
│                         用戶瀏覽器                               │
└─────────────────────────────┬───────────────────────────────────┘
                              │ HTTPS (443)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Nginx 反向代理                              │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐         │
│  │   /api/*    │    │     /*      │    │  /uploads/* │         │
│  │  → Backend  │    │ → Frontend  │    │   靜態檔案   │         │
│  └─────────────┘    └─────────────┘    └─────────────┘         │
└─────────────────────────────┬───────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│    Backend (PHP-FPM)    │     │   Frontend (Node.js)    │
│    CodeIgniter 4        │     │      Nuxt 3 SSR         │
│    Port: 9000           │     │      Port: 3000         │
└────────────┬────────────┘     └─────────────────────────┘
             │
             ▼
┌─────────────────────────┐
│      MariaDB 10.6       │
│      Port: 3306         │
└─────────────────────────┘
```

---

## 3. 環境配置

### 3.1 環境配置檔案對照表

| 檔案 | 用途 | Git 追蹤 | 說明 |
|------|------|---------|------|
| `.env.example` | 配置範本 | ✅ | 所有設定項目的參考 |
| `.env.dev` | 開發環境 | ✅ | 開發用預設值 |
| `.env.prod` | 生產環境 | ❌ | **必須手動建立並修改** |
| `.env` | 當前環境 | ❌ | 由腳本自動建立 symlink |

### 3.2 開發環境配置 (.env.dev)

```bash
# ============================================
# PORT 配置 - 使用 9xxx 系列避免衝突
# ============================================
DB_PORT=9130           # MariaDB
BACKEND_PORT=9230      # Backend API
FRONTEND_PORT=9330     # Frontend (Docker)
FRONTEND_HMR_PORT=24678 # HMR 熱重載
PHPMYADMIN_PORT=9730   # phpMyAdmin

# ============================================
# 資料庫配置
# ============================================
DB_HOST=database
DB_NAME=crm_db
DB_USER=crm_user
DB_PASSWORD=crm_password
DB_ROOT_PASSWORD=root_password

# ============================================
# 後端配置
# ============================================
CI_ENVIRONMENT=development
JWT_SECRET_KEY=dev-secret-key-change-in-production-12345678901234567890123456789012
JWT_TIME_TO_LIVE=3600        # Token 有效期 (秒)
JWT_REFRESH_TIME_TO_LIVE=604800  # Refresh Token 有效期

# ============================================
# 前端配置
# ============================================
NUXT_PUBLIC_API_BASE_URL=http://localhost:9230/api/v1
NUXT_PUBLIC_APP_NAME="CRM 權限管理系統 (開發)"

# ============================================
# CORS 配置
# ============================================
CORS_ALLOWED_ORIGINS=http://localhost:9330,http://localhost:3001
```

### 3.3 生產環境配置 (.env.prod)

```bash
# ============================================
# ⚠️ 生產環境配置 - 請務必修改所有預設值！
# ============================================

# ============================================
# 部署配置
# ============================================
DEPLOY_COLOR=blue      # 藍綠部署：blue 或 green

# ============================================
# PORT 配置 - 標準 Port
# ============================================
NGINX_HTTP_PORT=80
NGINX_HTTPS_PORT=443
DB_PORT=3306
BACKEND_PORT=9000      # 內部 Port
FRONTEND_PORT=3000     # 內部 Port

# ============================================
# 資料庫配置 - ⚠️ 必須修改！
# ============================================
DB_HOST=database
DB_NAME=crm_db
DB_USER=crm_user
DB_PASSWORD=CHANGE_ME_STRONG_PASSWORD_HERE      # ⚠️ 修改
DB_ROOT_PASSWORD=CHANGE_ME_ROOT_PASSWORD_HERE   # ⚠️ 修改

# ============================================
# 後端配置 - ⚠️ 必須修改！
# ============================================
CI_ENVIRONMENT=production
# 使用以下指令生成：openssl rand -base64 32
JWT_SECRET_KEY=CHANGE_ME_USE_OPENSSL_RAND_BASE64_32  # ⚠️ 修改
JWT_TIME_TO_LIVE=3600
JWT_REFRESH_TIME_TO_LIVE=604800

# ============================================
# 前端配置 - ⚠️ 必須修改！
# ============================================
API_BASE_URL=https://your-domain.com/api/v1     # ⚠️ 修改
APP_NAME=CRM 權限管理系統

# ============================================
# CORS 配置 - ⚠️ 必須修改！
# ============================================
CORS_ALLOWED_ORIGINS=https://your-domain.com    # ⚠️ 修改

# ============================================
# SSL 配置
# ============================================
SSL_CERT_FILE=cert.pem
SSL_KEY_FILE=key.pem

# ============================================
# 日誌等級
# ============================================
LOG_LEVEL=warning
```

### 3.4 生產環境設定檢查清單

部署前請確認以下項目：

- [ ] 修改 `DB_PASSWORD`（使用強密碼）
- [ ] 修改 `DB_ROOT_PASSWORD`（使用強密碼）
- [ ] 修改 `JWT_SECRET_KEY`（執行 `openssl rand -base64 32`）
- [ ] 設定正確的 `API_BASE_URL`
- [ ] 設定正確的 `CORS_ALLOWED_ORIGINS`
- [ ] 放置 SSL 憑證到 `docker/nginx/ssl/`
- [ ] 確認防火牆規則

---

## 4. 開發環境

### 4.1 啟動開發環境

```bash
# 方式一：使用根目錄 symlink
./develop.sh

# 方式二：直接執行腳本
./scripts/dev/develop.sh
```

### 4.2 開發環境服務

| 服務 | URL | 說明 |
|------|-----|------|
| Backend API | http://localhost:9230 | CodeIgniter 4 |
| phpMyAdmin | http://localhost:9730 | 資料庫管理 |
| MariaDB | localhost:9130 | 資料庫 |

### 4.3 前端開發

前端需要在另一個終端機手動啟動：

```bash
cd frontend
npm install  # 首次執行
npm run dev
```

前端開發伺服器：http://localhost:3001

### 4.4 開發常用指令

```bash
# 查看後端日誌
docker compose logs -f backend

# 重啟後端
docker compose restart backend

# 進入後端容器
docker compose exec backend bash

# 執行資料庫遷移
docker compose exec backend php run-migrations.php

# 執行 Seeders
docker compose exec backend php run-seeders.php

# 停止所有服務
docker compose down
```

### 4.5 預設帳號

| 用途 | 帳號 | 密碼 |
|------|------|------|
| 系統管理員 | admin | admin123 |
| phpMyAdmin | crm_user | crm_password |

---

## 5. 生產環境部署

### 5.1 部署選項

| 指令 | 說明 | 停機時間 |
|------|------|---------|
| `./production.sh` | 標準完整部署 | 有停機 |
| `./production.sh --cache-only` | 僅清除快取 | 短暫停機 |
| `./production.sh --blue-green` | 藍綠部署 | **零停機** |
| `./production.sh --status` | 查看部署狀態 | - |

### 5.2 首次部署流程

```bash
# 1. 建立生產環境配置
cp .env.example .env.prod

# 2. 編輯配置（⚠️ 必須修改所有預設值）
vim .env.prod

# 3. 生成 SSL 憑證（測試用自簽憑證）
./scripts/deploy/generate-ssl-cert.sh your-domain.com

# 4. 執行部署
./production.sh
```

### 5.3 標準部署流程

```bash
# 完整部署（重建所有映像）
./production.sh

# 部署流程：
# 1. ✅ 環境檢查
# 2. ✅ 停止現有容器
# 3. ✅ 清除快取
# 4. ✅ 建立必要目錄
# 5. ✅ 構建 Docker 映像
# 6. ✅ 啟動服務
# 7. ✅ 執行資料庫遷移
```

### 5.4 快取更新

適用於小型更新（如程式碼修正）：

```bash
./production.sh --cache-only

# 僅執行：
# 1. 清除後端快取
# 2. 清除前端快取
# 3. 重啟服務
```

---

## 6. 藍綠部署

### 6.1 概念說明

藍綠部署是一種零停機部署策略，透過維護兩個獨立的環境（藍色和綠色）來實現：

```
                        ┌─────────────┐
                        │   Nginx     │
                        │  流量入口    │
                        └──────┬──────┘
                               │
                  ┌────────────┴────────────┐
                  ▼                         ▼
         ┌────────────────┐       ┌────────────────┐
         │  🔵 藍色環境   │       │  🟢 綠色環境   │
         │  (當前活躍)    │       │  (備用/新版)   │
         │                │       │                │
         │ backend-blue   │       │ backend-green  │
         │ frontend-blue  │       │ frontend-green │
         └────────────────┘       └────────────────┘
                  │                         │
                  └────────────┬────────────┘
                               ▼
                        ┌─────────────┐
                        │  Database   │
                        │   (共用)    │
                        └─────────────┘
```

### 6.2 部署流程

```
當前狀態：藍色環境活躍
   │
   ▼
┌─────────────────────────────┐
│ 1. 構建新版本到綠色環境      │
│    docker build backend-green│
│    docker build frontend-green│
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│ 2. 啟動綠色環境              │
│    docker compose up green   │
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│ 3. 健康檢查                  │
│    檢查 backend-green 健康   │
│    檢查 frontend-green 健康  │
└──────────────┬──────────────┘
               │ 通過
               ▼
┌─────────────────────────────┐
│ 4. 執行資料庫遷移            │
│    php run-migrations.php    │
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│ 5. 切換流量                  │
│    修改 nginx 指向 green     │
│    nginx reload              │
└──────────────┬──────────────┘
               │
               ▼
   綠色環境活躍，藍色環境待命
   （可快速回滾）
```

### 6.3 使用方式

```bash
# 執行藍綠部署
./production.sh --blue-green
# 或
./scripts/deploy/blue-green-deploy.sh deploy

# 查看當前狀態
./scripts/deploy/blue-green-deploy.sh status

# 手動切換環境（不重新部署）
./scripts/deploy/blue-green-deploy.sh switch

# 回滾到上一個環境
./scripts/deploy/blue-green-deploy.sh rollback

# 健康檢查
./scripts/deploy/blue-green-deploy.sh health

# 清理非活躍環境（釋放資源）
./scripts/deploy/blue-green-deploy.sh cleanup
```

### 6.4 指令說明

| 指令 | 說明 |
|------|------|
| `deploy` | 部署到非活躍環境，健康檢查通過後切換流量 |
| `status` | 顯示當前活躍環境、備用環境、容器狀態 |
| `switch` | 手動切換流量到另一個環境（需該環境已運行） |
| `rollback` | 回滾到上一個環境 |
| `health` | 執行健康檢查 |
| `cleanup` | 停止並移除非活躍環境的容器 |

### 6.5 Nginx 配置切換

流量切換透過修改 Nginx 配置中的 `$active_color` 變數：

```nginx
# docker/nginx/conf.d/blue-green.conf

# 藍綠部署切換點
# 修改此變數以切換活躍環境
set $active_color blue;  # 或 green
```

切換後執行 `nginx -s reload` 即時生效。

### 6.6 注意事項

1. **資料庫共用**：藍綠環境共用同一個資料庫，遷移需注意向後相容
2. **API 相容性**：確保新舊版本的 API 在切換期間相容
3. **回滾限制**：回滾不會回滾資料庫變更
4. **資源消耗**：同時運行兩個環境會消耗更多資源

---

## 7. 腳本參考

### 7.1 腳本一覽表

| 腳本 | 路徑 | 說明 |
|------|------|------|
| develop.sh | scripts/dev/ | 啟動開發環境 |
| production.sh | scripts/deploy/ | 生產環境部署 |
| blue-green-deploy.sh | scripts/deploy/ | 藍綠部署 |
| generate-ssl-cert.sh | scripts/deploy/ | SSL 憑證生成 |
| run-migrations.sh | scripts/deploy/ | 資料庫遷移 |
| backup-database.sh | scripts/db/ | 資料庫備份 |
| health-check.sh | scripts/test/ | 健康檢查 |

### 7.2 develop.sh

```bash
./develop.sh

# 功能：
# - 使用 .env.dev 配置
# - 啟動 database, backend, phpmyadmin
# - 執行資料庫遷移和 seeders
# - 不啟動前端（需手動 npm run dev）
```

### 7.3 production.sh

```bash
# 標準部署
./production.sh

# 僅清除快取
./production.sh --cache-only

# 藍綠部署
./production.sh --blue-green

# 查看狀態
./production.sh --status
```

### 7.4 blue-green-deploy.sh

```bash
./scripts/deploy/blue-green-deploy.sh <command>

# Commands:
#   deploy    - 部署並切換
#   status    - 查看狀態
#   switch    - 手動切換
#   rollback  - 回滾
#   health    - 健康檢查
#   cleanup   - 清理非活躍環境
```

### 7.5 generate-ssl-cert.sh

```bash
# 使用預設域名 (localhost)
./scripts/deploy/generate-ssl-cert.sh

# 指定域名
./scripts/deploy/generate-ssl-cert.sh your-domain.com

# 輸出：
# - docker/nginx/ssl/cert.pem
# - docker/nginx/ssl/key.pem
```

### 7.6 backup-database.sh

```bash
./scripts/db/backup-database.sh [environment]

# 範例：
./scripts/db/backup-database.sh production
./scripts/db/backup-database.sh development
```

---

## 8. 故障排除

### 8.1 Port 已被佔用

```bash
# 查看佔用程序
lsof -i :9230
lsof -i :9330

# 解決方案
docker compose down
# 或修改 .env 中的 port 設定
```

### 8.2 資料庫連接失敗

```bash
# 檢查容器狀態
docker compose ps database

# 查看日誌
docker compose logs database

# 重啟資料庫
docker compose restart database

# 檢查連接
docker compose exec database mysql -u crm_user -p
```

### 8.3 後端 500 錯誤

```bash
# 查看後端日誌
docker compose logs -f backend

# 查看 CodeIgniter 日誌
docker compose exec backend cat writable/logs/log-$(date +%Y-%m-%d).log

# 檢查權限
docker compose exec backend ls -la writable/
```

### 8.4 前端無法連接 API

```bash
# 檢查 CORS 設定
grep CORS .env

# 檢查 API URL
grep API_BASE_URL .env

# 測試 API
curl http://localhost:9230/api/v1/health
```

### 8.5 藍綠部署失敗

```bash
# 檢查目標環境健康
./scripts/deploy/blue-green-deploy.sh health

# 查看 Nginx 配置
cat docker/nginx/conf.d/blue-green.conf | grep active_color

# 查看容器狀態
docker compose -f docker-compose.blue-green.yml ps

# 回滾
./scripts/deploy/blue-green-deploy.sh rollback
```

### 8.6 SSL 憑證錯誤

```bash
# 重新生成自簽憑證
./scripts/deploy/generate-ssl-cert.sh

# 檢查憑證內容
openssl x509 -in docker/nginx/ssl/cert.pem -text -noout

# 檢查憑證到期日
openssl x509 -in docker/nginx/ssl/cert.pem -noout -dates
```

---

## 9. 安全注意事項

### 9.1 生產環境必做

| 項目 | 說明 |
|------|------|
| 修改資料庫密碼 | 使用強密碼，至少 16 字元 |
| 修改 JWT 金鑰 | 使用 `openssl rand -base64 32` 生成 |
| 使用正式 SSL 憑證 | 推薦 Let's Encrypt |
| 設定防火牆 | 僅開放 80/443 port |
| 定期備份資料庫 | 設定 cron job |
| 監控日誌 | 設定日誌輪替和監控 |

### 9.2 密碼生成指令

```bash
# 生成強密碼（資料庫用）
openssl rand -base64 24

# 生成 JWT 金鑰
openssl rand -base64 32

# 生成隨機字串
cat /dev/urandom | tr -dc 'a-zA-Z0-9' | head -c 32
```

### 9.3 SSL 憑證

**開發/測試環境**：使用自簽憑證
```bash
./scripts/deploy/generate-ssl-cert.sh
```

**生產環境**：使用 Let's Encrypt
```bash
# 安裝 certbot
apt install certbot

# 取得憑證
certbot certonly --standalone -d your-domain.com

# 複製憑證
cp /etc/letsencrypt/live/your-domain.com/fullchain.pem docker/nginx/ssl/cert.pem
cp /etc/letsencrypt/live/your-domain.com/privkey.pem docker/nginx/ssl/key.pem
```

### 9.4 不應提交到 Git 的檔案

以下檔案已加入 `.gitignore`：

- `.env`
- `.env.prod`
- `.env.*.local`
- `docker/nginx/ssl/*.pem`
- `docker/nginx/ssl/*.key`
- `docker/nginx/ssl/*.crt`
- `backend/writable/logs/*`
- `*.sql` (備份檔案)

---

## 附錄 A：Docker Compose 服務對照

### 開發環境 (docker-compose.yml)

| 服務 | 容器名稱 | Port | 說明 |
|------|---------|------|------|
| database | crm_database | 9130:3306 | MariaDB |
| backend | crm_backend | 9230:8080 | PHP 開發伺服器 |
| phpmyadmin | crm_phpmyadmin | 9730:80 | 資料庫管理 |

### 藍綠部署 (docker-compose.blue-green.yml)

| 服務 | 容器名稱 | 說明 |
|------|---------|------|
| database | crm_db_prod | 共用資料庫 |
| backend-blue | crm_backend_blue | 藍色後端 |
| backend-green | crm_backend_green | 綠色後端 |
| frontend-blue | crm_frontend_blue | 藍色前端 |
| frontend-green | crm_frontend_green | 綠色前端 |
| nginx | crm_nginx_prod | 反向代理 |

---

## 附錄 B：快速參考卡

```
┌─────────────────────────────────────────────────────────────┐
│                    CRM RBAC 快速參考                         │
├─────────────────────────────────────────────────────────────┤
│ 開發環境                                                     │
│   啟動：./develop.sh                                        │
│   前端：cd frontend && npm run dev                          │
│   API：http://localhost:9230                                │
│   DB：http://localhost:9730 (phpMyAdmin)                    │
├─────────────────────────────────────────────────────────────┤
│ 生產環境                                                     │
│   標準部署：./production.sh                                  │
│   快取更新：./production.sh --cache-only                    │
│   藍綠部署：./production.sh --blue-green                    │
│   查看狀態：./production.sh --status                        │
├─────────────────────────────────────────────────────────────┤
│ 藍綠部署                                                     │
│   部署：./scripts/deploy/blue-green-deploy.sh deploy        │
│   回滾：./scripts/deploy/blue-green-deploy.sh rollback      │
│   切換：./scripts/deploy/blue-green-deploy.sh switch        │
├─────────────────────────────────────────────────────────────┤
│ 常用指令                                                     │
│   日誌：docker compose logs -f [service]                    │
│   狀態：docker compose ps                                   │
│   重啟：docker compose restart [service]                    │
│   停止：docker compose down                                 │
│   遷移：docker compose exec backend php run-migrations.php  │
├─────────────────────────────────────────────────────────────┤
│ 預設帳號                                                     │
│   系統：admin / admin123                                    │
│   DB：crm_user / crm_password                               │
└─────────────────────────────────────────────────────────────┘
```

---

**文件版本**：1.0  
**最後更新**：2025-11-30  
**維護者**：CRM RBAC Team
