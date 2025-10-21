<!--
  SYNC IMPACT REPORT
  ==================
  Version: 1.2.0 → 1.3.0 (Minor Amendment)
  Date: 2025-10-21

  Changes:
  - Added new principle: VII. Observability and Traceability
  - Enhanced Performance Requirements principle to reference observability
  - Added Observability Gate to Quality Gates section

  Modified Principles:
  - Quality Gates: Added 8th gate - Observability Gate to verify logging, metrics, and tracing

  Added Principles:
  - VII. Observability and Traceability: Mandates comprehensive logging, metrics, distributed tracing, and error tracking

  Removed Principles: None

  Template Compatibility:
  ✅ plan-template.md: Updated Constitution Check with Observability and Traceability verification; updated to reference 8 quality gates
  ✅ spec-template.md: Templates support observability requirements, no structural changes needed
  ✅ tasks-template.md: Task organization should include observability tasks (logging, metrics, tracing, error tracking)
  ✅ checklist-template.md: Compatible with observability requirements
  ✅ agent-file-template.md: No updates needed

  Deferred Items: None

  Migration Notes:
  - All services MUST emit structured logs (JSON format recommended)
  - All API endpoints MUST have request/response logging with correlation IDs
  - All critical operations MUST emit metrics (latency, error rates, throughput)
  - Distributed tracing MUST be implemented for cross-service requests
  - Error tracking and alerting MUST be configured for production systems
-->

# CRM Project Constitution

## Core Principles

### I. Code Quality Standards

**MUST** maintain high code quality through:

- **Readability First**: Code MUST be self-documenting with clear naming, logical structure, and comments only where business logic is complex or non-obvious
- **Modular Design**: Functions and modules MUST have single, well-defined responsibilities; no function exceeds 50 lines without explicit justification
- **Type Safety**: All code MUST use strict typing (type hints in Python, strict mode in TypeScript, etc.); no `any` or equivalent escape hatches without documented rationale
- **Documentation**: All public APIs MUST have docstrings/JSDoc with parameters, return types, and usage examples; README files MUST exist for all major modules
- **Code Review**: No code merges without peer review approval; reviews MUST verify adherence to all constitution principles

**Rationale**: Maintainable code reduces technical debt, accelerates onboarding, and prevents defects. Strict standards enable confident refactoring and long-term sustainability.

### II. Testing Discipline (NON-NEGOTIABLE)

**MUST** ensure comprehensive testing coverage:

- **Test-Driven Development**: For critical business logic, tests MUST be written first, approved by reviewers, verified to fail, then implementation proceeds (Red-Green-Refactor)
- **Multi-Level Testing**:
  - Unit tests for all business logic (target: >80% coverage)
  - Integration tests for all API contracts and inter-service communication
  - Contract tests when external APIs or shared schemas are involved
  - End-to-end tests for critical user journeys (at least P1 user stories)
- **Test Quality**: Tests MUST be independent, deterministic, and fast (<5s for unit suites); flaky tests are treated as failing tests
- **Continuous Testing**: All tests MUST pass before merge; breaking tests block deployment

**Rationale**: Testing discipline prevents regressions, documents intended behavior, and enables confident continuous delivery. TDD for critical paths ensures requirements are clear before implementation begins.

### III. User Experience Consistency

**MUST** deliver uniform, accessible user experiences:

- **Design System**: All UI components MUST use shared design tokens (colors, spacing, typography) from a central design system; no one-off styling
- **Interaction Patterns**: Common actions (forms, navigation, error handling, loading states) MUST follow established patterns; deviations require UX review
- **Accessibility (WCAG 2.1 AA)**: All interfaces MUST meet WCAG 2.1 Level AA standards:
  - Keyboard navigation for all interactive elements
  - ARIA labels for screen readers
  - Color contrast ratios ≥4.5:1 for text
  - Text resizable to 200% without loss of functionality
- **Responsive Design**: All interfaces MUST adapt to mobile, tablet, and desktop viewports with no horizontal scrolling or broken layouts
- **User Feedback**: All user actions MUST provide immediate feedback (loading indicators, success/error messages, validation hints)

**Rationale**: Consistent UX reduces cognitive load, improves user satisfaction, and ensures accessibility for all users regardless of ability or device.

### IV. Performance Requirements

**MUST** meet measurable performance targets:

- **Response Times**:
  - API endpoints: p95 latency <200ms for read operations, <500ms for write operations
  - Page loads: Time to Interactive (TTI) <3s on 3G networks
  - UI interactions: All user interactions respond within 100ms (perceived as instantaneous)
