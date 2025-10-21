# 資料模型：RBAC 權限管理系統

**Date**: 2025-10-21
**Feature**: RBAC 權限管理系統
**Database**: MariaDB 10.6+

## 實體關係圖概述

```
[users] 1--N [role_assignments] N--1 [roles]
[roles] 1--N [role_permissions] N--1 [permissions]
[roles] N--N [role_hierarchy] (Closure Table)
[permissions] 1--N [condition_rules]
[users] 1--N [audit_logs]
```

## 核心實體

### 1. roles (角色)

代表系統中的角色，可以是預設角色或自訂角色。

**欄位**:

| 欄位名稱 | 型別 | 限制 | 說明 |
|---------|------|------|------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | 角色 ID |
| name | VARCHAR(100) | NOT NULL, UNIQUE | 角色名稱 |
| display_name | VARCHAR(255) | NOT NULL | 顯示名稱（繁體中文） |
| description | TEXT | NULL | 角色描述 |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | 啟用狀態 (1=啟用, 0=停用) |
| is_system | TINYINT(1) | NOT NULL, DEFAULT 0 | 是否為系統預設角色 (不可刪除) |
| parent_role_id | BIGINT UNSIGNED | NULL, FOREIGN KEY(roles.id) | 父角色 ID（用於繼承） |
| created_by | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY(users.id) | 建立者 ID |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | 建立時間 |
| updated_at | TIMESTAMP | NULL, ON UPDATE CURRENT_TIMESTAMP | 更新時間 |

**索引**:
- PRIMARY KEY (id)
- UNIQUE KEY (name)
- INDEX (is_active)
- INDEX (parent_role_id)
- INDEX (created_by)

**驗證規則**:
- name: 英文、數字、底線，3-100 字元
- display_name: 必填，2-255 字元
- 系統角色 (is_system=1) 不可刪除和修改 name

**預設資料** (Seeder):
```sql
INSERT INTO roles (name, display_name, description, is_system) VALUES
('system_admin', '系統管理員', '擁有所有權限的超級管理員', 1),
('sales_manager', '業務主管', '管理業務團隊和客戶', 1),
('sales_staff', '業務人員', '處理客戶和訂單', 1),
('customer_service', '客服人員', '處理客戶諮詢和支援', 1);
```

---

### 2. permissions (權限)

代表系統中的權限，定義對特定功能模組的特定操作能力。

**欄位**:

| 欄位名稱 | 型別 | 限制 | 說明 |
|---------|------|------|------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | 權限 ID |
| module | VARCHAR(100) | NOT NULL | 功能模組（如 customer, order, report） |
| action | ENUM | NOT NULL | 操作類型（'view','edit','export','assign'） |
| display_name | VARCHAR(255) | NOT NULL | 顯示名稱（繁體中文） |
| description | TEXT | NULL | 權限描述 |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | 建立時間 |
| updated_at | TIMESTAMP | NULL, ON UPDATE CURRENT_TIMESTAMP | 更新時間 |

**索引**:
- PRIMARY KEY (id)
- UNIQUE KEY (module, action)
- INDEX (module)

**action ENUM 值**:
- `view`: 檢視
- `edit`: 編輯
- `export`: 匯出
- `assign`: 指派

**預設資料** (Seeder):
```sql
INSERT INTO permissions (module, action, display_name, description) VALUES
-- 客戶管理
('customer', 'view', '檢視客戶', '檢視客戶清單和詳細資料'),
('customer', 'edit', '編輯客戶', '建立、修改、刪除客戶資料'),
('customer', 'export', '匯出客戶', '匯出客戶清單為 CSV/Excel'),
('customer', 'assign', '指派客戶', '將客戶指派給業務人員'),
-- 訂單管理
('order', 'view', '檢視訂單', '檢視訂單清單和詳細資料'),
('order', 'edit', '編輯訂單', '建立、修改訂單'),
('order', 'export', '匯出訂單', '匯出訂單清單'),
-- 報表中心
('report', 'view', '檢視報表', '檢視業績報表和統計'),
('report', 'export', '匯出報表', '匯出報表資料'),
-- 權限管理
('role', 'view', '檢視角色', '檢視角色清單和權限'),
('role', 'edit', '編輯角色', '建立、修改、刪除角色'),
('user_permission', 'view', '檢視使用者權限', '檢視使用者的角色和權限'),
('user_permission', 'edit', '編輯使用者權限', '指派或撤銷使用者的角色');
```

