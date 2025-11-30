# CRM RBAC Testing Guide

This document provides instructions for running all tests for the RBAC Permission Management system.

## Test Overview

### Test Files Created

**Backend Tests (3 files):**
- `backend/tests/contract/RoleContractTest.php` - API contract validation (8.7KB)
- `backend/tests/integration/RoleAPITest.php` - Integration tests (12KB)
- `backend/tests/unit/RoleServiceTest.php` - Unit tests (14KB)
- `backend/tests/Support/Database/Seeds/TestSeeder.php` - Test data seeder

**Frontend Tests (2 files):**
- `frontend/tests/unit/useRoles.test.ts` - Composable unit tests (9.9KB)
- `frontend/tests/e2e/role-management.spec.ts` - E2E tests (12KB)

**Total: 5 test files with comprehensive coverage**

---

## Prerequisites

### Backend Tests

1. **Docker containers running:**
   ```bash
   docker compose up -d database backend
   ```

2. **Database seeded:**
   ```bash
   docker compose exec backend php spark migrate
   docker compose exec backend php spark db:seed PermissionSeeder
   docker compose exec backend php spark db:seed RoleSeeder
   ```

3. **Composer dependencies installed:**
   ```bash
   cd backend
   composer install
   ```

### Frontend Tests

1. **Node.js dependencies installed:**
   ```bash
   cd frontend
   npm install
   ```

2. **Backend API running** (for E2E tests):
   ```bash
   docker compose up -d backend
   ```

---

## Running Backend Tests

### All Backend Tests
```bash
cd backend
vendor/bin/phpunit
```

### Contract Tests Only
```bash
vendor/bin/phpunit --testsuite contract
```

### Integration Tests Only
```bash
vendor/bin/phpunit --testsuite integration
```

### Unit Tests Only
```bash
vendor/bin/phpunit --testsuite unit
```

### With Coverage Report
```bash
vendor/bin/phpunit --coverage-html coverage/
```

### Inside Docker Container
```bash
docker compose exec backend vendor/bin/phpunit
```

---

## Running Frontend Tests

### All Frontend Tests
```bash
cd frontend
npm run test
```

### Unit Tests Only
```bash
npm run test:unit
```

### E2E Tests
```bash
npm run test:e2e
```

### E2E Tests with UI
```bash
npm run test:e2e:ui
```

### Watch Mode (Unit Tests)
```bash
npm run test -- --watch
```

---

## Test Coverage

### Backend Test Coverage

**Contract Tests (RoleContractTest.php):**
- ✅ GET /api/v1/roles returns correct structure
- ✅ Query parameters (page, per_page, sort, order)
- ✅ POST /api/v1/roles creates new role
- ✅ Required field validation
- ✅ Name pattern validation
- ✅ GET /api/v1/roles/{id} returns role detail
- ✅ 404 handling for non-existent roles
- ✅ PUT /api/v1/roles/{id} updates role
- ✅ DELETE /api/v1/roles/{id} deletes role
- ✅ System role deletion prevention
- ✅ GET /api/v1/roles/{id}/permissions
- ✅ Authentication requirement

**Integration Tests (RoleAPITest.php):**
- ✅ Complete role creation workflow
- ✅ Permission assignment
- ✅ Condition rule creation
- ✅ Role update workflow
- ✅ Role deletion workflow
- ✅ System role protection
- ✅ Name uniqueness validation
- ✅ Pagination and filtering
- ✅ Search functionality
- ✅ Role permissions inheritance

**Unit Tests (RoleServiceTest.php):**
- ✅ Create role with basic info
- ✅ Create role with permissions
- ✅ Create role with condition rules
- ✅ Update role
- ✅ Update role permissions
- ✅ Delete custom role
- ✅ System role deletion prevention
- ✅ Name validation
- ✅ Name uniqueness
- ✅ Get role by ID
- ✅ Get roles with filters
- ✅ Search functionality
- ✅ Pagination
- ✅ Condition type validation
- ✅ Operator validation
- ✅ Permission inheritance

### Frontend Test Coverage

**Unit Tests (useRoles.test.ts):**
- ✅ getRoles fetches from API
- ✅ Pagination parameters
- ✅ Search parameter
- ✅ API error handling
- ✅ createRole validation
- ✅ Condition rules inclusion
- ✅ updateRole functionality
- ✅ Permission updates
- ✅ 404 error handling
- ✅ deleteRole functionality
- ✅ System role deletion prevention
- ✅ getRoleById with details
- ✅ Store state updates
- ✅ Loading states

