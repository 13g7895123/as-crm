#!/bin/bash

# ============================================================================
# CRM RBAC Development Environment Script
# ============================================================================
# 開發環境啟動腳本
#
# 功能:
#   - 檢查並停止現有容器
#   - 僅啟動後端服務 (資料庫 + 後端 API + phpMyAdmin)
#   - 執行資料庫遷移和 seeders
#   - 前端請手動執行: cd frontend && npm run dev
#
# 使用方式:
#   ./develop.sh
#
# ============================================================================

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "=============================================="
echo -e "${BLUE}CRM RBAC 開發環境啟動${NC}"
echo "=============================================="
echo ""

# ============================================================================
# 1. 環境檢查
# ============================================================================

echo -e "${YELLOW}[1/5] 檢查環境...${NC}"

# 檢查 Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker 未安裝${NC}"
    echo "請先安裝 Docker: https://docs.docker.com/get-docker/"
    exit 1
fi
echo -e "${GREEN}  ✓ Docker 已安裝${NC}"

# 檢查 Docker Compose
if ! command -v docker compose &> /dev/null; then
    echo -e "${RED}✗ Docker Compose 未安裝${NC}"
    echo "請先安裝 Docker Compose: https://docs.docker.com/compose/install/"
    exit 1
fi
echo -e "${GREEN}  ✓ Docker Compose 已安裝${NC}"

# 檢查 .env.dev 檔案
if [ ! -f .env.dev ]; then
    echo -e "${RED}✗ .env.dev 檔案不存在${NC}"
    echo "請確保專案根目錄有 .env.dev 檔案"
    exit 1
fi
echo -e "${GREEN}  ✓ .env.dev 檔案存在${NC}"

# 建立 .env 連結到 .env.dev（供 docker-compose 使用）
ln -sf .env.dev .env
echo -e "${GREEN}  ✓ .env 已連結到 .env.dev${NC}"

# 載入環境變數
set -a
source .env.dev
set +a
echo -e "${GREEN}  ✓ 環境變數已載入${NC}"

echo ""

# ============================================================================
# 2. 檢查並停止現有容器
# ============================================================================

echo -e "${YELLOW}[2/5] 檢查並停止現有容器...${NC}"

# 檢查後端相關容器是否運行
BACKEND_RUNNING=$(docker compose ps -q backend database phpmyadmin 2>/dev/null | wc -l)

if [ "$BACKEND_RUNNING" -gt 0 ]; then
    echo "  發現運行中的後端容器，正在停止..."
    docker compose stop backend database phpmyadmin 2>/dev/null || true
    echo -e "${GREEN}  ✓ 容器已停止${NC}"
else
    echo "  沒有運行中的後端容器"
fi

echo ""

# ============================================================================
# 3. 建立必要目錄並設定權限
# ============================================================================

echo -e "${YELLOW}[3/5] 建立必要目錄...${NC}"

# 建立目錄
mkdir -p backend/writable/{cache,logs,session,uploads}
mkdir -p backend/public/uploads
mkdir -p docker/mariadb
echo -e "${GREEN}  ✓ 目錄結構已就緒${NC}"

# 清理舊的日誌檔案
if [ -d backend/writable/logs ]; then
    rm -f backend/writable/logs/log-*.log 2>/dev/null || {
        echo -e "${YELLOW}  ⚠ 無法清理部分日誌檔案${NC}"
    }
fi

# 設定權限
chmod -R 777 backend/writable 2>/dev/null || {
    echo -e "${YELLOW}  ⚠ 部分權限設定失敗（將由容器處理）${NC}"
}
chmod 755 backend/app/Config/Boot 2>/dev/null || true
echo -e "${GREEN}  ✓ 權限設定完成${NC}"

echo ""

# ============================================================================
# 4. 啟動後端服務
# ============================================================================

echo -e "${YELLOW}[4/5] 啟動後端服務...${NC}"
echo "  正在啟動: 資料庫 + 後端 API + phpMyAdmin"

docker compose up -d --build database backend phpmyadmin

echo -e "${GREEN}  ✓ 後端服務已啟動${NC}"

# 等待資料庫就緒
echo "  等待資料庫啟動..."
sleep 10

echo ""

# ============================================================================
# 5. 資料庫初始化
# ============================================================================

echo -e "${YELLOW}[5/5] 執行資料庫初始化...${NC}"

# 執行 migrations
echo "  執行 migrations..."
docker compose exec -T backend php run-migrations.php 2>/dev/null || {
    echo -e "${YELLOW}  ⚠ Migration 執行失敗或已完成${NC}"
}

# 執行 seeders
echo "  執行 seeders..."
docker compose exec -T backend php run-seeders.php 2>/dev/null || {
    echo -e "${YELLOW}  ⚠ Seeders 執行失敗或已完成${NC}"
}

echo -e "${GREEN}  ✓ 資料庫初始化完成${NC}"
echo ""

# ============================================================================
# 完成
# ============================================================================

echo "=============================================="
echo -e "${GREEN}開發環境啟動完成！${NC}"
echo "=============================================="
echo ""
echo "服務資訊："
echo "  - 後端 API:    http://localhost:${BACKEND_PORT:-9230}"
echo "  - 資料庫:      localhost:${DB_PORT:-9130}"
echo "  - phpMyAdmin:  http://localhost:${PHPMYADMIN_PORT:-9730}"
echo "                 帳號: ${DB_USER:-crm_user}"
echo "                 密碼: ${DB_PASSWORD:-crm_password}"
echo ""
echo -e "${BLUE}前端服務：${NC}"
echo "  前端未啟動。請在新的終端執行："
echo -e "  ${GREEN}cd frontend && npm run dev${NC}"
echo "  前端將運行在: http://localhost:3001 (或其他可用 port)"
echo ""
echo "常用指令："
echo "  查看後端日誌:    docker compose logs -f backend"
echo "  查看資料庫日誌:  docker compose logs -f database"
echo "  停止後端服務:    docker compose stop backend database phpmyadmin"
echo "  重新啟動後端:    docker compose restart backend"
echo "  進入後端容器:    docker compose exec backend bash"
echo "  執行 migration:  docker compose exec backend php run-migrations.php"
echo "  執行 seeders:    docker compose exec backend php run-seeders.php"
echo ""
echo -e "${YELLOW}開發提示：${NC}"
echo "  - 後端使用 PHP 內建伺服器，支援熱重載"
echo "  - 前端使用 Nuxt dev server，支援 HMR"
echo "  - 資料庫資料持久化在 docker/mariadb/data"
echo ""