---

### 3. role_permissions (角色權限關聯)

定義角色擁有哪些權限。

**欄位**:

| 欄位名稱 | 型別 | 限制 | 說明 |
|---------|------|------|------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | 關聯 ID |
| role_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY(roles.id) ON DELETE CASCADE | 角色 ID |
| permission_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY(permissions.id) ON DELETE CASCADE | 權限 ID |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | 建立時間 |

**索引**:
- PRIMARY KEY (id)
- UNIQUE KEY (role_id, permission_id)
- INDEX (role_id)
- INDEX (permission_id)

---

### 4. condition_rules (條件限制規則)

定義權限的條件式限制（如部門、區域、客戶分群）。

**欄位**:

| 欄位名稱 | 型別 | 限制 | 說明 |
|---------|------|------|------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | 規則 ID |
| role_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY(roles.id) ON DELETE CASCADE | 所屬角色 ID |
| permission_id | BIGINT UNSIGNED | NULL, FOREIGN KEY(permissions.id) ON DELETE CASCADE | 限制的權限 ID（NULL=適用於角色所有權限） |
| condition_type | VARCHAR(50) | NOT NULL | 條件類型（department, region, customer_group, order_status, amount_range） |
| operator | ENUM | NOT NULL | 運算子（'equals','not_equals','in','not_in','greater_than','less_than','between'） |
| condition_value | JSON | NOT NULL | 條件值（依 condition_type 而定） |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | 建立時間 |
| updated_at | TIMESTAMP | NULL, ON UPDATE CURRENT_TIMESTAMP | 更新時間 |

**索引**:
- PRIMARY KEY (id)
- INDEX (role_id, permission_id)
- INDEX (condition_type)

**condition_value JSON 範例**:
```json
{
  "values": ["業務部", "客服部"]
}
```

**operator ENUM 值**:
- `equals`: 等於
- `not_equals`: 不等於
- `in`: 包含於
- `not_in`: 不包含於
- `greater_than`: 大於
- `less_than`: 小於
- `between`: 介於之間

---

### 5. role_assignments (角色指派)

代表將角色指派給使用者，支援時間性授權。

**欄位**:

| 欄位名稱 | 型別 | 限制 | 說明 |
|---------|------|------|------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | 指派 ID |
| user_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY(users.id) ON DELETE CASCADE | 使用者 ID |
| role_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY(roles.id) ON DELETE CASCADE | 角色 ID |
| valid_from | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | 有效開始時間 |
| valid_until | DATETIME | NULL | 有效結束時間（NULL=永久有效） |
| assigned_by | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY(users.id) | 指派者 ID |
| assigned_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | 指派時間 |
| revoked_at | TIMESTAMP | NULL | 撤銷時間 |
| revoked_by | BIGINT UNSIGNED | NULL, FOREIGN KEY(users.id) | 撤銷者 ID |

**索引**:
- PRIMARY KEY (id)
- INDEX (user_id, valid_from, valid_until)
- INDEX (role_id)
- INDEX (assigned_by)
- INDEX (valid_until) -- 用於清理過期角色

**驗證規則**:
- valid_from < valid_until (if valid_until is not NULL)
- 不可重複指派相同的 role_id 給同一個 user_id（在有效期內）

---

### 6. role_hierarchy (角色階層 - Closure Table)

