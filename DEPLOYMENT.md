# 部署指南 (Deployment Guide)

本專案提供兩個主要的部署腳本，分別用於開發環境和生產環境。

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
- ✅ 自動檢查並停止現有容器
- ✅ 執行資料庫 migrations 和 seeders
- ✅ 支援熱重載（backend + frontend HMR）
- ✅ 適合開發階段快速迭代

---

### 2. `production.sh` - 生產環境

**用途：** 生產環境部署，完整構建所有服務

**啟動的服務：**
- ✅ MariaDB 資料庫
- ✅ Backend API (PHP)
- ✅ Frontend (Nuxt SSR)
- ✅ phpMyAdmin

**使用方式：**

#### 完整部署（重新構建所有映像）
```bash
./production.sh
```

#### 僅更新快取（不重新構建）
```bash
./production.sh --cache-only
```

**功能說明：**

**完整部署 (`./production.sh`)：**
1. 停止現有容器
2. 清除所有快取
   - Backend writable/cache
   - Frontend .nuxt 和 .output
   - Docker 建置快取
3. 重新構建 Docker 映像（--no-cache）
4. 啟動所有服務
5. 執行資料庫 migrations 和 seeders

**僅快取更新 (`./production.sh --cache-only`)：**
1. 停止現有容器
2. 清除應用快取
   - Backend writable/cache
   - Frontend .nuxt 和 .output
3. 重啟服務（不重新構建）

**服務資訊：**
- Frontend: http://localhost:9330
- Backend API: http://localhost:9230
- 資料庫: localhost:9130
- phpMyAdmin: http://localhost:9730

**特點：**
- ✅ 自動檢查並停止現有容器
- ✅ 完整的快取清理機制
- ✅ 支援兩種更新模式（完整/快取）
- ✅ 執行資料庫遷移
- ✅ 適合生產環境部署

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
as.crm/
├── production.sh        # 生產環境部署腳本
├── develop.sh          # 開發環境啟動腳本
├── docker-compose.yml  # Docker Compose 配置
├── .env               # 環境變數配置
├── backend/
│   ├── writable/      # 可寫目錄（日誌、快取等）
│   └── ...
├── frontend/
│   ├── .nuxt/        # Nuxt 開發快取
│   ├── .output/      # Nuxt 建置輸出
│   └── ...
└── scripts/          # 輔助腳本
    ├── run-migrations.sh
    ├── backup-database.sh
    ├── health-check.sh
    └── ...
```

---

## 🔧 環境變數

主要環境變數在 `.env` 檔案中設定：

```bash
# 資料庫配置
DB_HOST=database
DB_PORT=9130
DB_NAME=crm_db
DB_USER=crm_user
DB_PASSWORD=crm_password
DB_ROOT_PASSWORD=root_password

# 服務 Port
BACKEND_PORT=9230
FRONTEND_PORT=9330
PHPMYADMIN_PORT=9730

# JWT 配置
JWT_SECRET_KEY=your_secret_key_here
JWT_TIME_TO_LIVE=3600
JWT_REFRESH_TIME_TO_LIVE=604800

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:9330

# Nuxt
NUXT_PUBLIC_API_BASE_URL=http://localhost:9230/api/v1
NUXT_PUBLIC_APP_NAME=CRM 權限管理系統
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

### 問題：快取未清除

```bash
# 手動清除所有快取
rm -rf backend/writable/cache/*
rm -rf frontend/.nuxt
rm -rf frontend/.output

# 使用生產腳本快取模式
./production.sh --cache-only
```

### 問題：權限錯誤

```bash
# 設定 backend writable 目錄權限
chmod -R 777 backend/writable

# 重新執行腳本
./develop.sh  # 或 ./production.sh
```

---

## 📚 相關文檔

- [README.md](README.md) - 專案總覽
- [CLAUDE.md](CLAUDE.md) - 開發指南
- [docs/](docs/) - 詳細文檔

---

## ⚠️ 注意事項

### 開發環境
- 前端必須手動啟動（`npm run dev`）
- 資料會持久化在 Docker volumes
- 適合快速開發和測試

### 生產環境
- 使用 `--no-cache` 構建確保最新版本
- 建議定期執行完整部署
- 快取模式僅用於小更新
- 記得定期備份資料庫

---

**最後更新：** 2025-10-31
