#!/bin/bash

# 測試 Roles API（需要認證）
# 使用方式: ./test-roles-with-auth.sh

API_BASE="http://localhost:9230/api/v1"

echo "=========================================="
echo "測試 CRM Roles API（含認證）"
echo "=========================================="
echo ""

# 1. 先登入獲取 token
echo "步驟 1: 登入獲取 token..."
echo "---"

LOGIN_RESPONSE=$(curl -s -X POST "${API_BASE}/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123"
  }')

echo "登入回應:"
echo "$LOGIN_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$LOGIN_RESPONSE"
echo ""

# 提取 token - 使用 Python 的 json 模組更穩健
TOKEN=$(echo "$LOGIN_RESPONSE" | python3 -c "import sys, json; data = json.load(sys.stdin); print(data.get('data', {}).get('access_token', ''))" 2>/dev/null || echo "")

if [ -z "$TOKEN" ]; then
  echo "❌ 無法獲取 token，請檢查登入資訊"
  echo "   預設帳號: admin"
  echo "   預設密碼: admin123"
  echo ""
  echo "可能需要先運行 seeders 建立測試資料："
  echo "   docker compose exec backend php run-seeders.php"
  exit 1
fi

echo "✅ 成功獲取 token: ${TOKEN:0:20}..."
echo ""

# 2. 使用 token 獲取 roles
echo "步驟 2: 使用 token 獲取 roles..."
echo "---"

ROLES_RESPONSE=$(curl -s -X GET "${API_BASE}/roles?page=1&per_page=20&sort=created_at&order=desc" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN")

echo "Roles 回應:"
echo "$ROLES_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$ROLES_RESPONSE"
echo ""

# 檢查是否成功 - 檢查是否有 data 欄位（移除格式化後的空白）
if echo "$ROLES_RESPONSE" | tr -d ' \n' | grep -q '"data":\['; then
  echo "✅ 成功獲取 roles 列表"
  
  # 使用 Python 計算角色數量更準確
  ROLES_COUNT=$(echo "$ROLES_RESPONSE" | python3 -c "import sys, json; data = json.load(sys.stdin); print(len(data.get('data', [])))" 2>/dev/null || echo "0")
  echo "📊 總共有 $ROLES_COUNT 個角色"
else
  echo "❌ 獲取 roles 失敗"
fi

echo ""
echo "=========================================="
echo "測試完成"
echo "=========================================="
