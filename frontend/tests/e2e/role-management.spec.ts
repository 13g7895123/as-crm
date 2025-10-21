import { test, expect } from '@playwright/test'

/**
 * Role Management E2E Tests
 *
 * Tests the complete user flow for role management
 * including creating, editing, and deleting roles through the UI
 */

test.describe('Role Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/auth/login')
    await page.fill('input[name="username"]', 'test_admin')
    await page.fill('input[name="password"]', 'password')
    await page.click('button[type="submit"]')

    // Wait for redirect to dashboard
    await page.waitForURL('/dashboard')

    // Navigate to roles page
    await page.goto('/roles')
  })

  test('should display roles list', async ({ page }) => {
    // Wait for roles to load
    await page.waitForSelector('[data-testid="roles-table"]')

    // Check that at least the seeded roles are displayed
    const roleRows = await page.locator('[data-testid="role-row"]').count()
    expect(roleRows).toBeGreaterThan(0)

    // Check for system roles
    await expect(page.getByText('系統管理員')).toBeVisible()
    await expect(page.getByText('業務主管')).toBeVisible()
  })

  test('should filter roles by active status', async ({ page }) => {
    await page.waitForSelector('[data-testid="roles-table"]')

    // Filter by active roles
    await page.click('[data-testid="filter-active"]')
    await page.click('[data-testid="filter-active-option-true"]')

    // All visible roles should be active
    const activeIndicators = await page.locator('[data-testid="role-active-badge"]').all()
    for (const indicator of activeIndicators) {
      await expect(indicator).toHaveText('啟用')
    }
  })

  test('should search roles by name', async ({ page }) => {
    await page.waitForSelector('[data-testid="roles-table"]')

    // Search for a specific role
    await page.fill('[data-testid="search-input"]', '業務主管')
    await page.waitForTimeout(500) // Wait for debounce

    // Should only show matching roles
    await expect(page.getByText('業務主管')).toBeVisible()

    const roleRows = await page.locator('[data-testid="role-row"]').count()
    expect(roleRows).toBeLessThanOrEqual(2) // May include parent/child roles
  })

  test('should create a new role with permissions', async ({ page }) => {
    // Click create button
    await page.click('[data-testid="create-role-button"]')

    // Wait for form to appear
    await page.waitForURL('/roles/create')

    // Fill in basic information
    await page.fill('[data-testid="role-name-input"]', 'regional_sales_manager')
    await page.fill('[data-testid="role-display-name-input"]', '區域業務主管')
    await page.fill('[data-testid="role-description-input"]', '負責華東區域的業務管理')

    // Select permissions
    await page.click('[data-testid="permission-customer.view"]')
    await page.click('[data-testid="permission-customer.edit"]')

    // Add condition rule
    await page.click('[data-testid="add-condition-button"]')
    await page.selectOption('[data-testid="condition-type-select"]', 'region')
    await page.selectOption('[data-testid="condition-operator-select"]', 'equals')
    await page.fill('[data-testid="condition-value-input"]', '華東')

    // Submit form
    await page.click('[data-testid="submit-role-button"]')

    // Wait for success message
    await expect(page.getByText('角色建立成功')).toBeVisible()

    // Should redirect to roles list
    await page.waitForURL('/roles')

    // New role should appear in list
    await expect(page.getByText('區域業務主管')).toBeVisible()
  })

  test('should validate required fields when creating role', async ({ page }) => {
    await page.click('[data-testid="create-role-button"]')
    await page.waitForURL('/roles/create')

    // Try to submit without filling required fields
    await page.click('[data-testid="submit-role-button"]')

    // Should show validation errors
    await expect(page.getByText('角色名稱為必填')).toBeVisible()
    await expect(page.getByText('顯示名稱為必填')).toBeVisible()

    // Should remain on create page
    expect(page.url()).toContain('/roles/create')
  })

  test('should validate role name format', async ({ page }) => {
    await page.click('[data-testid="create-role-button"]')
    await page.waitForURL('/roles/create')

    // Enter invalid name (with spaces and uppercase)
    await page.fill('[data-testid="role-name-input"]', 'Invalid Role Name')
    await page.fill('[data-testid="role-display-name-input"]', '無效角色')

    // Blur the input to trigger validation
    await page.click('[data-testid="role-description-input"]')

    // Should show validation error
    await expect(
      page.getByText('角色名稱只能包含小寫字母、數字和底線')
    ).toBeVisible()
  })

  test('should view role details', async ({ page }) => {
    await page.waitForSelector('[data-testid="roles-table"]')

    // Click on first role
    await page.click('[data-testid="role-row"]:first-child [data-testid="view-role-button"]')

    // Wait for detail page
    await page.waitForURL(/\/roles\/\d+$/)

    // Should display role information
    await expect(page.getByTestId('role-name')).toBeVisible()
    await expect(page.getByTestId('role-display-name')).toBeVisible()
    await expect(page.getByTestId('role-description')).toBeVisible()

    // Should display permissions section
    await expect(page.getByTestId('role-permissions-section')).toBeVisible()

    // Should display condition rules section
    await expect(page.getByTestId('role-conditions-section')).toBeVisible()
  })

  test('should edit an existing role', async ({ page }) => {
    // Create a role first
    await page.click('[data-testid="create-role-button"]')
    await page.waitForURL('/roles/create')

    await page.fill('[data-testid="role-name-input"]', 'editable_role')
    await page.fill('[data-testid="role-display-name-input"]', '可編輯角色')
    await page.click('[data-testid="permission-customer.view"]')
    await page.click('[data-testid="submit-role-button"]')

    await page.waitForURL('/roles')
    await expect(page.getByText('可編輯角色')).toBeVisible()

    // Click edit button
    const editButton = page.locator(
      '[data-testid="role-row"]:has-text("可編輯角色") [data-testid="edit-role-button"]'
    )
    await editButton.click()

    // Wait for edit page
    await page.waitForURL(/\/roles\/\d+\/edit$/)

    // Update display name
    await page.fill('[data-testid="role-display-name-input"]', '已編輯角色')

    // Add more permissions
    await page.click('[data-testid="permission-customer.edit"]')

    // Submit update
    await page.click('[data-testid="submit-role-button"]')

    // Wait for success message
    await expect(page.getByText('角色更新成功')).toBeVisible()

    // Verify changes in list
    await page.waitForURL('/roles')
    await expect(page.getByText('已編輯角色')).toBeVisible()
  })

  test('should delete a custom role', async ({ page }) => {
    // Create a role to delete
    await page.click('[data-testid="create-role-button"]')
    await page.waitForURL('/roles/create')

    await page.fill('[data-testid="role-name-input"]', 'deletable_role')
    await page.fill('[data-testid="role-display-name-input"]', '待刪除角色')
    await page.click('[data-testid="permission-customer.view"]')
    await page.click('[data-testid="submit-role-button"]')

    await page.waitForURL('/roles')

    // Click delete button
    const deleteButton = page.locator(
      '[data-testid="role-row"]:has-text("待刪除角色") [data-testid="delete-role-button"]'
    )
    await deleteButton.click()

    // Confirm deletion in modal
    await page.waitForSelector('[data-testid="confirm-delete-modal"]')
    await expect(page.getByText('確認刪除角色')).toBeVisible()
    await expect(page.getByText('待刪除角色')).toBeVisible()

    await page.click('[data-testid="confirm-delete-button"]')

    // Wait for success message
    await expect(page.getByText('角色刪除成功')).toBeVisible()

    // Role should no longer be in list
    await expect(page.getByText('待刪除角色')).not.toBeVisible()
  })

  test('should prevent deleting system roles', async ({ page }) => {
    await page.waitForSelector('[data-testid="roles-table"]')

    // Try to delete a system role
    const systemRoleRow = page.locator('[data-testid="role-row"]').filter({
      has: page.locator('[data-testid="system-role-badge"]')
    }).first()

    const deleteButton = systemRoleRow.locator('[data-testid="delete-role-button"]')

    // Delete button should be disabled for system roles
    await expect(deleteButton).toBeDisabled()

    // Or not visible at all
    await expect(deleteButton).toHaveAttribute('disabled', '')
  })

  test('should paginate through roles', async ({ page }) => {
    await page.waitForSelector('[data-testid="roles-table"]')

    // Check if pagination controls are visible
    const paginationControls = page.getByTestId('pagination-controls')

    if (await paginationControls.isVisible()) {
      // Get current page number
      const currentPage = await page.getByTestId('current-page').textContent()
      expect(currentPage).toBe('1')

      // Click next page
      await page.click('[data-testid="next-page-button"]')

      // Wait for page to update
      await page.waitForTimeout(500)

      // Page number should update
      const newPage = await page.getByTestId('current-page').textContent()
      expect(newPage).toBe('2')
    }
  })

  test('should display role permissions grouped by module', async ({ page }) => {
    await page.click('[data-testid="create-role-button"]')
    await page.waitForURL('/roles/create')

    // Permissions should be grouped by module
    await expect(page.getByTestId('permission-group-customer')).toBeVisible()
    await expect(page.getByTestId('permission-group-order')).toBeVisible()
    await expect(page.getByTestId('permission-group-report')).toBeVisible()

    // Each group should have permissions
    const customerPermissions = await page
      .locator('[data-testid="permission-group-customer"] [data-testid^="permission-"]')
      .count()

    expect(customerPermissions).toBeGreaterThan(0)
  })

  test('should add and remove condition rules', async ({ page }) => {
    await page.click('[data-testid="create-role-button"]')
    await page.waitForURL('/roles/create')

    // Add first condition
    await page.click('[data-testid="add-condition-button"]')
    await page.selectOption('[data-testid="condition-type-select-0"]', 'region')
    await page.selectOption('[data-testid="condition-operator-select-0"]', 'equals')
    await page.fill('[data-testid="condition-value-input-0"]', '華東')

    // Add second condition
    await page.click('[data-testid="add-condition-button"]')
    await page.selectOption('[data-testid="condition-type-select-1"]', 'department')
    await page.selectOption('[data-testid="condition-operator-select-1"]', 'in')
    await page.fill('[data-testid="condition-value-input-1"]', '業務部,銷售部')

    // Should have 2 conditions
    const conditionRows = await page.locator('[data-testid^="condition-row-"]').count()
    expect(conditionRows).toBe(2)

    // Remove first condition
    await page.click('[data-testid="remove-condition-button-0"]')

    // Should have 1 condition left
    const remainingConditions = await page.locator('[data-testid^="condition-row-"]').count()
    expect(remainingConditions).toBe(1)
  })

  test('should handle network errors gracefully', async ({ page, context }) => {
    // Intercept API calls and return error
    await context.route('**/api/v1/roles', (route) => {
      route.fulfill({
        status: 500,
        body: JSON.stringify({ error: 'Internal server error' })
      })
    })

    await page.goto('/roles')

    // Should display error message
    await expect(page.getByText('載入角色失敗')).toBeVisible()

    // Should show retry button
    await expect(page.getByTestId('retry-button')).toBeVisible()
  })
})
