# 部署指南 (Deployment Guide)

本專案提供兩個主要的部署腳本，分別用於開發環境和生產環境，並支援藍綠部署實現零停機更新。

## 📦 環境配置檔案

| 檔案 | 用途 | 提交到 Git |
|------|------|-----------|
| `.env.example` | 配置範本 | ✅ |
| `.env.dev` | 開發環境配置 | ✅ |
| `.env.prod` | 生產環境配置 | ❌ |
| `.env` | 當前使用的配置（symlink） | ❌ |

## 📦 腳本說明

### 1. `develop.sh` - 開發環境

**用途：** 開發階段使用，僅啟動後端服務

**啟動的服務：**
- ✅ MariaDB 資料庫
- ✅ Backend API (PHP)
- ✅ phpMyAdmin (資料庫管理工具)
- ❌ 前端（需手動啟動）

**使用方式：**
```bash
./develop.sh
```

**前端啟動（在新終端）：**
```bash
cd frontend
npm run dev
```

**服務資訊：**
- Backend API: http://localhost:9230
- 資料庫: localhost:9130
- phpMyAdmin: http://localhost:9730
- 前端: http://localhost:3001 (npm run dev)

**特點：**
- ✅ 自動使用 `.env.dev` 配置
- ✅ 自動檢查並停止現有容器
- ✅ 執行資料庫 migrations 和 seeders
- ✅ 支援熱重載（backend + frontend HMR）
- ✅ 適合開發階段快速迭代

---

### 2. `production.sh` - 生產環境

**用途：** 生產環境部署，支援標準部署和藍綠部署

**使用方式：**

#### 標準完整部署
```bash
./production.sh
```

#### 僅更新快取
```bash
./production.sh --cache-only
```

#### 藍綠部署（零停機）
```bash
./production.sh --blue-green
```

#### 查看部署狀態
```bash
./production.sh --status
```

**功能說明：**

| 模式 | 說明 | 停機時間 |
|------|------|---------|
| 標準部署 | 重建所有映像並重啟 | 有停機 |
| 快取更新 | 清除快取並重啟 | 短暫停機 |
| 藍綠部署 | 部署到備用環境後切換 | **零停機** |

---

## 🔵🟢 藍綠部署

藍綠部署允許在不停機的情況下更新系統，透過維護兩個獨立環境實現。

### 概念

```
                    ┌─────────────┐
                    │   Nginx     │
                    │  (流量入口)  │
                    └──────┬──────┘
                           │
              ┌────────────┴────────────┐
              ▼                         ▼
     ┌────────────────┐       ┌────────────────┐
     │  藍色環境 🔵   │       │  綠色環境 🟢   │
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

### 部署流程

1. **部署新版本**到非活躍環境
2. **健康檢查**確認新版本正常
3. **執行遷移**（如需要）
4. **切換流量**到新環境
5. **保留舊環境**用於快速回滾

### 使用方式

```bash
# 部署到非活躍環境並切換
./scripts/deploy/blue-green-deploy.sh deploy

# 查看當前狀態
./scripts/deploy/blue-green-deploy.sh status

# 手動切換環境（不重新部署）
./scripts/deploy/blue-green-deploy.sh switch

# 回滾到上一個環境
./scripts/deploy/blue-green-deploy.sh rollback

# 健康檢查
./scripts/deploy/blue-green-deploy.sh health

# 清理非活躍環境
./scripts/deploy/blue-green-deploy.sh cleanup
```

### Docker Compose 配置

藍綠部署使用 `docker-compose.blue-green.yml`：

```bash
# 僅啟動藍色環境
docker compose -f docker-compose.blue-green.yml --profile blue up -d

# 僅啟動綠色環境
docker compose -f docker-compose.blue-green.yml --profile green up -d

# 啟動所有環境
docker compose -f docker-compose.blue-green.yml --profile all up -d
```

---

## 🔒 生產環境設定

### 1. 建立 `.env.prod`

```bash
cp .env.example .env.prod
```

### 2. 修改安全配置

```bash
# ⚠️ 必須修改以下設定！

# 資料庫密碼
DB_PASSWORD=your_strong_password_here
DB_ROOT_PASSWORD=your_root_password_here

# JWT 金鑰（使用以下指令生成）
# openssl rand -base64 32
JWT_SECRET_KEY=your_random_jwt_key_here

# API URL
API_BASE_URL=https://your-domain.com/api/v1

# CORS
CORS_ALLOWED_ORIGINS=https://your-domain.com
```

### 3. SSL 憑證

```bash
# 生成自簽憑證（僅測試用）
./scripts/deploy/generate-ssl-cert.sh your-domain.com

# 生產環境請使用正式憑證
# 將憑證放置於：
# - docker/nginx/ssl/cert.pem
# - docker/nginx/ssl/key.pem
```

---

## 🔄 常見操作

### 開發環境

```bash
# 啟動開發環境
./develop.sh

# 在新終端啟動前端
cd frontend && npm run dev

# 查看後端日誌
docker compose logs -f backend

# 重啟後端
docker compose restart backend

