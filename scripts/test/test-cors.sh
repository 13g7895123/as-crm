#!/bin/bash

###############################################################################
# CORS 自動化測試腳本
#
# 用途：測試後端 CORS 設定是否正確運作
# 使用方式：./scripts/test-cors.sh [backend_url] [frontend_origins...]
#
# 範例：
#   ./scripts/test-cors.sh http://localhost:9230 http://localhost:3003 http://localhost:3000
###############################################################################

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 預設值
BACKEND_URL="${1:-http://localhost:9230}"
ORIGINS=("${@:2}")

# 如果沒有提供 origins，使用預設值
if [ ${#ORIGINS[@]} -eq 0 ]; then
    ORIGINS=(
        "http://localhost:3003"
        "http://127.0.0.1:3003"
        "http://localhost:3000"
        "http://127.0.0.1:3000"
    )
fi

echo -e "${BLUE}======================================"
echo "CORS 自動化測試腳本"
echo "=====================================${NC}"
echo ""
echo "後端 URL: $BACKEND_URL"
echo "測試的 Origins:"
for origin in "${ORIGINS[@]}"; do
    echo "  - $origin"
done
echo ""

# 測試計數器
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

###############################################################################
# 測試函數
###############################################################################

# 測試 CORS 健康檢查
test_cors_health() {
    echo -e "${BLUE}[測試] CORS 健康檢查${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))

    RESPONSE=$(curl -s "$BACKEND_URL/api/v1/cors/health")

    # 檢查回應中是否包含 "healthy"
    if echo "$RESPONSE" | grep -q '"status":"healthy"' || echo "$RESPONSE" | grep -q '"status": "healthy"'; then
        echo -e "${GREEN}✓ PASS${NC} - CORS 設定健康"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} - CORS 設定有問題"
        echo "回應: $RESPONSE"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

# 測試 OPTIONS preflight 請求
test_preflight() {
    local origin=$1
    echo -e "${BLUE}[測試] OPTIONS Preflight - $origin${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))

    # 發送 OPTIONS 請求
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
        -X OPTIONS \
        -H "Origin: $origin" \
        -H "Access-Control-Request-Method: GET" \
        -H "Access-Control-Request-Headers: Content-Type, Authorization" \
        "$BACKEND_URL/api/v1/roles")

    HEADERS=$(curl -s -I \
        -X OPTIONS \
        -H "Origin: $origin" \
        -H "Access-Control-Request-Method: GET" \
        "$BACKEND_URL/api/v1/roles")

    # 檢查狀態碼
    if [ "$HTTP_CODE" == "204" ]; then
        # 檢查 CORS 標頭
        if echo "$HEADERS" | grep -q "Access-Control-Allow-Origin: $origin"; then
            echo -e "${GREEN}✓ PASS${NC} - Preflight 請求成功，CORS 標頭正確"
            PASSED_TESTS=$((PASSED_TESTS + 1))
            return 0
        else
            echo -e "${RED}✗ FAIL${NC} - Preflight 成功但缺少正確的 CORS 標頭"
            echo "標頭:"
            echo "$HEADERS" | grep -i "access-control"
            FAILED_TESTS=$((FAILED_TESTS + 1))
            return 1
        fi
    else
        echo -e "${RED}✗ FAIL${NC} - Preflight 請求失敗 (HTTP $HTTP_CODE)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

# 測試實際 API 請求
test_actual_request() {
    local origin=$1
    echo -e "${BLUE}[測試] 實際 GET 請求 - $origin${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))

    # 發送實際請求並檢查 CORS 標頭 (使用 -i 來包含標頭，不使用 -I HEAD 請求)
    RESPONSE=$(curl -s -i \
        -H "Origin: $origin" \
        "$BACKEND_URL/api/v1/cors/debug")

    if echo "$RESPONSE" | grep -q "Access-Control-Allow-Origin: $origin"; then
        echo -e "${GREEN}✓ PASS${NC} - 實際請求包含正確的 CORS 標頭"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} - 實際請求缺少 CORS 標頭"
        echo "標頭:"
        echo "$RESPONSE" | head -20 | grep -i "access-control"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

# 測試除錯端點
test_debug_endpoint() {
    local origin=$1
    echo -e "${BLUE}[測試] CORS 除錯端點 - $origin${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))

    RESPONSE=$(curl -s \
        -H "Origin: $origin" \
        "$BACKEND_URL/api/v1/cors/debug")

    # 檢查回應中是否包含 "is_origin_allowed":true
    if echo "$RESPONSE" | grep -q '"is_origin_allowed":true' || echo "$RESPONSE" | grep -q '"is_origin_allowed": true'; then
        echo -e "${GREEN}✓ PASS${NC} - Origin 被允許"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} - Origin 未被允許"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

###############################################################################
# 執行測試
###############################################################################

echo -e "${YELLOW}開始執行測試...${NC}"
echo ""

# 1. 測試健康檢查
test_cors_health
echo ""

# 2. 對每個 origin 執行測試
for origin in "${ORIGINS[@]}"; do
    echo -e "${YELLOW}--- 測試 Origin: $origin ---${NC}"
    test_preflight "$origin"
    test_actual_request "$origin"
    test_debug_endpoint "$origin"
    echo ""
done

###############################################################################
# 顯示測試結果摘要
###############################################################################

echo -e "${BLUE}======================================"
echo "測試結果摘要"
echo "=====================================${NC}"
echo -e "總測試數: ${BLUE}$TOTAL_TESTS${NC}"
echo -e "通過: ${GREEN}$PASSED_TESTS${NC}"
echo -e "失敗: ${RED}$FAILED_TESTS${NC}"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✓ 所有測試通過！CORS 設定正常運作。${NC}"
    exit 0
else
    echo -e "${RED}✗ 有 $FAILED_TESTS 個測試失敗。請檢查 CORS 設定。${NC}"
    echo ""
    echo "建議檢查項目："
    echo "1. backend/app/Config/Cors.php 中的 allowedOrigins"
    echo "2. backend/app/Config/Filters.php 中的 corsFilter 設定"
    echo "3. 後端容器是否已重啟：docker compose restart backend"
    echo "4. 執行除錯端點查看詳細資訊："
    echo "   curl -H 'Origin: http://localhost:3003' $BACKEND_URL/api/v1/cors/debug | jq"
    exit 1
fi