- **Resource Efficiency**:
  - Backend services: <512MB memory per instance under normal load
  - Frontend bundles: Initial JS bundle <200KB gzipped; total page weight <2MB
  - Database queries: All queries optimized with indexes; no N+1 queries
- **Scalability**:
  - Horizontal scaling: Services MUST be stateless and horizontally scalable
  - Concurrent load: System MUST handle 1000 concurrent users without degradation
- **Monitoring**: All services MUST emit metrics for performance tracking (see Principle VII for observability details); dashboards MUST exist for key performance indicators

**Rationale**: Performance is a feature. Slow systems frustrate users and limit growth. Measurable targets enable proactive optimization and prevent performance regressions.

### V. Documentation Language Standards

**MUST** use Traditional Chinese (zh-TW) for all user-facing content:

- **Specifications**: All feature specifications (spec.md files) MUST be written in Traditional Chinese
- **Implementation Plans**: All implementation plans (plan.md files) MUST be written in Traditional Chinese
- **User Documentation**: All user-facing documentation (README, quickstart guides, API documentation for external consumption) MUST be in Traditional Chinese
- **UI Text**: All user interface text, messages, labels, and error messages MUST be in Traditional Chinese
- **API Responses**: All API response messages intended for end users MUST be in Traditional Chinese

**Exemptions** (MAY remain in English):
- Source code (variable names, function names, class names)
- Code comments and inline documentation (for developer readability)
- Internal developer documentation not exposed to end users
- Git commit messages and PR descriptions (internal team communication)
- Technical logs and debug output (for developer troubleshooting)

**Rationale**: Consistent language ensures accessibility for the target user base (Traditional Chinese speakers in Taiwan/Hong Kong/Macau). Clear language requirements prevent mixed-language documentation that confuses users and reduces product quality.

### VI. Frontend/Backend Separation (NON-NEGOTIABLE)

**MUST** maintain strict separation between frontend and backend codebases:

- **Directory Structure**: Frontend code MUST reside in `frontend/` directory; backend code MUST reside in `backend/` directory
- **Scope Isolation**:
  - Features scoped to frontend MUST NOT modify any code in `backend/` directory
  - Features scoped to backend MUST NOT modify any code in `frontend/` directory
  - Cross-cutting features MUST be split into separate frontend and backend tasks/PRs
- **API Contract First**:
  - All communication between frontend and backend MUST occur via documented API contracts
  - API contracts MUST be defined in `specs/[###-feature]/contracts/` before implementation
  - Changes to API contracts require updates to both frontend and backend
- **Independent Deployment**:
  - Frontend and backend MUST be independently deployable
  - Breaking changes to APIs require versioning strategy (v1, v2, etc.)
  - No shared code libraries between frontend and backend (except API contract types/schemas)
- **Testing Boundaries**:
  - Frontend tests MUST mock backend API calls (no direct backend imports)
  - Backend tests MUST NOT depend on frontend code
  - Contract tests verify API compatibility between frontend and backend

**Rationale**: Separation enables independent development and deployment, reduces coupling, allows specialized teams to work in parallel, and prevents accidental cross-contamination of concerns. Clear boundaries improve maintainability and reduce merge conflicts.

### VII. Observability and Traceability (NON-NEGOTIABLE)

**MUST** ensure comprehensive system observability and traceability:

- **Structured Logging**:
  - All services MUST emit structured logs in JSON format with standard fields (timestamp, level, service, correlation_id, message)
  - Log levels MUST be used appropriately (DEBUG for development, INFO for normal operations, WARN for recoverable issues, ERROR for failures)
  - Sensitive data (passwords, tokens, PII) MUST NOT appear in logs
  - All API requests/responses MUST be logged with correlation IDs for request tracing
- **Metrics and Monitoring**:
  - All API endpoints MUST emit metrics: request count, latency (p50, p95, p99), error rate, active requests
  - Business-critical operations MUST emit custom metrics (e.g., orders created, payments processed, user signups)
  - System health metrics MUST be collected: CPU, memory, disk usage, database connection pool
  - Metrics MUST be aggregated and visualized in dashboards (Grafana, Datadog, or equivalent)
- **Distributed Tracing**:
  - All requests MUST include correlation IDs (request_id, trace_id) propagated across service boundaries
  - Cross-service calls MUST be traceable end-to-end using distributed tracing (OpenTelemetry, Jaeger, or equivalent)
  - Database queries and external API calls MUST be included in traces
- **Error Tracking and Alerting**:
  - All errors MUST be tracked with full context (stack trace, request details, user context)
  - Error tracking system MUST be integrated (Sentry, Rollbar, or equivalent)
  - Critical errors MUST trigger alerts to on-call engineers
  - Error rates exceeding thresholds MUST trigger automated alerts
