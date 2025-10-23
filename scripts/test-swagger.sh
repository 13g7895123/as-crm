#!/bin/bash

# Swagger API Documentation Test Script
# Tests all Swagger-related endpoints

echo "========================================"
echo "Swagger API Documentation Diagnostic"
echo "========================================"
echo ""

BASE_URL="http://localhost:9230"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test function
test_endpoint() {
    local url=$1
    local description=$2
    local expected_status=$3

    echo -n "Testing: $description... "

    response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>&1)

    if [ "$response" = "$expected_status" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $response)"
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} (Expected HTTP $expected_status, got HTTP $response)"
        return 1
    fi
}

# Test counters
total_tests=0
passed_tests=0

# Test 1: Swagger UI HTML
total_tests=$((total_tests + 1))
if test_endpoint "$BASE_URL/swagger" "Swagger UI HTML page" "200"; then
    passed_tests=$((passed_tests + 1))
fi

# Test 2: OpenAPI Spec (YAML)
total_tests=$((total_tests + 1))
if test_endpoint "$BASE_URL/swagger/spec" "OpenAPI Specification (YAML)" "200"; then
    passed_tests=$((passed_tests + 1))
fi

# Test 3: Check Swagger UI can load the spec
echo ""
echo "Additional Checks:"
echo "------------------"

# Check if spec contains required fields
echo -n "Checking OpenAPI spec validity... "
spec_content=$(curl -s "$BASE_URL/swagger/spec" 2>&1)

if echo "$spec_content" | grep -q "openapi: 3.0.3" && \
   echo "$spec_content" | grep -q "title: CRM RBAC Permission Management API"; then
    echo -e "${GREEN}✓ PASS${NC} (Valid OpenAPI 3.0.3 spec)"
else
    echo -e "${RED}✗ FAIL${NC} (Invalid or incomplete spec)"
fi

# Check Swagger UI HTML contains correct spec URL
echo -n "Checking Swagger UI configuration... "
ui_content=$(curl -s "$BASE_URL/swagger" 2>&1)

if echo "$ui_content" | grep -q 'url: "/swagger/spec"'; then
    echo -e "${GREEN}✓ PASS${NC} (Correct relative URL)"
elif echo "$ui_content" | grep -q 'url: "http://localhost:8080'; then
    echo -e "${RED}✗ FAIL${NC} (Wrong port in URL - using 8080 instead of 9230)"
else
    echo -e "${YELLOW}? UNKNOWN${NC} (Could not verify URL)"
fi

# Check if backend is running
echo -n "Checking backend server... "
if curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/" 2>&1 | grep -q "200\|404"; then
    echo -e "${GREEN}✓ PASS${NC} (Backend is responding)"
else
    echo -e "${RED}✗ FAIL${NC} (Backend not responding on port 9230)"
fi

# Summary
echo ""
echo "========================================"
echo "Summary"
echo "========================================"
echo "Tests passed: $passed_tests/$total_tests"
echo ""

if [ $passed_tests -eq $total_tests ]; then
    echo -e "${GREEN}All tests passed! ✓${NC}"
    echo ""
    echo "Swagger UI should be accessible at:"
    echo "  → $BASE_URL/swagger"
    echo ""
    echo "If you're still having issues viewing it in your browser:"
    echo "  1. Clear your browser cache (Ctrl+Shift+R or Cmd+Shift+R)"
    echo "  2. Check browser console for JavaScript errors (F12)"
    echo "  3. Ensure you're accessing http://localhost:9230/swagger (not 8080)"
    exit 0
else
    echo -e "${RED}Some tests failed!${NC}"
    echo ""
    echo "Troubleshooting steps:"
    echo "  1. Ensure Docker container is running: docker compose ps"
    echo "  2. Check backend logs: docker compose logs backend --tail 50"
    echo "  3. Restart backend: docker compose restart backend"
    exit 1
fi
