# Implementation Plan: RBAC 權限管理系統

**Branch**: `001-rbac-permission-management` | **Date**: 2025-10-21 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-rbac-permission-management/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

建立完整的 RBAC（Role-Based Access Control）權限管理系統,支援多層級角色管理、動態建立自訂角色與權限組合、條件式權限限制（部門/區域/客戶分群）、時間性授權、角色階層與繼承,以及完整的審計記錄與回溯機制。系統採用 CodeIgniter 4 後端搭配 Nuxt 3 前端,前端介面採用「側邊欄 + 導覽列 + 內容區域」的三區塊布局,提供簡潔清晰的使用者體驗。

## Technical Context

**Backend**
- **Language/Version**: PHP 8.1+
- **Framework**: CodeIgniter 4.4.* with CodeIgniter Shield 1.0+ (authentication/authorization framework)
- **Primary Dependencies**:
  - firebase/php-jwt ^6.10 (JWT token handling)
  - CodeIgniter Shield (base auth framework to extend)
- **Storage**: MySQL/MariaDB (via CodeIgniter ORM)
- **Testing**: PHPUnit ^10.5, PHPStan ^1.10 (static analysis), Faker ^1.23 (test data)
- **API Design**: RESTful API with JWT authentication

**Frontend**
- **Language/Version**: TypeScript 5.4+
- **Framework**: Nuxt 3.11+ (Vue 3.4+)
- **UI Library**: Nuxt UI 2.14+ (Tailwind CSS based component library)
- **State Management**: Pinia 2.1+
- **Form Validation**: VeeValidate 4.12+ with Zod schema validation
- **Testing**: Vitest 3.2+ (unit), Playwright 1.42+ (e2e)
- **Type Checking**: vue-tsc, strict TypeScript mode

**Target Platform**: Web application (responsive design for desktop/tablet/mobile)

**Project Type**: Full-stack web (frontend + backend with API contract)

**Performance Goals**:
- API response time: p95 <200ms (read), <500ms (write)
- Frontend TTI: <3s on 3G network
- Permission check: <100ms (including condition evaluation)
- Support 1000 concurrent users

**Constraints**:
- WCAG 2.1 AA accessibility compliance
- Traditional Chinese (zh-TW) for all UI and documentation
- Frontend bundle size: <200KB gzipped initial load
- Backend memory: <512MB per instance
- Audit log completeness: 100% (no missing operations)

**Scale/Scope**:
- Support 1000+ custom roles
- Support 100,000+ role assignments (users)
- Support 1,000,000+ audit log entries with <10s query time
- ~15-20 frontend pages/views
- ~30-40 backend API endpoints

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Verify compliance with `.specify/memory/constitution.md`:

- [x] **Code Quality Standards**: Design supports modular architecture, type safety, and clear documentation
  - Backend: CodeIgniter 4 MVC pattern with strict typing (PHP 8.1+), PHPStan static analysis
  - Frontend: TypeScript strict mode, component-based architecture with Nuxt 3
  - Documentation: All public APIs will have docstrings/JSDoc with parameter types and examples

- [x] **Testing Discipline**: Test strategy defined (unit, integration, contract, e2e for P1 stories); TDD planned for critical paths
  - Backend: PHPUnit for unit/integration tests; TDD for permission evaluation logic (critical path)
  - Frontend: Vitest for unit tests, Playwright for e2e tests on P1 user stories
  - Contract tests: API contract validation between frontend and backend

- [x] **User Experience Consistency**: UX patterns documented; accessibility (WCAG 2.1 AA) requirements identified; responsive design planned
  - Design system: Nuxt UI component library (Tailwind CSS based) for consistent styling
  - Accessibility: WCAG 2.1 AA compliance with keyboard navigation, ARIA labels, 4.5:1 contrast ratios
  - Responsive design: Three-layout structure (sidebar + navbar + content) adapts to mobile/tablet/desktop
  - UX patterns: Loading states, error messages, feedback mechanisms defined in spec FR-033 to FR-038

- [x] **Performance Requirements**: Performance targets defined (API latency <200ms/500ms, TTI <3s, bundle <200KB); monitoring strategy included
  - API latency targets: p95 <200ms (read), <500ms (write)
  - Permission check performance: <100ms including condition evaluation
  - Frontend: TTI <3s on 3G, bundle <200KB gzipped
  - Monitoring: API metrics (latency, error rate), database query performance tracking

- [x] **Documentation Language Standards**: This plan.md written in Traditional Chinese (zh-TW); spec.md in zh-TW; user-facing docs planned in zh-TW
  - plan.md: Traditional Chinese ✓
  - spec.md: Traditional Chinese ✓
  - All UI text, error messages, and user documentation will be in Traditional Chinese

- [x] **Frontend/Backend Separation**: Feature scope clearly defined (frontend-only, backend-only, or both with API contract); changes confined to appropriate directory
  - **Feature Scope**: Frontend + Backend (API Contract Change)
  - Backend: `backend/app/` (Models, Controllers, Services, Filters for RBAC)
  - Frontend: `frontend/` (pages, components, stores, composables for RBAC UI)
  - API Contracts: `specs/001-rbac-permission-management/contracts/` (OpenAPI specs)

