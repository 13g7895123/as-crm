#!/bin/bash

# Frontend Development Environment Build Script for CRM RBAC System
# Usage: ./build-frontend-dev.sh
#
# This script starts ONLY the frontend service.
# Prerequisites: Backend and database must already be running.
# Use this when you're frequently restarting frontend for UI/UX development.

set -e

echo "=========================================="
echo "CRM RBAC 前端開發環境建置腳本"
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
    echo "請先執行 ./build-dev.sh 建立完整環境"
    exit 1
fi

# Load environment variables
source .env

echo -e "${GREEN}✓ 環境變數已載入${NC}"

# Check if backend is running
if ! docker compose ps backend | grep -q "Up"; then
    echo -e "${YELLOW}警告: 後端服務未運行${NC}"
    echo "前端服務依賴後端 API。請先執行："
    echo "  ./build-backend-dev.sh"
    echo ""
    read -p "是否仍要繼續啟動前端？(y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Create necessary directories
echo ""
echo "建立必要目錄..."
mkdir -p frontend/.nuxt

# Set permissions
chmod -R 777 frontend/.nuxt

echo -e "${GREEN}✓ 目錄建立完成${NC}"

# Create Frontend Dockerfile.dev if not exists
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

# Stop frontend if it's already running
echo ""
echo "停止現有前端容器（如果有的話）..."
docker compose stop frontend
docker compose rm -f frontend

# Build and start frontend service only
echo ""
echo "建置與啟動前端服務..."
docker compose up -d --build frontend

# Wait for frontend to start
echo ""
echo "等待前端服務啟動..."
sleep 5

echo ""
echo "=========================================="
echo -e "${GREEN}前端開發環境建置完成！${NC}"
echo "=========================================="
echo ""
echo "服務資訊："
echo "  - Frontend: http://localhost:${FRONTEND_PORT}"
echo "  - Backend API: http://localhost:${BACKEND_PORT}"
echo ""
echo "常用指令："
echo "  查看日誌: docker compose logs -f frontend"
echo "  停止服務: docker compose stop frontend"
echo "  重新啟動: docker compose restart frontend"
echo "  進入容器: docker compose exec frontend sh"
echo ""
echo "提示："
echo "  - 前端支援 HMR (Hot Module Replacement)"
echo "  - 修改程式碼後會自動重新載入"
echo "  - 如需重新安裝依賴，請停止容器後重新執行此腳本"
echo ""
