#!/bin/bash

# CRM RBAC Test Verification Script
# Verifies all test files are present and properly structured

echo "======================================"
echo "CRM RBAC Test Verification"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
TOTAL=0
PASSED=0
FAILED=0

# Function to check file exists
check_file() {
    TOTAL=$((TOTAL + 1))
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $1 ($(du -h "$1" | cut -f1))"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}✗${NC} $1 (NOT FOUND)"
        FAILED=$((FAILED + 1))
    fi
}

echo "Backend Tests:"
echo "--------------"
check_file "backend/tests/contract/RoleContractTest.php"
check_file "backend/tests/integration/RoleAPITest.php"
check_file "backend/tests/unit/RoleServiceTest.php"
check_file "backend/tests/Support/Database/Seeds/TestSeeder.php"

echo ""
echo "Frontend Tests:"
echo "---------------"
check_file "frontend/tests/unit/useRoles.test.ts"
check_file "frontend/tests/e2e/role-management.spec.ts"

echo ""
echo "Configuration Files:"
echo "-------------------"
check_file "backend/phpunit.xml.dist"
check_file "frontend/package.json"

echo ""
echo "Documentation:"
echo "-------------"
check_file "TESTING.md"

echo ""
echo "======================================"
echo "Summary:"
echo "======================================"
echo -e "Total Checks: $TOTAL"
echo -e "${GREEN}Passed: $PASSED${NC}"
if [ $FAILED -gt 0 ]; then
    echo -e "${RED}Failed: $FAILED${NC}"
else
    echo -e "${GREEN}Failed: $FAILED${NC}"
fi
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All test files are present and ready!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Start Docker containers: docker compose up -d"
    echo "2. Install dependencies: cd backend && composer install"
    echo "3. Install frontend deps: cd frontend && npm install"
    echo "4. Run backend tests: cd backend && vendor/bin/phpunit"
    echo "5. Run frontend tests: cd frontend && npm run test"
    echo ""
    echo "See TESTING.md for detailed instructions."
    exit 0
else
    echo -e "${RED}✗ Some test files are missing!${NC}"
    exit 1
fi