使用 Closure Table 模式儲存角色之間的階層關係，支援多層繼承。

**欄位**:

| 欄位名稱 | 型別 | 限制 | 說明 |
|---------|------|------|------|
| ancestor_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY(roles.id) ON DELETE CASCADE | 祖先角色 ID |
| descendant_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY(roles.id) ON DELETE CASCADE | 後代角色 ID |
| depth | INT | NOT NULL | 層級深度（0=自己，1=直接子角色，2+=間接子角色） |

**索引**:
- PRIMARY KEY (ancestor_id, descendant_id)
- INDEX (descendant_id)
- INDEX (depth)

**範例資料**:
```sql
-- 系統管理員 → 部門主管 → 業務主管 → 業務人員
INSERT INTO role_hierarchy (ancestor_id, descendant_id, depth) VALUES
-- 系統管理員
(1, 1, 0), (1, 2, 1), (1, 3, 2), (1, 4, 3),
-- 部門主管
(2, 2, 0), (2, 3, 1), (2, 4, 2),
-- 業務主管
(3, 3, 0), (3, 4, 1),
-- 業務人員
(4, 4, 0);
```

**查詢所有繼承權限**:
```sql
SELECT DISTINCT p.*
FROM permissions p
INNER JOIN role_permissions rp ON p.id = rp.permission_id
INNER JOIN role_hierarchy rh ON rp.role_id = rh.ancestor_id
WHERE rh.descendant_id = ?  -- 目標角色 ID
```

---

### 7. audit_logs (審計記錄)

記錄所有使用者操作，支援完整的回溯和稽核。

**欄位**:

| 欄位名稱 | 型別 | 限制 | 說明 |
|---------|------|------|------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | 記錄 ID |
| user_id | BIGINT UNSIGNED | NOT NULL, FOREIGN KEY(users.id) | 操作者 ID |
| action | VARCHAR(50) | NOT NULL | 操作類型（view, create, update, delete, export, assign, permission_check_failed） |
| target_type | VARCHAR(100) | NOT NULL | 目標資料類型（customer, order, role, user_permission） |
| target_id | BIGINT UNSIGNED | NULL | 目標資料 ID |
| old_values | JSON | NULL | 修改前的欄位值（僅 update） |
| new_values | JSON | NULL | 修改後的欄位值（create/update） |
| result | ENUM | NOT NULL, DEFAULT 'success' | 操作結果（'success','failed','denied'） |
| ip_address | VARCHAR(45) | NOT NULL | IP 位址（支援 IPv6） |
| user_agent | VARCHAR(255) | NULL | User Agent |
| request_id | VARCHAR(36) | NULL | 請求 ID（correlation ID for tracing） |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | 操作時間 |

**索引**:
- PRIMARY KEY (id)
- INDEX (user_id, created_at)
- INDEX (target_type, target_id, created_at)
- INDEX (action, created_at)
- INDEX (result, created_at)
- INDEX (request_id)

**分割策略** (Partitioning):
- 按月份分割（`PARTITION BY RANGE (MONTH(created_at))`）
- 每月建立新分割區
- 自動歸檔 90 天前的資料到 `audit_logs_archive` 表

**old_values / new_values JSON 範例**:
```json
{
  "phone": "0912345678",
  "region": "華北",
  "department": "業務部"
}
```

---

### 8. users (使用者 - 參考)

此表假設已存在於系統中，RBAC 系統僅引用其欄位。

**必要欄位** (RBAC 系統依賴):

| 欄位名稱 | 型別 | 說明 |
|---------|------|------|
| id | BIGINT UNSIGNED | 使用者 ID |
| username | VARCHAR(100) | 使用者名稱 |
| email | VARCHAR(255) | Email |
| department | VARCHAR(100) | 部門（用於條件限制） |
| region | VARCHAR(100) | 區域（用於條件限制） |
| is_active | TINYINT(1) | 啟用狀態 |

