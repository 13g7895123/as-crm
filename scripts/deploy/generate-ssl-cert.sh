#!/bin/bash

# ============================================================================
# SSL 自簽憑證生成腳本
# ============================================================================
# 用於開發/測試環境的自簽 SSL 憑證
# 生產環境請使用正式憑證（如 Let's Encrypt）
#
# 使用方式：
#   ./generate-ssl-cert.sh [domain]
#
# 範例：
#   ./generate-ssl-cert.sh                    # 使用 localhost
#   ./generate-ssl-cert.sh crm.example.com   # 使用自訂域名
# ============================================================================

set -e

# 顏色定義
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 配置
DOMAIN="${1:-localhost}"
SSL_DIR="$(dirname "$0")/../../docker/nginx/ssl"
DAYS=365

# 建立目錄
mkdir -p "$SSL_DIR"

echo ""
echo "=============================================="
echo "生成 SSL 自簽憑證"
echo "=============================================="
echo ""
echo "域名: $DOMAIN"
echo "有效期: $DAYS 天"
echo "輸出目錄: $SSL_DIR"
echo ""

# 生成私鑰和憑證
openssl req -x509 -nodes -days $DAYS -newkey rsa:2048 \
    -keyout "$SSL_DIR/key.pem" \
    -out "$SSL_DIR/cert.pem" \
    -subj "/CN=$DOMAIN/O=CRM RBAC/C=TW" \
    -addext "subjectAltName=DNS:$DOMAIN,DNS:localhost,IP:127.0.0.1"

echo -e "${GREEN}✓ SSL 憑證已生成${NC}"
echo ""
echo "檔案："
echo "  - 私鑰: $SSL_DIR/key.pem"
echo "  - 憑證: $SSL_DIR/cert.pem"
echo ""
echo -e "${YELLOW}注意：這是自簽憑證，僅供開發/測試使用${NC}"
echo -e "${YELLOW}生產環境請使用正式憑證（如 Let's Encrypt）${NC}"
echo ""
