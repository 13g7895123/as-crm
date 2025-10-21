#!/bin/bash

# Production Environment Build Script for CRM RBAC System
# Usage: ./build.sh

set -e

echo "=========================================="
echo "CRM RBAC 生產環境建置腳本"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}錯誤: Docker 未安裝${NC}"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker compose &> /dev/null; then
    echo -e "${RED}錯誤: Docker Compose 未安裝${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker 與 Docker Compose 已就緒${NC}"

# Check for .env.prod file
if [ ! -f .env.prod ]; then
    echo -e "${RED}錯誤: .env.prod 檔案不存在${NC}"
    echo ""
    echo "請建立 .env.prod 檔案，包含以下環境變數："
    echo ""
    cat <<EOF
DB_ROOT_PASSWORD=your_secure_root_password
DB_NAME=crm_db
DB_USER=crm_user
DB_PASSWORD=your_secure_password
JWT_SECRET_KEY=your_256_bit_secret_key
JWT_TIME_TO_LIVE=3600
JWT_REFRESH_TIME_TO_LIVE=604800
CORS_ALLOWED_ORIGINS=https://your-domain.com
API_BASE_URL=https://your-domain.com/api/v1
APP_NAME=CRM 系統
EOF
    echo ""
    exit 1
fi

echo -e "${GREEN}✓ .env.prod 檔案已就緒${NC}"

# Load environment variables
export $(cat .env.prod | xargs)

# Create necessary directories
echo ""
echo "建立必要目錄..."
mkdir -p backend/writable/{cache,logs,session,uploads}
mkdir -p backend/public/uploads
mkdir -p docker/mariadb
mkdir -p docker/nginx/conf.d
mkdir -p docker/nginx/ssl

echo -e "${GREEN}✓ 目錄建立完成${NC}"

# Create production backend .env
echo ""
echo "建立 Backend .env 檔案..."
cat > backend/.env <<EOF
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = database
database.default.database = ${DB_NAME}
database.default.username = ${DB_USER}
database.default.password = ${DB_PASSWORD}
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306

#--------------------------------------------------------------------
# JWT Authentication
#--------------------------------------------------------------------
JWT_SECRET_KEY = ${JWT_SECRET_KEY}
JWT_TIME_TO_LIVE = ${JWT_TIME_TO_LIVE}
JWT_REFRESH_TIME_TO_LIVE = ${JWT_REFRESH_TIME_TO_LIVE}

#--------------------------------------------------------------------
# CORS
#--------------------------------------------------------------------
CORS_ALLOWED_ORIGINS = ${CORS_ALLOWED_ORIGINS}
EOF
echo -e "${GREEN}✓ Backend .env 建立完成${NC}"

# Create production frontend .env
echo ""
echo "建立 Frontend .env 檔案..."
cat > frontend/.env <<EOF
NUXT_PUBLIC_API_BASE_URL=${API_BASE_URL}
NUXT_PUBLIC_APP_NAME=${APP_NAME}
EOF
echo -e "${GREEN}✓ Frontend .env 建立完成${NC}"

# Create MariaDB production init script
if [ ! -f docker/mariadb/prod-init.sql ]; then
    echo ""
    echo "建立 MariaDB 生產環境初始化腳本..."
    cat > docker/mariadb/prod-init.sql <<EOF
-- MariaDB 生產環境初始化腳本
ALTER DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 設定效能優化參數
SET GLOBAL query_cache_size = 67108864;
SET GLOBAL query_cache_type = 1;
SET GLOBAL innodb_buffer_pool_size = 268435456;

GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
EOF
    echo -e "${GREEN}✓ MariaDB 初始化腳本建立完成${NC}"
fi

# Create production Dockerfiles
if [ ! -f backend/Dockerfile ]; then
    echo ""
    echo "建立 Backend Dockerfile..."
    cat > backend/Dockerfile <<EOF
FROM php:8.1-fpm

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
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl opcache

# Install APCu for caching
RUN pecl install apcu && docker-php-ext-enable apcu

