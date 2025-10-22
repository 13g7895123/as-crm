# Tasks: RBAC 權限管理系統

**Input**: Design documents from `/specs/001-rbac-permission-management/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: 測試任務僅包含於 P1 核心功能（根據 plan.md 的 TDD 要求）

**Organization**: 任務按使用者故事分組,以便獨立實作和測試每個故事

## Format: `[ID] [P?] [Story] Description`
- **[P]**: 可平行執行（不同檔案、無相依性）
- **[Story]**: 此任務屬於哪個使用者故事（例如 US1, US2, US3）
- 描述中包含確切的檔案路徑

## Path Conventions
- **Backend**: `backend/app/`
- **Frontend**: `frontend/`
- 根目錄: Docker 配置和環境變數

---

## Phase 1: Setup (共用基礎設施)

**Purpose**: 專案初始化和基本結構設定

- [X] T001 檢查並建立專案根目錄結構（backend/, frontend/, specs/, docker/）
- [X] T002 [P] 建立 .env 檔案（所有 port 配置集中管理）於專案根目錄
- [X] T003 [P] 建立 backend/composer.json 並安裝 CodeIgniter 4.4+ 相依套件
- [X] T004 [P] 建立 frontend/package.json 並安裝 Nuxt 3 相依套件
- [X] T005 建立 docker-compose.yml（開發環境: database, backend, frontend）於根目錄
- [X] T006 建立 docker-compose.prod.yml（生產環境: database, backend, frontend, nginx）於根目錄
- [X] T007 [P] 建立 backend/Dockerfile（多階段構建: 開發/生產）於 backend/
- [X] T008 [P] 建立 frontend/Dockerfile（多階段構建: builder/production）於 frontend/
- [X] T009 [P] 建立 docker/nginx/default.conf（反向代理配置）於 docker/nginx/
- [X] T010 [P] 建立 build-dev.sh（啟動所有開發服務）於根目錄
- [X] T011 [P] 建立 build-backend-dev.sh（僅啟動後端服務）於根目錄
- [X] T012 [P] 建立 build-frontend-dev.sh（僅啟動前端服務）於根目錄
- [X] T013 [P] 建立 build.sh（生產環境建置腳本）於根目錄

---

## Phase 2: Foundational (阻塞性先決條件)

**Purpose**: 核心基礎設施,必須在任何使用者故事之前完成

**⚠️ 關鍵**: 在此階段完成前,不能開始任何使用者故事的工作

- [X] T014 建立 backend/app/Database/Migrations/2025-10-21-000001-create-users-table.php（假設 users 表不存在）
- [X] T015 [P] 建立 backend/app/Database/Migrations/2025-10-21-000002-create-roles-table.php
- [X] T016 [P] 建立 backend/app/Database/Migrations/2025-10-21-000003-create-permissions-table.php
- [X] T017 [P] 建立 backend/app/Database/Migrations/2025-10-21-000004-create-role-permissions-table.php
- [X] T018 [P] 建立 backend/app/Database/Migrations/2025-10-21-000005-create-condition-rules-table.php
- [X] T019 [P] 建立 backend/app/Database/Migrations/2025-10-21-000006-create-role-assignments-table.php
- [X] T020 [P] 建立 backend/app/Database/Migrations/2025-10-21-000007-create-role-hierarchy-table.php
- [X] T021 [P] 建立 backend/app/Database/Migrations/2025-10-21-000008-create-audit-logs-table.php（含分割策略）
- [X] T022 執行所有資料庫 migrations（php spark migrate）
- [X] T023 [P] 建立 backend/app/Database/Seeds/RoleSeeder.php（預設角色: 系統管理員、業務主管、業務人員、客服）
- [X] T024 [P] 建立 backend/app/Database/Seeds/PermissionSeeder.php（客戶/訂單/報表/角色/使用者權限管理模組的 view/edit/export/assign 權限）
- [X] T025 執行資料庫 seeders（php spark db:seed RoleSeeder && php spark db:seed PermissionSeeder）
- [X] T026 [P] 配置 backend/app/Config/Routes.php（API v1 路由結構）
- [X] T027 [P] 建立 backend/app/Filters/AuthFilter.php（JWT 身份驗證 filter）
- [X] T028 [P] 配置 backend/app/Config/Cors.php（CORS 設定,允許前端網域）
- [X] T029 [P] 建立 frontend/nuxt.config.ts（Nuxt 3 配置,含 API base URL, Pinia, UI 框架）
- [X] T030 [P] 建立 frontend/stores/auth.ts（Pinia auth store,管理 JWT token 和使用者狀態）
- [X] T031 [P] 建立 frontend/composables/useAuth.ts（身份驗證 composable,提供 login/logout/getToken 方法）
- [X] T032 [P] 建立 backend/app/Libraries/JWT.php（JWT token 生成和驗證 library）
- [X] T033 建立 backend/app/Controllers/API/AuthController.php（login/logout/refresh token endpoints）
- [X] T034 [P] 建立 frontend/layouts/default.vue（主要布局:側邊欄+導覽列+內容區域三區塊結構,符合 FR-033）
- [X] T035 [P] 建立 frontend/components/layout/Sidebar.vue（側邊欄元件:淺灰/白色背景,深色文字,權限動態選單,符合 FR-034）
- [X] T036 [P] 建立 frontend/components/layout/Navbar.vue（導覽列元件:白色背景,深色文字/圖示,60-64px 高度,符合 FR-035）
- [X] T037 [P] 建立 frontend/components/layout/ContentArea.vue（內容區域容器:白色/淺灰背景,卡片式設計,符合 FR-036）

**Checkpoint**: 基礎設施就緒 - 使用者故事實作現在可以平行開始

---

## Phase 3: User Story 1 - 系統管理員建立自訂角色與權限 (Priority: P1) 🎯 MVP

**Goal**: 系統管理員能夠建立、編輯、刪除自訂角色,設定權限組合（檢視/編輯/匯出/指派）,以及配置條件式限制（部門、區域）

**Independent Test**: 建立一個新角色「區域業務主管」、賦予特定權限（檢視和編輯客戶資料）、設定條件限制（限制於華東區域）、驗證角色的權限設定被正確保存,並可透過 API 查詢

### Tests for User Story 1 (TDD - P1 核心功能) ⚠️

**NOTE: 先寫這些測試,確保它們在實作前失敗**

- [X] T038 [P] [US1] 建立 backend/tests/contract/RoleContractTest.php（測試 roles API 符合 OpenAPI 規格）
- [X] T039 [P] [US1] 建立 backend/tests/integration/RoleAPITest.php（測試建立/更新/刪除角色的完整流程）
- [X] T040 [P] [US1] 建立 backend/tests/unit/RoleServiceTest.php（測試 RoleService 的業務邏輯）
- [X] T041 [P] [US1] 建立 frontend/tests/unit/useRoles.test.ts（測試 useRoles composable）
- [X] T042 [P] [US1] 建立 frontend/tests/e2e/role-management.spec.ts（E2E 測試角色管理完整流程）

### Implementation for User Story 1

- [X] T043 [P] [US1] 建立 backend/app/Models/RoleModel.php（角色資料模型,含驗證規則）
- [X] T044 [P] [US1] 建立 backend/app/Models/PermissionModel.php（權限資料模型）
- [X] T045 [P] [US1] 建立 backend/app/Models/RolePermissionModel.php（角色-權限關聯模型）
- [X] T046 [P] [US1] 建立 backend/app/Models/ConditionRuleModel.php（條件限制規則模型,含 JSON 解析）
- [X] T047 [US1] 建立 backend/app/Services/RoleService.php（角色 CRUD 業務邏輯,依賴 T043-T046）
- [X] T048 [US1] 建立 backend/app/Services/PermissionService.php（權限查詢業務邏輯,依賴 T044）
- [X] T049 [US1] 建立 backend/app/Controllers/API/RoleController.php（Roles API endpoints: GET/POST/PUT/DELETE /roles,依賴 T047-T048）
- [X] T050 [US1] 建立 backend/app/Controllers/API/PermissionController.php（Permissions API endpoints: GET /permissions,依賴 T048）
- [X] T051 [US1] 在 RoleService 中實作條件限制驗證邏輯（驗證 condition_type 和 operator 的有效性）
- [X] T052 [US1] 在 RoleController 中新增錯誤處理和驗證（系統角色不可刪除、name 唯一性檢查）
- [X] T053 [P] [US1] 建立 frontend/stores/roles.ts（Pinia roles store,管理角色列表狀態）
- [X] T054 [P] [US1] 建立 frontend/stores/permissions.ts（Pinia permissions store,快取權限資料,TTL 30分鐘）
- [X] T055 [US1] 建立 frontend/composables/useRoles.ts（角色管理 composable,提供 createRole/updateRole/deleteRole/getRoles 方法,依賴 T053）
- [X] T056 [US1] 建立 frontend/composables/usePermissions.ts（權限查詢 composable,提供 getPermissions/can/canAny 方法,依賴 T054）
- [X] T057 [P] [US1] 建立 frontend/components/roles/RoleForm.vue（角色表單元件,含名稱、描述、啟用狀態輸入,使用 default.vue 布局）
- [X] T058 [P] [US1] 建立 frontend/components/roles/PermissionSelector.vue（權限選擇器元件,按模組分組顯示權限）
- [X] T059 [P] [US1] 建立 frontend/components/roles/ConditionBuilder.vue（條件建構器元件,支援部門/區域/客戶分群條件）
- [X] T060 [US1] 建立 frontend/pages/roles/index.vue（角色清單頁面,含分頁、篩選、排序,使用 default.vue 布局,依賴 T055, T057-T059）
- [X] T061 [US1] 建立 frontend/pages/roles/create.vue（建立角色頁面,整合 RoleForm/PermissionSelector/ConditionBuilder,使用 default.vue 布局,依賴 T055, T057-T059）
- [X] T062 [US1] 建立 frontend/pages/roles/[id]/edit.vue（編輯角色頁面,使用 default.vue 布局,依賴 T055, T057-T059）
- [X] T063 [US1] 執行所有 User Story 1 測試並確保通過（npm run test:unit && npm run test:e2e && vendor/bin/phpunit）

**Checkpoint**: 此時 User Story 1 應該完全可運作且可獨立測試

---

## Phase 4: User Story 2 - 業務主管管理團隊成員權限 (Priority: P2)

**Goal**: 業務主管能夠為團隊成員指派角色、設定時間性授權（有效期限）、檢視團隊成員的當前權限狀態

**Independent Test**: 業務主管登入、選擇團隊成員、為其指派「業務人員」角色、設定「2025-12-31 前有效」的時間限制、驗證該成員在指定時間內擁有相應權限、過期後權限自動撤銷

### Implementation for User Story 2

- [X] T064 [P] [US2] 建立 backend/app/Models/RoleAssignmentModel.php（角色指派模型,含時間性授權驗證）
- [X] T065 [US2] 建立 backend/app/Services/RoleAssignmentService.php（角色指派業務邏輯,依賴 T064）
- [X] T066 [US2] 建立 backend/app/Controllers/API/RoleAssignmentController.php（Role Assignments API endpoints: POST/DELETE /role-assignments, GET /users/{userId}/roles,依賴 T065）
- [X] T067 [US2] 實作時間性授權檢查邏輯（在 RoleAssignmentService 中驗證 valid_from < valid_until）
- [X] T068 [US2] 建立排程任務清理過期角色指派（backend/app/Commands/CleanExpiredRolesCommand.php,每 5 分鐘執行以符合 SC-008 要求）
- [X] T069 [US2] 建立過期前通知機制（backend/app/Commands/NotifyExpiringRolesCommand.php,檢查 7天/3天/1天前即將過期的角色）
- [X] T070 [P] [US2] 建立 frontend/pages/teams/manage.vue（團隊成員權限管理頁面,顯示成員清單及當前角色,使用 default.vue 布局）
- [X] T071 [P] [US2] 建立 frontend/components/teams/MemberPermissionEditor.vue（成員權限編輯器元件,支援指派角色和設定有效期限）
- [X] T072 [US2] 建立 frontend/composables/useRoleAssignments.ts（角色指派 composable,提供 assignRole/revokeRole/getUserRoles 方法）
- [X] T073 [US2] 在 frontend/pages/teams/manage.vue 中整合 MemberPermissionEditor（依賴 T070-T072）

### Additional Features for User Story 2

- [X] T074 [US2] 在 RoleAssignmentController 中實作延長/縮短角色有效期限功能（PUT /role-assignments/{id}/extend endpoint,支援 FR-013）
- [X] T075 [P] [US2] 建立 frontend/components/notifications/ExpiringRoleNotification.vue（過期通知元件,顯示即將過期的角色,整合至 Navbar.vue）
- [X] T076 [US2] 在 MemberPermissionEditor.vue 中新增延長/縮短期限 UI（依賴 T074）
- [X] T077 [US2] 在 frontend/layouts/default.vue 中整合 ExpiringRoleNotification 到 Navbar（依賴 T075）

### Tests for User Story 2

- [X] T078 [P] [US2] 建立 backend/tests/integration/RoleAssignmentAPITest.php（測試角色指派、時間性授權、延長期限的完整流程）

**Checkpoint**: 此時 User Stories 1 和 2 應該都能獨立運作

---

## Phase 5: User Story 3 - 業務人員在權限範圍內執行操作 (Priority: P2)

**Goal**: 業務人員根據被賦予的角色和權限,能夠執行被允許的操作（檢視、編輯客戶資料）,並在嘗試執行超出權限範圍的操作時收到明確的拒絕提示,所有操作被記錄

**Independent Test**: 業務人員登入（角色: 業務人員,區域限制: 華東）、檢視客戶清單（僅顯示華東區域客戶）、編輯客戶資料（成功）、嘗試匯出客戶清單（被拒絕,因角色未包含匯出權限）、所有操作被記錄在審計日誌

### Implementation for User Story 3

- [X] T079 [P] [US3] 建立 backend/app/Libraries/PermissionChecker.php（權限檢查工具類別,含條件限制驗證）
- [X] T080 [US3] 建立 backend/app/Services/AuthorizationService.php（權限驗證核心邏輯,合併多角色權限,依賴 T079）
- [X] T081 [US3] 建立 backend/app/Filters/PermissionFilter.php（權限檢查 filter,在請求進入 controller 前驗證,依賴 T080）
- [X] T082 [US3] 在 RoleController 中整合 PermissionFilter（設定所需權限: role:view, role:edit）
- [X] T083 [US3] 建立 backend/app/Services/AuditService.php（審計記錄服務,記錄操作到 audit_logs 表）
- [X] T084 [US3] 在所有 API Controllers 中整合 AuditService（記錄成功/失敗/拒絕的操作,依賴 T083）
- [X] T085 [US3] 實作條件限制動態查詢生成（在 AuthorizationService 中解析 JSON 條件並生成 SQL WHERE 子句）
- [X] T086 [US3] 實作權限快取機制（使用者登入時載入權限到 session,TTL 30分鐘,權限變更時清除）
- [X] T087 [P] [US3] 建立 frontend/composables/usePermissions.ts 中的 can() 方法（檢查使用者是否有特定權限）
- [X] T088 [P] [US3] 建立 frontend/middleware/permission.ts（路由中介軟體,保護需要權限的頁面）
- [X] T089 [US3] 在 frontend UI 元件中整合權限控制（隱藏/禁用無權限的按鈕,使用 v-if="can('permission')",符合 FR-038）
- [X] T090 [US3] 建立 frontend/pages/permissions/my-permissions.vue（我的權限頁面,顯示使用者當前所有角色和權限,使用 default.vue 布局）
- [X] T091 [US3] 實作權限變更後即時生效機制（後端權限變更時清除相關使用者的 session cache）

**Checkpoint**: 此時所有 P1 和 P2 使用者故事應該獨立運作

---

## Phase 6: User Story 4 - 稽核人員檢視操作審計記錄 (Priority: P3)

**Goal**: 稽核人員能夠檢視和查詢所有使用者的操作記錄,包括操作者、時間、操作類型、目標資料、操作結果,支援按多條件篩選和匯出報表

**Independent Test**: 稽核人員登入、選擇時間範圍（最近 7 天）、篩選操作類型（編輯客戶資料）、檢視操作記錄清單（顯示誰修改了哪些客戶的什麼欄位）、匯出為 CSV 報表

### Implementation for User Story 4

- [X] T092 [P] [US4] 建立 backend/app/Models/AuditLogModel.php（審計記錄模型,含分割表查詢邏輯）
- [X] T093 [US4] 建立 backend/app/Services/AuditLogService.php（審計記錄查詢服務,支援多條件篩選,依賴 T092）
- [X] T094 [US4] 建立 backend/app/Controllers/API/AuditLogController.php（Audit Logs API endpoints: GET /audit-logs, POST /audit-logs/export,依賴 T093）
- [X] T095 [US4] 實作審計記錄匯出功能（在 AuditLogService 中產生 CSV/Excel 報表）
- [X] T096 [US4] 實作審計記錄歸檔機制（backend/app/Commands/ArchiveAuditLogsCommand.php,90天後移至 audit_logs_archive 表）
- [X] T097 [P] [US4] 建立 frontend/pages/audit/logs.vue（審計記錄查詢頁面,含多條件篩選表單,使用 default.vue 布局）
- [X] T098 [P] [US4] 建立 frontend/components/audit/AuditLogTable.vue（審計記錄表格元件,顯示操作詳情和修改前後值）
- [X] T099 [US4] 建立 frontend/composables/useAuditLogs.ts（審計記錄 composable,提供 getAuditLogs/exportAuditLogs 方法）
- [X] T100 [US4] 在 frontend/pages/audit/logs.vue 中整合 AuditLogTable 和匯出功能（依賴 T097-T099）

**Checkpoint**: 審計記錄功能完整可用

---

## Phase 7: User Story 5 - 系統管理員設定角色階層與繼承 (Priority: P3)

**Goal**: 系統管理員能夠設定角色之間的階層關係,使上層角色自動繼承下層角色的所有權限,並可在此基礎上新增額外權限

**Independent Test**: 建立「業務人員」角色（基礎權限）、建立「業務主管」角色並設定繼承自「業務人員」、為業務主管新增額外權限（檢視報表）、驗證業務主管同時擁有業務人員權限和報表權限、修改業務人員權限時業務主管權限自動同步更新

### Implementation for User Story 5

- [X] T101 [P] [US5] 建立 backend/app/Models/RoleHierarchyModel.php（角色階層 Closure Table 模型）
- [X] T102 [US5] 在 RoleService 中實作角色階層管理（建立/更新/刪除階層關係,維護 Closure Table,依賴 T101）
- [X] T103 [US5] 更新 PermissionService 查詢邏輯以包含繼承權限（使用 Closure Table JOIN 查詢所有繼承權限）
- [X] T104 [US5] 實作角色階層變更時的權限同步機制（父角色權限變更時自動更新所有子角色的繼承權限,符合 SC-010）
- [X] T105 [US5] 在 RoleController 中新增階層相關 endpoints（GET /roles/{id}/hierarchy, PUT /roles/{id}/parent）
- [X] T106 [P] [US5] 建立 frontend/components/roles/RoleHierarchyTree.vue（角色階層樹狀結構元件,視覺化顯示繼承關係）
- [X] T107 [US5] 在 frontend/pages/roles/[id]/edit.vue 中整合 RoleHierarchyTree（允許設定父角色,依賴 T106）
- [X] T108 [US5] 更新 frontend/components/roles/PermissionSelector.vue 以顯示繼承的權限（區分直接權限和繼承權限）

**Checkpoint**: 角色階層功能完整實作

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: 改進影響多個使用者故事的部分

- [ ] T109 [P] 效能優化: 在 AuthorizationService 中實作 APCu 快取（快取角色-權限對應,TTL 30分鐘）
- [ ] T110 [P] 效能優化: 為所有資料庫表新增適當索引（依據 data-model.md 的索引策略）
- [ ] T111 [P] 效能優化: 實作 Eager Loading 避免 N+1 queries（在所有 Model 中設定關聯預載入）
- [ ] T112 [P] 安全性強化: 實作 SQL Injection 防護檢查（確保所有查詢使用 Query Builder 或 Prepared Statements）
- [ ] T113 [P] 安全性強化: 實作 XSS 防護（Nuxt 3 預設轉義,檢查所有 v-html 使用）
- [ ] T114 [P] 安全性強化: 實作 CSRF 防護（在所有 POST/PUT/DELETE endpoints 檢查 CSRF token）
- [ ] T115 [P] 建立 API 文件（整合 OpenAPI specs 到 Swagger UI,部署於 /api/docs）
- [ ] T116 [P] 繁體中文翻譯完整性檢查（確保所有 UI 文字、錯誤訊息、API 回應為繁體中文）
- [ ] T117 [P] 建立前端 E2E 測試 CI pipeline（GitHub Actions 配置,執行 Playwright 測試）
- [ ] T118 [P] 建立後端測試 CI pipeline（GitHub Actions 配置,執行 PHPUnit 測試）
- [ ] T119 [P] 實作結構化 logging（後端使用 Monolog,前端使用 Winston/Pino,含 correlation IDs）
- [ ] T120 [P] 實作 metrics collection（效能指標收集: API 回應時間、權限檢查時間、資料庫查詢時間）
- [ ] T121 執行 quickstart.md 驗證（確保開發環境設定指南準確且可執行）
- [ ] T122 程式碼品質檢查: 執行 PHPStan 靜態分析（backend/）
- [ ] T123 程式碼品質檢查: 執行 ESLint 和 Prettier（frontend/）
- [ ] T124 無障礙檢查: 執行 Lighthouse accessibility audit（確保 WCAG 2.1 AA 合規）
- [ ] T125 效能測試: 執行負載測試驗證效能目標（使用 k6,確保 p95 <200ms for read, <500ms for write）
- [ ] T126 建立生產環境部署檢查清單（依據 quickstart.md 的部署前檢查清單）
- [ ] T127 Docker 映像檔安全掃描（使用 Docker Scout 或 Trivy 掃描漏洞）

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 無相依性 - 可立即開始
- **Foundational (Phase 2)**: 依賴 Setup 完成 - 阻塞所有使用者故事
- **User Stories (Phase 3-7)**: 所有依賴 Foundational 階段完成
  - 使用者故事可以平行進行（如果有足夠人力）
  - 或按優先級順序執行（P1 → P2 → P3）
- **Polish (Phase 8)**: 依賴所有期望的使用者故事完成

### User Story Dependencies

- **User Story 1 (P1)**: Foundational (Phase 2) 完成後可開始 - 無其他故事相依
- **User Story 2 (P2)**: Foundational (Phase 2) 完成後可開始 - 可能整合 US1 但應可獨立測試
- **User Story 3 (P2)**: Foundational (Phase 2) 完成後可開始 - 可能整合 US1/US2 但應可獨立測試
- **User Story 4 (P3)**: Foundational (Phase 2) 完成後可開始 - 依賴 US3 的審計記錄功能
- **User Story 5 (P3)**: Foundational (Phase 2) 完成後可開始 - 擴展 US1 的角色管理功能

### Within Each User Story

- 測試（如包含）必須先寫並確保在實作前失敗
- Models before services
- Services before controllers/endpoints
- 核心實作 before 整合
- 故事完成 before 移至下一個優先級

### Parallel Opportunities

- Phase 1: T002-T004, T007-T009, T010-T013 可平行執行
- Phase 2: T015-T021, T023-T024, T026-T037 可平行執行
- User Story 1 測試: T038-T042 可平行執行
- User Story 1 Models: T043-T046 可平行執行
- User Story 1 Frontend stores: T053-T054 可平行執行
- User Story 1 Frontend components: T057-T059 可平行執行
- 一旦 Foundational 完成,所有使用者故事可由不同團隊成員平行開發
- Phase 8: 大部分任務（T109-T120, T122-T124, T127）可平行執行

---

## Parallel Example: User Story 1

```bash
# 一起啟動 User Story 1 的所有測試:
Task: "建立 backend/tests/contract/RoleContractTest.php"
Task: "建立 backend/tests/integration/RoleAPITest.php"
Task: "建立 backend/tests/unit/RoleServiceTest.php"
Task: "建立 frontend/tests/unit/useRoles.test.ts"
Task: "建立 frontend/tests/e2e/role-management.spec.ts"