# 停止後端服務
docker compose stop backend database phpmyadmin

# 進入後端容器
docker compose exec backend bash

# 手動執行 migration
docker compose exec backend php run-migrations.php

# 手動執行 seeders
docker compose exec backend php run-seeders.php
```

### 生產環境

```bash
# 完整部署（首次部署或重大更新）
./production.sh

# 快速更新（只更新程式碼和快取）
./production.sh --cache-only

# 藍綠部署（零停機）
./production.sh --blue-green

# 查看部署狀態
./production.sh --status

# 查看所有服務日誌
docker compose logs -f

# 查看特定服務日誌
docker compose logs -f backend
docker compose logs -f frontend

# 檢查服務狀態
docker compose ps

# 重啟所有服務
docker compose restart

# 停止所有服務
docker compose down

# 完全清理（包含 volumes）
docker compose down -v
```

---

## 🗂️ 目錄結構

```
crm/
├── develop.sh              # 開發環境腳本 (symlink)
├── production.sh           # 生產環境腳本 (symlink)
├── docker-compose.yml      # 標準 Docker Compose
├── docker-compose.prod.yml # 生產環境配置
├── docker-compose.blue-green.yml  # 藍綠部署配置
├── .env.dev                # 開發環境變數
├── .env.prod               # 生產環境變數 (不提交)
│
├── scripts/
│   ├── dev/
│   │   └── develop.sh
│   ├── deploy/
│   │   ├── production.sh
│   │   ├── blue-green-deploy.sh
│   │   ├── generate-ssl-cert.sh
│   │   └── run-migrations.sh
│   ├── db/
│   │   └── backup-database.sh
│   └── test/
│       └── health-check.sh
│
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf
│   │   ├── conf.d/
│   │   │   └── blue-green.conf
│   │   └── ssl/
│   │       ├── cert.pem
│   │       └── key.pem
│   └── ...
│
├── backend/
│   └── writable/           # 可寫目錄
│
└── frontend/
    ├── .nuxt/              # 開發快取
    └── .output/            # 建置輸出
```

---

## 🔧 環境變數

### 開發環境 (`.env.dev`)

```bash
# Port 配置（9xxx 系列）
DB_PORT=9130
BACKEND_PORT=9230
FRONTEND_PORT=9330
PHPMYADMIN_PORT=9730

# 資料庫
DB_HOST=database
DB_NAME=crm_db
DB_USER=crm_user
DB_PASSWORD=crm_password

# 後端
CI_ENVIRONMENT=development
JWT_SECRET_KEY=dev-secret-key-change-in-production

# 前端
NUXT_PUBLIC_API_BASE_URL=http://localhost:9230/api/v1
```

### 生產環境 (`.env.prod`)

```bash
# 部署配置
DEPLOY_COLOR=blue

# Port 配置（標準 port）
NGINX_HTTP_PORT=80
NGINX_HTTPS_PORT=443
DB_PORT=3306

# 資料庫（⚠️ 修改密碼！）
DB_PASSWORD=CHANGE_ME_STRONG_PASSWORD
DB_ROOT_PASSWORD=CHANGE_ME_ROOT_PASSWORD

# 後端
CI_ENVIRONMENT=production
JWT_SECRET_KEY=CHANGE_ME_USE_OPENSSL_RAND_BASE64_32

# 前端
API_BASE_URL=https://your-domain.com/api/v1

# CORS
CORS_ALLOWED_ORIGINS=https://your-domain.com
```

---

## 📝 故障排除

### 問題：Port 已被佔用

```bash
# 查看佔用 port 的程序
lsof -i :9230
lsof -i :9330

# 停止所有 Docker 容器
docker compose down
```

### 問題：資料庫連接失敗

```bash
# 檢查資料庫容器狀態
docker compose ps database

# 查看資料庫日誌
docker compose logs database

# 重啟資料庫
docker compose restart database
```

### 問題：藍綠部署切換失敗

```bash
# 檢查目標環境是否健康
./scripts/deploy/blue-green-deploy.sh health

# 查看 nginx 配置
cat docker/nginx/conf.d/blue-green.conf | grep active_color

# 手動回滾
./scripts/deploy/blue-green-deploy.sh rollback
```

### 問題：SSL 憑證錯誤

```bash
# 重新生成自簽憑證
./scripts/deploy/generate-ssl-cert.sh

# 檢查憑證
openssl x509 -in docker/nginx/ssl/cert.pem -text -noout
```

---

## ⚠️ 注意事項

### 開發環境
- 前端必須手動啟動（`npm run dev`）
- 資料會持久化在 Docker volumes
- 適合快速開發和測試

### 生產環境
- **務必修改** `.env.prod` 中的密碼和金鑰
- 使用正式 SSL 憑證
- 建議使用藍綠部署實現零停機
- 定期備份資料庫
- 保留舊環境用於快速回滾

### 藍綠部署
- 資料庫是共用的，需注意遷移相容性
- 部署前確認新舊版本 API 相容
- 回滾不會回滾資料庫變更

---

**最後更新：** 2025-11-30