# Configure OPcache for production
RUN { \\
    echo 'opcache.memory_consumption=128'; \\
    echo 'opcache.interned_strings_buffer=8'; \\
    echo 'opcache.max_accelerated_files=4000'; \\
    echo 'opcache.revalidate_freq=2'; \\
    echo 'opcache.fast_shutdown=1'; \\
    echo 'opcache.enable_cli=1'; \\
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install production dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Set proper permissions
RUN chown -R www-data:www-data writable public/uploads

EXPOSE 9000

CMD ["php-fpm"]
EOF
    echo -e "${GREEN}✓ Backend Dockerfile 建立完成${NC}"
fi

if [ ! -f frontend/Dockerfile ]; then
    echo ""
    echo "建立 Frontend Dockerfile..."
    cat > frontend/Dockerfile <<EOF
# Build stage
FROM node:18-alpine AS builder

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .
RUN npm run build

# Production stage
FROM node:18-alpine

WORKDIR /app

COPY --from=builder /app/.output ./.output
COPY --from=builder /app/package*.json ./

ENV NODE_ENV=production

EXPOSE 3000

CMD ["node", ".output/server/index.mjs"]
EOF
    echo -e "${GREEN}✓ Frontend Dockerfile 建立完成${NC}"
fi

# Create Nginx configuration
if [ ! -f docker/nginx/conf.d/default.conf ]; then
    echo ""
    echo "建立 Nginx 設定檔..."
    mkdir -p docker/nginx/conf.d
    cat > docker/nginx/conf.d/default.conf <<EOF
upstream backend_upstream {
    server backend:9000;
}

upstream frontend_upstream {
    server frontend:3000;
}

server {
    listen 80;
    server_name _;

    # Redirect HTTP to HTTPS
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name _;

    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;

    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Backend API
    location /api/ {
        fastcgi_pass backend_upstream;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/html/public/index.php;
        include fastcgi_params;

        # CORS headers
        add_header 'Access-Control-Allow-Origin' '${CORS_ALLOWED_ORIGINS}' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'Authorization, Content-Type' always;
    }

    # Static files
    location /uploads/ {
        alias /var/www/html/public/uploads/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Frontend (Nuxt)
    location / {
        proxy_pass http://frontend_upstream;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}
EOF
    echo -e "${GREEN}✓ Nginx 設定檔建立完成${NC}"
fi

# Create self-signed SSL certificate (for development/testing)
if [ ! -f docker/nginx/ssl/cert.pem ]; then
    echo ""
    echo -e "${YELLOW}警告: SSL 憑證不存在，建立自簽憑證（僅供測試用途）${NC}"
    mkdir -p docker/nginx/ssl
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout docker/nginx/ssl/key.pem \
        -out docker/nginx/ssl/cert.pem \
        -subj "/C=TW/ST=Taiwan/L=Taipei/O=CRM/CN=localhost" \
        2>/dev/null || echo -e "${RED}OpenSSL 未安裝，請手動建立 SSL 憑證${NC}"
fi

# Build Docker images
echo ""
echo "建置 Docker 映像檔..."
docker compose -f docker-compose.prod.yml build --no-cache

# Stop existing containers
echo ""
echo "停止現有容器（如果有的話）..."
docker compose -f docker-compose.prod.yml down -v

# Start containers
echo ""
echo "啟動生產環境容器..."
docker compose -f docker-compose.prod.yml up -d

# Wait for database
echo ""
echo "等待資料庫啟動..."
sleep 15

# Run migrations
echo ""
echo "執行資料庫 migrations..."
docker compose -f docker-compose.prod.yml exec backend php spark migrate

echo ""
echo "執行資料庫 seeders..."
docker compose -f docker-compose.prod.yml exec backend php spark db:seed RoleSeeder || true
docker compose -f docker-compose.prod.yml exec backend php spark db:seed PermissionSeeder || true

echo ""
echo "=========================================="
echo -e "${GREEN}生產環境建置完成！${NC}"
echo "=========================================="
echo ""
echo "服務資訊："
echo "  - HTTPS: https://your-domain.com"
echo "  - HTTP (redirect): http://your-domain.com"
echo ""
echo "常用指令："
echo "  查看日誌: docker compose -f docker-compose.prod.yml logs -f"
echo "  停止服務: docker compose -f docker-compose.prod.yml down"
echo "  重新啟動: docker compose -f docker-compose.prod.yml restart"
echo ""
echo -e "${YELLOW}重要提示：${NC}"
echo "  - 請將自簽 SSL 憑證替換為正式憑證（Let's Encrypt）"
echo "  - 請確認防火牆已開放 80 和 443 port"
echo "  - 請定期備份資料庫"
echo ""