- [x] **Observability and Traceability**: Logging strategy defined (structured logs, correlation IDs); metrics identified; tracing plan included; error tracking and audit logging planned
  - Structured logging: JSON format with correlation IDs for all API requests
  - Audit logging: 100% coverage of all data mutations (create/update/delete operations), security events (login, permission changes)
  - Metrics: API endpoint latency, permission check duration, role assignment count, audit log growth rate
  - Error tracking: Capture failed permission checks, invalid role assignments, authorization failures

- [x] **Development Workflow**: Feature branch strategy, PR process, incremental delivery (P1→P2→P3) planned
  - Branch: `001-rbac-permission-management`
  - Incremental delivery: P1 (role creation & assignment) → P2 (permission enforcement & business operations) → P3 (audit queries & role hierarchy)
  - Separate PRs for frontend and backend where possible; combined PR only for API contract changes

- [x] **Quality Gates**: All 8 gates (Code Quality, Testing, Security, UX, Performance, Documentation Language, Architecture, Observability) can be satisfied by implementation
  - Code Quality: ESLint/Prettier (frontend), PHPStan (backend), peer review required
  - Testing: All test suites pass, >80% coverage target
  - Security: Input validation, JWT auth, no secrets in code, permission checks before operations
  - UX: WCAG 2.1 AA, design system compliance, responsive design verification
  - Performance: Lighthouse >90, API latency targets met, bundle size limits enforced
  - Documentation Language: All zh-TW for user-facing content
  - Architecture: Changes confined to `backend/` and `frontend/`, API contracts in `specs/001-.../contracts/`
  - Observability: Structured logs, metrics, audit trails, error tracking implemented

*Document any violations in Complexity Tracking section with justification.*

## Project Structure

### Documentation (this feature)

```
specs/[###-feature]/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```
# Frontend/Backend Separation (CONSTITUTION REQUIREMENT)
backend/
├── app/
│   ├── Models/            # Database models (Role, Permission, RoleAssignment, AuditLog, ConditionRule)
│   ├── Controllers/       # API controllers (RoleController, PermissionController, AuditLogController)
│   ├── Services/          # Business logic (PermissionService, RoleService, AuditService)
│   ├── Filters/           # Authorization filters (PermissionFilter)
│   ├── Database/
│   │   └── Migrations/    # Database schema migrations for RBAC tables
│   └── Config/            # Configuration files (RBAC config)
└── tests/
    ├── unit/              # Unit tests for services and models
    ├── integration/       # Integration tests for API endpoints
    └── contract/          # API contract validation tests

frontend/
├── pages/
│   ├── roles/             # Role management pages (list, create, edit)
│   ├── permissions/       # Permission configuration pages
│   ├── users/             # User role assignment pages
│   └── audit-logs/        # Audit log query pages
├── components/
│   ├── rbac/              # RBAC-specific components (RoleForm, PermissionMatrix, ConditionBuilder)
│   ├── layout/            # Layout components (Sidebar, Navbar, ContentArea)
│   └── common/            # Shared UI components
├── stores/
│   ├── roles.ts           # Pinia store for role management state
│   ├── permissions.ts     # Pinia store for permission state
│   └── auditLogs.ts       # Pinia store for audit log queries
├── composables/
│   ├── usePermissionCheck.ts  # Composable for checking user permissions
│   └── useRoleManagement.ts   # Composable for role operations
└── tests/
    ├── unit/              # Component and composable unit tests
    └── e2e/               # End-to-end tests for P1 user stories

specs/001-rbac-permission-management/
├── contracts/             # OpenAPI/REST API contract definitions
│   ├── roles-api.yaml     # Role management endpoints
│   ├── permissions-api.yaml   # Permission endpoints
│   └── audit-logs-api.yaml    # Audit log endpoints
├── plan.md                # This file
├── spec.md                # Feature specification
├── research.md            # Phase 0 research output (to be generated)
├── data-model.md          # Phase 1 data model (to be generated)
└── quickstart.md          # Phase 1 quickstart guide (to be generated)
```

**Feature Scope**: **Frontend + Backend (API Contract Change)**

This feature requires changes to both frontend and backend directories with API contract definitions.

**Directories Modified**:
- **Backend**:
  - `backend/app/Models/` - RBAC entity models
  - `backend/app/Controllers/` - API controllers for RBAC operations
  - `backend/app/Services/` - Business logic for permission evaluation, role management
  - `backend/app/Filters/` - Authorization filter middleware
  - `backend/app/Database/Migrations/` - Database schema for RBAC tables
  - `backend/tests/` - Unit, integration, and contract tests

- **Frontend**:
  - `frontend/pages/` - RBAC management pages (roles, permissions, users, audit logs)
  - `frontend/components/` - RBAC UI components and layout structure
  - `frontend/stores/` - Pinia state management for RBAC data
  - `frontend/composables/` - Reusable permission check logic
  - `frontend/tests/` - Unit and e2e tests

- **API Contracts**:
  - `specs/001-rbac-permission-management/contracts/` - OpenAPI specifications for all RBAC endpoints

## Complexity Tracking

*Fill ONLY if Constitution Check has violations that must be justified*

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| [e.g., 4th project] | [current need] | [why 3 projects insufficient] |
| [e.g., Repository pattern] | [specific problem] | [why direct DB access insufficient] |