- **Audit Trails**:
  - All data mutations (create, update, delete) MUST be logged with user ID, timestamp, and changed fields
  - Security-sensitive operations (login, permission changes, data exports) MUST be audited
  - Audit logs MUST be immutable and retained per compliance requirements

**Rationale**: Observability enables rapid debugging, proactive issue detection, and data-driven optimization. Traceability ensures accountability, supports compliance, and accelerates incident response. Without observability, production issues become black boxes.

## Development Workflow

**MUST** follow disciplined development practices:

- **Branching Strategy**: Feature branches created from main; branch naming follows `###-feature-name` convention with optional `-frontend` or `-backend` suffix for separation clarity
- **Commit Hygiene**: Commits are atomic, have descriptive messages, and reference issue numbers; history is clean and revert-safe
- **Pull Requests**: All changes via PR with description, test evidence, and constitution compliance checklist
- **Code Freeze**: No direct commits to main/master; all changes through reviewed PRs
- **Documentation First**: For new features, spec.md MUST be approved before implementation begins; spec.md MUST be in Traditional Chinese per Principle V
- **Incremental Delivery**: Features developed as independently testable user stories (P1, P2, P3); MVP (P1 story) deployed first
- **Separation Enforcement**: PRs that modify both frontend/ and backend/ directories MUST be justified in PR description; prefer separate PRs unless implementing API contract changes

**Rationale**: Structured workflow prevents integration chaos, enables safe rollbacks, and ensures all stakeholders review changes before production.

## Quality Gates

**MUST** pass all gates before deployment:

1. **Code Quality Gate**:
   - Linter/formatter passes (zero warnings)
   - Type checker passes with strict mode
   - Code review approval from at least one peer
   - No commented-out code or debug statements

2. **Testing Gate**:
   - All test suites pass (unit, integration, contract, e2e)
   - Code coverage meets targets (>80% for new code)
   - No flaky tests in the suite
   - Performance tests meet latency/throughput targets

3. **Security Gate**:
   - Dependency vulnerability scan passes (no high/critical CVEs)
   - Secrets/credentials not committed to version control
   - Input validation for all user-provided data
   - Authentication/authorization verified for protected endpoints

4. **UX Gate**:
   - Accessibility audit passes (WCAG 2.1 AA)
   - Design system compliance verified
   - Responsive design tested on mobile/tablet/desktop
   - User feedback mechanisms implemented

5. **Performance Gate**:
   - Lighthouse score >90 (Performance, Accessibility, Best Practices)
   - API latency targets met in staging environment
   - Bundle size limits not exceeded
   - Database query performance profiled

6. **Documentation Language Gate**:
   - All spec.md files in Traditional Chinese (zh-TW)
   - All plan.md files in Traditional Chinese (zh-TW)
   - All user-facing documentation in Traditional Chinese (zh-TW)
   - UI text and API user messages in Traditional Chinese (zh-TW)

7. **Architecture Gate**:
   - Frontend changes confined to `frontend/` directory (unless API contract change)
   - Backend changes confined to `backend/` directory (unless API contract change)
   - API contract changes documented in `specs/[###-feature]/contracts/`
   - No direct imports between frontend and backend codebases
   - Contract tests verify frontend/backend compatibility

8. **Observability Gate**:
   - Structured logging implemented with correlation IDs
   - Metrics instrumentation added for new endpoints/operations
   - Distributed tracing context propagated across service calls
   - Error tracking integrated with proper error context
   - Audit logging added for data mutations and security-sensitive operations
   - Dashboards updated with new metrics/endpoints

**Failure Handling**: Any gate failure blocks merge/deployment; fix required before proceeding; no "merge and fix later" exceptions.

## Governance

This constitution supersedes all other development practices and guidelines.

**Amendment Process**:
- Proposed changes documented with rationale and impact analysis
- Team review and approval required (consensus or majority vote)
- Version bumped per semantic versioning (MAJOR for principle removals/redefinitions, MINOR for additions, PATCH for clarifications)
- Migration plan created if changes affect existing code
- All dependent templates and documentation updated

**Compliance Review**:
- All PRs MUST verify compliance with this constitution
- Violations require documented justification in Complexity Tracking table (plan.md)
- Unjustified complexity accumulation triggers refactoring priority
- Constitution reviewed quarterly for relevance and effectiveness

**Runtime Development Guidance**: For day-to-day development workflows, refer to templates in `.specify/templates/` for detailed execution instructions.

**Version**: 1.3.0 | **Ratified**: 2025-10-21 | **Last Amended**: 2025-10-21
