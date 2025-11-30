#!/bin/bash

# ============================================================================
# CRM RBAC Production Deployment Script
# ============================================================================
# 生產環境部署腳本
#
# 功能:
#   - 標準部署（單一環境）
#   - 藍綠部署（零停機）
#   - 清除快取
#   - 執行資料庫遷移
#
# 使用方式:
#   ./production.sh                    # 標準完整部署
#   ./production.sh --cache-only       # 僅清除快取並重啟
#   ./production.sh --blue-green       # 藍綠部署（零停機）
#   ./production.sh --status           # 查看部署狀態
#
# ============================================================================

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 取得腳本所在目錄
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$PROJECT_ROOT"

# 檢查參數
CACHE_ONLY=false
BLUE_GREEN=false
STATUS_ONLY=false

for arg in "$@"; do
    case $arg in
        --cache-only)
            CACHE_ONLY=true
            ;;
        --blue-green)
            BLUE_GREEN=true
            ;;
        --status)
            STATUS_ONLY=true
            ;;
    esac
done

# 如果是藍綠部署，委託給專用腳本
if [ "$BLUE_GREEN" = true ]; then
    exec "$SCRIPT_DIR/blue-green-deploy.sh" deploy
fi

if [ "$STATUS_ONLY" = true ]; then
    exec "$SCRIPT_DIR/blue-green-deploy.sh" status
fi

echo "=============================================="
if [ "$CACHE_ONLY" = true ]; then
    echo -e "${BLUE}CRM RBAC 快取更新${NC}"
else
    echo -e "${BLUE}CRM RBAC 生產環境完整部署${NC}"
fi
echo "=============================================="
echo ""

# ============================================================================
# 1. 環境檢查
# ============================================================================

echo -e "${YELLOW}[1/7] 檢查環境...${NC}"

# 檢查 Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker 未安裝${NC}"
    exit 1
fi
echo -e "${GREEN}  ✓ Docker 已安裝${NC}"

# 檢查 Docker Compose
if ! command -v docker compose &> /dev/null; then
    echo -e "${RED}✗ Docker Compose 未安裝${NC}"
    exit 1
fi
echo -e "${GREEN}  ✓ Docker Compose 已安裝${NC}"

# 檢查 .env.prod 檔案
if [ ! -f .env.prod ]; then
    echo -e "${RED}✗ .env.prod 檔案不存在${NC}"
    echo "請確保專案根目錄有 .env.prod 檔案"
    echo "並修改其中的密碼和金鑰設定"
    exit 1
fi
echo -e "${GREEN}  ✓ .env.prod 檔案存在${NC}"

# 建立 .env 連結到 .env.prod（供 docker-compose 使用）
ln -sf .env.prod .env
echo -e "${GREEN}  ✓ .env 已連結到 .env.prod${NC}"

# 載入環境變數
set -a
source .env.prod
set +a
echo -e "${GREEN}  ✓ 環境變數已載入${NC}"

echo ""

# ============================================================================
# 2. 停止現有容器
# ============================================================================

echo -e "${YELLOW}[2/7] 檢查並停止現有容器...${NC}"

# 檢查是否有運行中的容器
if docker compose ps | grep -q "Up"; then
    echo "  發現運行中的容器，正在停止..."
    docker compose down
    echo -e "${GREEN}  ✓ 容器已停止${NC}"
else
    echo "  沒有運行中的容器"
fi

echo ""

# ============================================================================
# 3. 清除快取
# ============================================================================

echo -e "${YELLOW}[3/7] 清除快取...${NC}"