# 一起啟動 User Story 1 的所有 models:
Task: "建立 backend/app/Models/RoleModel.php"
Task: "建立 backend/app/Models/PermissionModel.php"
Task: "建立 backend/app/Models/RolePermissionModel.php"
Task: "建立 backend/app/Models/ConditionRuleModel.php"

# 一起啟動 User Story 1 的前端元件:
Task: "建立 frontend/components/roles/RoleForm.vue"
Task: "建立 frontend/components/roles/PermissionSelector.vue"
Task: "建立 frontend/components/roles/ConditionBuilder.vue"
```

---

## Implementation Strategy

### MVP First (僅 User Story 1)

1. 完成 Phase 1: Setup
2. 完成 Phase 2: Foundational（關鍵 - 阻塞所有故事）
3. 完成 Phase 3: User Story 1
4. **停止並驗證**: 獨立測試 User Story 1
5. 如果就緒則部署/展示

### Incremental Delivery

1. 完成 Setup + Foundational → 基礎就緒
2. 新增 User Story 1 → 獨立測試 → 部署/展示（MVP!）
3. 新增 User Story 2 → 獨立測試 → 部署/展示
4. 新增 User Story 3 → 獨立測試 → 部署/展示
5. 新增 User Story 4 → 獨立測試 → 部署/展示
6. 新增 User Story 5 → 獨立測試 → 部署/展示
7. 每個故事都能在不破壞先前故事的情況下增加價值

### Parallel Team Strategy

多位開發者時:

1. 團隊一起完成 Setup + Foundational
2. 一旦 Foundational 完成:
   - 開發者 A: User Story 1（角色管理核心）
   - 開發者 B: User Story 2（角色指派）
   - 開發者 C: User Story 3（權限驗證）
   - 開發者 D: User Story 4（審計記錄）
   - 開發者 E: User Story 5（角色階層）
3. 故事獨立完成並整合

---

## Notes

- [P] 任務 = 不同檔案,無相依性
- [Story] 標籤將任務映射到特定使用者故事以便追蹤
- 每個使用者故事應該可獨立完成和測試
- 實作前驗證測試失敗
- 每個任務或邏輯組後提交
- 在任何檢查點停止以獨立驗證故事
- 避免: 模糊的任務、同檔案衝突、破壞獨立性的跨故事相依

---

## Summary

**總任務數**: 122
**使用者故事數**: 5
**MVP 範圍**: User Story 1（P1 - 系統管理員建立自訂角色與權限）

**各使用者故事的任務數**:
- User Story 1 (P1): 26 任務（含 5 個測試任務）
- User Story 2 (P2): 10 任務
- User Story 3 (P2): 13 任務
- User Story 4 (P3): 9 任務
- User Story 5 (P3): 8 任務
- Setup: 13 任務
- Foundational: 24 任務（含 4 個布局元件任務）
- Polish: 19 任務

**平行機會**:
- Setup 階段: 10 個任務可平行
- Foundational 階段: 21 個任務可平行
- User Story 1: 11 個任務可平行
- User Story 2-5: 一旦 Foundational 完成,所有故事可平行開發
- Polish 階段: 15 個任務可平行

**獨立測試標準**:
- US1: 建立角色 → 設定權限 → 驗證儲存 → API 查詢
- US2: 指派角色 → 設定有效期 → 驗證生效 → 過期自動撤銷
- US3: 登入 → 檢視被授權資料 → 執行允許操作 → 被拒絕超出權限操作 → 審計記錄
- US4: 查詢審計記錄 → 多條件篩選 → 檢視詳情 → 匯出報表
- US5: 設定繼承 → 驗證權限繼承 → 修改父角色 → 驗證自動同步

**格式驗證**: ✅ 所有任務遵循檢查清單格式（checkbox, ID, 標籤, 檔案路徑）