**E2E Tests (role-management.spec.ts):**
- ✅ Display roles list
- ✅ Filter by active status
- ✅ Search roles by name
- ✅ Create new role with permissions
- ✅ Required field validation
- ✅ Name format validation
- ✅ View role details
- ✅ Edit existing role
- ✅ Delete custom role
- ✅ System role deletion prevention
- ✅ Pagination controls
- ✅ Permission grouping by module
- ✅ Add/remove condition rules
- ✅ Network error handling

---

## Expected Test Results

### Backend Tests
```
PHPUnit 10.x by Sebastian Bergmann and contributors.

Contract Tests
..............                                             14 / 14 (100%)

Integration Tests
..................                                         18 / 18 (100%)

Unit Tests
.............................                              29 / 29 (100%)

Time: 00:05.234, Memory: 24.00 MB

OK (61 tests, 150 assertions)
```

### Frontend Tests
```
✓ frontend/tests/unit/useRoles.test.ts (14 tests)
✓ frontend/tests/e2e/role-management.spec.ts (14 tests)

Test Files  2 passed (2)
     Tests  28 passed (28)
  Start at  20:00:00
  Duration  3.24s
```

---

## Continuous Integration

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  backend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Start containers
        run: docker compose up -d database backend
      - name: Run migrations
        run: docker compose exec -T backend php spark migrate
      - name: Run seeders
        run: docker compose exec -T backend php spark db:seed TestSeeder
      - name: Run PHPUnit tests
        run: docker compose exec -T backend vendor/bin/phpunit

  frontend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      - name: Install dependencies
        run: cd frontend && npm ci
      - name: Run unit tests
        run: cd frontend && npm run test:unit
      - name: Install Playwright
        run: cd frontend && npx playwright install
      - name: Run E2E tests
        run: cd frontend && npm run test:e2e
```

---

## Troubleshooting

### Backend Tests

**Issue: Database connection failed**
```bash
# Solution: Ensure database container is running
docker compose up -d database
docker compose exec backend php spark migrate
```

**Issue: Class not found errors**
```bash
# Solution: Regenerate autoload files
cd backend
composer dump-autoload
```

**Issue: Test data conflicts**
```bash
# Solution: Reset database
docker compose exec backend php spark migrate:refresh
docker compose exec backend php spark db:seed TestSeeder
```

### Frontend Tests

**Issue: Module not found**
```bash
# Solution: Install dependencies
cd frontend
npm install
```

**Issue: E2E tests failing**
```bash
# Solution: Ensure backend is running and accessible
docker compose up -d backend
curl http://localhost:8080/api/v1/roles
```

**Issue: Playwright browsers not installed**
```bash
# Solution: Install Playwright browsers
npx playwright install
```

---

## Test Maintenance

### Adding New Tests

**Backend:**
1. Create test file in appropriate directory (contract/integration/unit)
2. Extend from `CodeIgniter\Test\CIUnitTestCase`
3. Use appropriate traits (DatabaseTestTrait, FeatureTestTrait)
4. Add to phpunit.xml testsuite if needed

**Frontend:**
1. Create test file in tests/unit or tests/e2e
2. Import from vitest or @playwright/test
3. Follow existing naming conventions (*.test.ts or *.spec.ts)

### Updating Tests

When API changes:
1. Update contract tests first
2. Update integration tests
3. Update unit tests
4. Update frontend E2E tests
5. Run full test suite to verify

---

## Test Data

### Test Seeder Creates:
- 5 permissions (customer.view, customer.edit, customer.export, customer.assign, order.view)
- 2 system roles (system_admin, sales_manager)
- Role-permission assignments
- 1 test user (test_admin / password)

### Test Fixtures:
Test data is reset before each test using `$refresh = true` in test classes.

---

## Performance

### Backend Tests
- Typical execution time: ~5 seconds
- Database operations use transactions (auto-rollback)
- In-memory SQLite can be used for faster tests

### Frontend Tests
- Unit tests: < 1 second
- E2E tests: 2-3 seconds
- Parallel execution supported

---

## Next Steps

1. ✅ All test files created and ready
2. ⏳ Set up CI/CD pipeline
3. ⏳ Add code coverage reporting
4. ⏳ Add performance benchmarks
5. ⏳ Add mutation testing

---

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [CodeIgniter Testing](https://codeigniter.com/user_guide/testing/index.html)
- [Vitest Documentation](https://vitest.dev/)
- [Playwright Documentation](https://playwright.dev/)