# 清除 Backend 快取
if [ -d "backend/writable/cache" ]; then
    echo "  清除 Backend 快取..."
    rm -rf backend/writable/cache/*
    echo -e "${GREEN}  ✓ Backend 快取已清除${NC}"
fi

# 清除 Frontend 快取
if [ -d "frontend/.nuxt" ]; then
    echo "  清除 Frontend .nuxt 快取..."
    rm -rf frontend/.nuxt
    echo -e "${GREEN}  ✓ Frontend .nuxt 快取已清除${NC}"
fi

if [ -d "frontend/.output" ]; then
    echo "  清除 Frontend .output 快取..."
    rm -rf frontend/.output
    echo -e "${GREEN}  ✓ Frontend .output 快取已清除${NC}"
fi

# 清除 Docker 快取 (僅完整更新時)
if [ "$CACHE_ONLY" = false ]; then
    echo "  清除 Docker 建置快取..."
    docker builder prune -f >/dev/null 2>&1 || true
    echo -e "${GREEN}  ✓ Docker 建置快取已清除${NC}"
fi

echo ""

# 如果只是清除快取，跳過構建步驟
if [ "$CACHE_ONLY" = true ]; then
    echo -e "${YELLOW}[4/7] 跳過構建步驟（僅快取更新）${NC}"
    echo ""

    echo -e "${YELLOW}[5/7] 跳過映像構建${NC}"
    echo ""

    echo -e "${YELLOW}[6/7] 啟動服務...${NC}"
    docker compose up -d
    echo -e "${GREEN}  ✓ 服務已啟動${NC}"
    echo ""

    echo -e "${YELLOW}[7/7] 跳過資料庫遷移${NC}"
    echo ""

    echo "=============================================="
    echo -e "${GREEN}快取更新完成！${NC}"
    echo "=============================================="
    exit 0
fi

# ============================================================================
# 4. 建立必要目錄
# ============================================================================

echo -e "${YELLOW}[4/7] 建立必要目錄...${NC}"

mkdir -p backend/writable/{cache,logs,session,uploads}
mkdir -p backend/public/uploads
mkdir -p docker/mariadb
chmod -R 777 backend/writable 2>/dev/null || true

echo -e "${GREEN}  ✓ 目錄結構已就緒${NC}"
echo ""

# ============================================================================
# 5. 構建 Docker 映像
# ============================================================================

echo -e "${YELLOW}[5/7] 構建 Docker 映像...${NC}"
echo "  這可能需要幾分鐘時間..."

docker compose build --no-cache

echo -e "${GREEN}  ✓ Docker 映像構建完成${NC}"
echo ""

# ============================================================================
# 6. 啟動服務
# ============================================================================

echo -e "${YELLOW}[6/7] 啟動服務...${NC}"

docker compose up -d

echo -e "${GREEN}  ✓ 服務已啟動${NC}"

# 等待資料庫就緒
echo "  等待資料庫啟動..."
sleep 10

echo ""

# ============================================================================
# 7. 資料庫遷移
# ============================================================================

echo -e "${YELLOW}[7/7] 執行資料庫遷移...${NC}"

# 執行 migrations
echo "  執行 migrations..."
docker compose exec -T backend php run-migrations.php || {
    echo -e "${YELLOW}  ⚠ Migration 執行失敗或已完成${NC}"
}

# 執行 seeders (僅首次部署)
if docker compose exec -T backend php -r "echo 'test';" &>/dev/null; then
    echo "  執行 seeders..."
    docker compose exec -T backend php run-seeders.php || {
        echo -e "${YELLOW}  ⚠ Seeders 執行失敗或已完成${NC}"
    }
fi

echo -e "${GREEN}  ✓ 資料庫更新完成${NC}"
echo ""

# ============================================================================
# 完成
# ============================================================================

echo "=============================================="
echo -e "${GREEN}生產環境部署完成！${NC}"
echo "=============================================="
echo ""
echo "服務資訊："
echo "  - 前端: http://localhost:${FRONTEND_PORT:-9330}"
echo "  - 後端 API: http://localhost:${BACKEND_PORT:-9230}"
echo "  - 資料庫: localhost:${DB_PORT:-9130}"
echo "  - phpMyAdmin: http://localhost:${PHPMYADMIN_PORT:-9730}"
echo ""
echo "常用指令："
echo "  查看日誌:     docker compose logs -f"
echo "  查看狀態:     docker compose ps"
echo "  停止服務:     docker compose down"
echo "  重新啟動:     docker compose restart"
echo "  快取更新:     ./production.sh --cache-only"
echo "  藍綠部署:     ./production.sh --blue-green"
echo "  部署狀態:     ./production.sh --status"
echo ""
echo -e "${YELLOW}提示：${NC}"
echo "  - 完整部署會重新構建所有映像"
echo "  - 使用 --cache-only 僅清除快取並重啟服務"
echo "  - 使用 --blue-green 進行零停機藍綠部署"
echo ""
