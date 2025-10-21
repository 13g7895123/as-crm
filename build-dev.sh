#!/bin/bash

# Development Environment Build Script for CRM RBAC System
# Usage: ./build-dev.sh
#
# This script starts ALL development services (database + backend + frontend).
# For selective service startup, use:
#   - ./build-backend-dev.sh (database + backend only)
#   - ./build-frontend-dev.sh (frontend only)

set -e

echo "=========================================="
echo "CRM RBAC 完整開發環境建置腳本"
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

# Create necessary directories
echo ""
echo "建立必要目錄..."
mkdir -p backend/writable/{cache,logs,session,uploads}
mkdir -p backend/public/uploads
mkdir -p frontend/.nuxt
mkdir -p docker/mariadb
mkdir -p docker/nginx/conf.d
mkdir -p docker/nginx/ssl

# Set permissions (ignore errors for files we don't own)
chmod -R 777 backend/writable 2>/dev/null || true
chmod -R 777 frontend/.nuxt 2>/dev/null || true

echo -e "${GREEN}✓ 目錄建立完成${NC}"

# Create .env if not exists
if [ ! -f .env ]; then
    echo ""
    echo "建立 .env 檔案..."
    cat > .env <<'EOF'
# ============================================
# PORT CONFIGURATION (Exported at top)
# ============================================
export DB_PORT=3306
export BACKEND_PORT=8080
export FRONTEND_PORT=3000
export FRONTEND_HMR_PORT=24678
export NGINX_HTTP_PORT=80
export NGINX_HTTPS_PORT=443

# ============================================
# DATABASE CONFIGURATION
# ============================================
DB_HOST=database
DB_NAME=crm_db
DB_USER=crm_user
DB_PASSWORD=crm_password
DB_ROOT_PASSWORD=root_password

# ============================================
# BACKEND CONFIGURATION
# ============================================
CI_ENVIRONMENT=development
JWT_SECRET_KEY=dev-secret-key-change-in-production
JWT_TIME_TO_LIVE=3600
JWT_REFRESH_TIME_TO_LIVE=604800

# ============================================
# FRONTEND CONFIGURATION
# ============================================
NUXT_PUBLIC_API_BASE_URL=http://localhost:${BACKEND_PORT}/api/v1
NUXT_PUBLIC_APP_NAME=CRM 系統

# ============================================
# CORS CONFIGURATION
# ============================================
CORS_ALLOWED_ORIGINS=http://localhost:${FRONTEND_PORT}
EOF
    echo -e "${GREEN}✓ .env 建立完成${NC}"
else
    echo -e "${YELLOW}.env 已存在，跳過${NC}"
fi

# Load environment variables
source .env

# Create MariaDB init script if not exists
mkdir -p docker/mysql-init
INIT_SQL_FILE="docker/mysql-init/init-db.sql"
if [ ! -f "$INIT_SQL_FILE" ]; then
    echo ""
    echo "建立 MariaDB 初始化腳本..."
    cat > "$INIT_SQL_FILE" <<EOF
-- MariaDB 開發環境初始化腳本
ALTER DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 授予使用者完整權限
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
EOF
    echo -e "${GREEN}✓ MariaDB 初始化腳本建立完成${NC}"
fi

# Create Dockerfiles if not exist
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

if [ ! -f frontend/Dockerfile.dev ]; then
    echo ""
    echo "建立 Frontend Dockerfile.dev..."
    cat > frontend/Dockerfile.dev <<EOF
FROM node:18-alpine

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy application files
COPY . .

EXPOSE 3000
EXPOSE 24678

CMD ["npm", "run", "dev"]
EOF
    echo -e "${GREEN}✓ Frontend Dockerfile.dev 建立完成${NC}"
fi

# Stop existing containers
echo ""
echo "停止現有容器（如果有的話）..."
docker compose down

# Build and start containers
echo ""
echo "建置與啟動所有 Docker 容器..."
docker compose up -d --build

# Wait for database to be ready
echo ""
echo "等待資料庫啟動..."
sleep 10

# Run migrations
echo ""
echo "執行資料庫 migrations..."
docker compose exec backend php spark migrate || echo -e "${YELLOW}Migration 失敗或尚未建立${NC}"

echo ""
echo "執行資料庫 seeders..."
docker compose exec backend php spark db:seed RoleSeeder || echo -e "${YELLOW}RoleSeeder 尚未建立，跳過${NC}"
docker compose exec backend php spark db:seed PermissionSeeder || echo -e "${YELLOW}PermissionSeeder 尚未建立，跳過${NC}"

echo ""
echo "=========================================="
echo -e "${GREEN}完整開發環境建置完成！${NC}"
echo "=========================================="
echo ""
echo "服務資訊："
echo "  - Frontend: http://localhost:${FRONTEND_PORT}"
echo "  - Backend API: http://localhost:${BACKEND_PORT}"
echo "  - Database: localhost:${DB_PORT}"
echo ""
echo "開發提示："
echo "  - 所有 port 配置於 .env 檔案"
echo "  - 前端支援 HMR (Hot Module Replacement)"
echo "  - 後端修改需重啟容器"
echo ""
echo "分離式啟動腳本："
echo "  - 僅啟動後端: ./build-backend-dev.sh"
echo "  - 僅啟動前端: ./build-frontend-dev.sh"
echo "  （前端頻繁重啟時可使用分離式腳本）"
echo ""
echo "常用指令："
echo "  查看所有日誌: docker compose logs -f"
echo "  查看特定服務: docker compose logs -f [backend|frontend|database]"
echo "  停止所有服務: docker compose down"
echo "  重新啟動服務: docker compose restart [service]"
echo "  進入 backend: docker compose exec backend bash"
echo "  進入 frontend: docker compose exec frontend sh"
echo ""
