#!/bin/bash

# Backend Development Environment Build Script for CRM RBAC System
# Usage: ./build-backend-dev.sh
#
# This script starts ONLY the database and backend services.
# Use this when you're working on backend code and don't need the frontend container.

set -e

echo "=========================================="
echo "CRM RBAC 後端開發環境建置腳本"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}錯誤: Docker 未安裝${NC}"
    echo "請先安裝 Docker: https://docs.docker.com/get-docker/"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker compose &> /dev/null; then
    echo -e "${RED}錯誤: Docker Compose 未安裝${NC}"
    echo "請先安裝 Docker Compose: https://docs.docker.com/compose/install/"
    exit 1
fi

echo -e "${GREEN}✓ Docker 與 Docker Compose 已就緒${NC}"

# Check if .env file exists
if [ ! -f .env ]; then
    echo -e "${RED}錯誤: .env 檔案不存在${NC}"
    echo "請先執行 ./build-dev.sh 建立完整環境，或手動建立 .env 檔案"
    exit 1
fi

# Load environment variables
source .env

echo -e "${GREEN}✓ 環境變數已載入${NC}"

# Stop existing containers to release file locks
echo ""
echo "檢查並停止現有容器..."
docker compose stop backend database 2>/dev/null || true

# Create necessary directories
echo ""
echo "建立必要目錄..."
mkdir -p backend/writable/{cache,logs,session,uploads}
mkdir -p backend/public/uploads
mkdir -p docker/mariadb

# Clean up old log files owned by root (if any)
echo "清理舊的日誌檔案..."
if [ -w backend/writable/logs ]; then
    # Try to remove old logs owned by root using sudo if needed
    if ! rm -f backend/writable/logs/log-*.log 2>/dev/null; then
        echo -e "${YELLOW}警告: 無法刪除部分日誌檔案（可能需要 sudo 權限）${NC}"
        echo "嘗試使用 sudo 清理..."
        sudo rm -f backend/writable/logs/log-*.log 2>/dev/null || echo -e "${YELLOW}跳過日誌清理${NC}"
    fi
fi

# Set permissions (this will work now that old files are removed)
echo "設定目錄權限..."
chmod -R 777 backend/writable 2>/dev/null || {
    echo -e "${YELLOW}警告: 部分權限設定失敗（將由容器內部處理）${NC}"
}

echo -e "${GREEN}✓ 目錄建立完成${NC}"

# Create MariaDB init script if not exists
if [ ! -f docker/mariadb/init.sql ]; then
    echo ""
    echo "建立 MariaDB 初始化腳本..."
    cat > docker/mariadb/init.sql <<EOF
-- MariaDB 開發環境初始化腳本
ALTER DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 授予使用者完整權限
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
EOF
    echo -e "${GREEN}✓ MariaDB 初始化腳本建立完成${NC}"
fi

# Create Backend Dockerfile.dev if not exists
if [ ! -f backend/Dockerfile.dev ]; then
    echo ""
    echo "建立 Backend Dockerfile.dev..."
    cat > backend/Dockerfile.dev <<EOF
FROM php:8.1-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \\
    git \\
    curl \\
    libpng-dev \\
    libonig-dev \\
    libxml2-dev \\
    zip \\
    unzip \\
    libicu-dev \\
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl

# Install APCu for caching
RUN pecl install apcu && docker-php-ext-enable apcu

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set permissions
RUN chmod -R 777 writable

EXPOSE 8080

CMD ["php", "spark", "serve", "--host=0.0.0.0", "--port=8080"]
EOF
    echo -e "${GREEN}✓ Backend Dockerfile.dev 建立完成${NC}"
fi

# Build and start backend services (including phpMyAdmin for database management)
echo ""
echo "建置與啟動後端服務（資料庫 + 後端 API + phpMyAdmin）..."
docker compose up -d --build database backend phpmyadmin

# Wait for database to be ready
echo ""
echo "等待資料庫啟動..."
sleep 10

# Run migrations using run-migrations.php (bypasses spark CLI issues)
echo ""
echo "執行資料庫 migrations..."
docker compose exec -T backend php run-migrations.php || echo -e "${YELLOW}Migration 失敗或尚未建立${NC}"

# Run seeders using run-seeders.php
echo ""
echo "執行資料庫 seeders..."
docker compose exec -T backend php run-seeders.php || echo -e "${YELLOW}Seeders 失敗或尚未建立${NC}"

echo ""
echo "=========================================="
echo -e "${GREEN}後端開發環境建置完成！${NC}"
echo "=========================================="
echo ""
echo "服務資訊："
echo "  - Backend API: http://localhost:${BACKEND_PORT}"
echo "  - Database: localhost:${DB_PORT}"
echo "  - phpMyAdmin: http://localhost:${PHPMYADMIN_PORT}"
echo "    (帳號: ${DB_USER} / 密碼: ${DB_PASSWORD})"
echo ""
echo "前端服務未啟動。如需啟動前端："
echo "  ./build-frontend-dev.sh"
echo ""
echo "常用指令："
echo "  查看日誌: docker compose logs -f backend"
echo "  停止服務: docker compose stop backend database phpmyadmin"
echo "  重新啟動後端: docker compose restart backend"
echo "  進入 backend 容器: docker compose exec backend bash"
echo "  執行 migrations: docker compose exec backend php run-migrations.php"
echo "  執行 seeders: docker compose exec backend php run-seeders.php"
echo ""