---

## 狀態轉換

### 角色狀態
```
[草稿] -(啟用)-> [啟用] -(停用)-> [停用] -(啟用)-> [啟用]
                                    -(刪除)-> [已刪除]
```

### 角色指派狀態
```
[有效] -(到期/撤銷)-> [失效]
```

## 資料關係完整性

### 級聯刪除規則

1. **刪除角色時**:
   - CASCADE 刪除 `role_permissions` (角色-權限關聯)
   - CASCADE 刪除 `condition_rules` (條件限制規則)
   - CASCADE 刪除 `role_assignments` (角色指派)
   - CASCADE 刪除 `role_hierarchy` (角色階層關係)

2. **刪除權限時**:
   - CASCADE 刪除 `role_permissions` (角色-權限關聯)
   - CASCADE 刪除 `condition_rules` (與該權限相關的條件)

3. **刪除使用者時**:
   - CASCADE 刪除 `role_assignments` (該使用者的角色指派)
   - RESTRICT `audit_logs` (保留審計記錄，但標記使用者已刪除)

### 約束條件

1. **唯一性約束**:
   - `roles.name` 必須唯一
   - `permissions.(module, action)` 組合必須唯一
   - `role_permissions.(role_id, permission_id)` 組合必須唯一
   - `role_hierarchy.(ancestor_id, descendant_id)` 組合必須唯一

2. **檢查約束**:
   - `roles.is_active` 只能是 0 或 1
   - `role_assignments.valid_from` < `role_assignments.valid_until`
   - `role_hierarchy.depth` >= 0

## 效能優化

### 查詢優化策略

1. **權限檢查查詢** (最高頻率):
```sql
-- 檢查使用者是否有特定權限（含條件限制）
SELECT COUNT(*) > 0 AS has_permission
FROM users u
INNER JOIN role_assignments ra ON u.id = ra.user_id
  AND (ra.valid_until IS NULL OR ra.valid_until > NOW())
  AND ra.valid_from <= NOW()
INNER JOIN role_hierarchy rh ON ra.role_id = rh.descendant_id
INNER JOIN role_permissions rp ON rh.ancestor_id = rp.role_id
INNER JOIN permissions p ON rp.permission_id = p.id
LEFT JOIN condition_rules cr ON rp.role_id = cr.role_id
  AND (cr.permission_id IS NULL OR cr.permission_id = p.id)
WHERE u.id = ? AND p.module = ? AND p.action = ?
  AND (cr.id IS NULL OR [條件驗證邏輯])
```

2. **快取策略**:
   - 使用者登入時載入所有有效角色和權限到 session
   - TTL: 30 分鐘
   - 權限變更時清除相關使用者的快取

3. **索引使用**:
   - 所有 JOIN 欄位都有索引
   - 時間範圍查詢使用複合索引

### 資料量估算

- 角色：1,000 個
- 權限：100 個
- 使用者：100,000 個
- 角色指派：200,000 筆（平均每使用者 2 個角色）
- 審計記錄：每月 1,000,000 筆（每天 ~33,000 筆）

## Migration 順序

1. `create_roles_table`
2. `create_permissions_table`
3. `create_role_permissions_table`
4. `create_condition_rules_table`
5. `create_role_assignments_table`
6. `create_role_hierarchy_table`
7. `create_audit_logs_table`
8. `seed_default_roles` (Seeder)
9. `seed_default_permissions` (Seeder)

## 總結

此資料模型設計支援：
- ✅ 動態建立自訂角色
- ✅ 細緻的權限控制（檢視/編輯/匯出/指派）
- ✅ 條件式限制（部門/區域/客戶分群等）
- ✅ 時間性授權
- ✅ 角色階層繼承（Closure Table）
- ✅ 完整的審計記錄
- ✅ 高效能查詢（索引優化）
- ✅ 水平擴展性（分割表策略）
