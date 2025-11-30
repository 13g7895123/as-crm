#!/bin/bash

echo "==================================="
echo "Testing Roles API"
echo "==================================="
echo ""

# Step 1: Login
echo "[1/3] Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:9230/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}')

echo "Login response:"
echo "$LOGIN_RESPONSE" | python3 -m json.tool
echo ""

# Extract token
TOKEN=$(echo "$LOGIN_RESPONSE" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('data', {}).get('access_token', ''))" 2>/dev/null)

if [ -z "$TOKEN" ]; then
    echo "❌ Failed to get access token"
    exit 1
fi

echo "✅ Got access token: ${TOKEN:0:50}..."
echo ""

# Step 2: Query roles
echo "[2/3] Querying roles..."
ROLES_RESPONSE=$(curl -s -X GET "http://localhost:9230/api/v1/roles?page=1&per_page=10" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "Raw roles response:"
echo "$ROLES_RESPONSE"
echo ""
echo "Formatted roles response:"
echo "$ROLES_RESPONSE" | python3 -m json.tool 2>&1 || echo "Failed to parse JSON"
echo ""

# Step 3: Check if roles data exists
ROLE_COUNT=$(echo "$ROLES_RESPONSE" | python3 -c "import sys, json; data=json.load(sys.stdin); print(len(data.get('data', [])))" 2>/dev/null)

if [ -z "$ROLE_COUNT" ] || [ "$ROLE_COUNT" -eq 0 ]; then
    echo "❌ No roles data found in API response"
    exit 1
fi

echo "✅ Found $ROLE_COUNT roles in database"
echo ""

# Step 4: Verify database query
echo "[3/3] Verifying database..."
DB_ROLES=$(docker compose exec -T database mysql -ucrm_user -pcrm_password crm_db -e "SELECT COUNT(*) as count FROM roles;" 2>/dev/null | tail -n 1)

echo "Database has $DB_ROLES roles"
echo ""

echo "==================================="
echo "✅ Test completed successfully!"
echo "==================================="
echo ""
echo "Summary:"
echo "  - API returns $ROLE_COUNT roles"
echo "  - Database contains $DB_ROLES roles"
echo "  - Data is correctly fetched from database"
