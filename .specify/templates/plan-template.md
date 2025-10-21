# Implementation Plan: [FEATURE]

**Branch**: `[###-feature-name]` | **Date**: [DATE] | **Spec**: [link]
**Input**: Feature specification from `/specs/[###-feature-name]/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

[Extract from feature spec: primary requirement + technical approach from research]

## Technical Context

<!--
  ACTION REQUIRED: Replace the content in this section with the technical details
  for the project. The structure here is presented in advisory capacity to guide
  the iteration process.
-->

**Language/Version**: [e.g., Python 3.11, Swift 5.9, Rust 1.75 or NEEDS CLARIFICATION]  
**Primary Dependencies**: [e.g., FastAPI, UIKit, LLVM or NEEDS CLARIFICATION]  
**Storage**: [if applicable, e.g., PostgreSQL, CoreData, files or N/A]  
**Testing**: [e.g., pytest, XCTest, cargo test or NEEDS CLARIFICATION]  
**Target Platform**: [e.g., Linux server, iOS 15+, WASM or NEEDS CLARIFICATION]
**Project Type**: [single/web/mobile - determines source structure]  
**Performance Goals**: [domain-specific, e.g., 1000 req/s, 10k lines/sec, 60 fps or NEEDS CLARIFICATION]  
**Constraints**: [domain-specific, e.g., <200ms p95, <100MB memory, offline-capable or NEEDS CLARIFICATION]  
**Scale/Scope**: [domain-specific, e.g., 10k users, 1M LOC, 50 screens or NEEDS CLARIFICATION]

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Verify compliance with `.specify/memory/constitution.md`:

- [ ] **Code Quality Standards**: Design supports modular architecture, type safety, and clear documentation
- [ ] **Testing Discipline**: Test strategy defined (unit, integration, contract, e2e for P1 stories); TDD planned for critical paths
- [ ] **User Experience Consistency**: UX patterns documented; accessibility (WCAG 2.1 AA) requirements identified; responsive design planned
- [ ] **Performance Requirements**: Performance targets defined (API latency <200ms/500ms, TTI <3s, bundle <200KB); monitoring strategy included
- [ ] **Documentation Language Standards**: This plan.md written in Traditional Chinese (zh-TW); spec.md in zh-TW; user-facing docs planned in zh-TW
- [ ] **Frontend/Backend Separation**: Feature scope clearly defined (frontend-only, backend-only, or both with API contract); changes confined to appropriate directory
- [ ] **Observability and Traceability**: Logging strategy defined (structured logs, correlation IDs); metrics identified; tracing plan included; error tracking and audit logging planned
- [ ] **Development Workflow**: Feature branch strategy, PR process, incremental delivery (P1→P2→P3) planned
- [ ] **Quality Gates**: All 8 gates (Code Quality, Testing, Security, UX, Performance, Documentation Language, Architecture, Observability) can be satisfied by implementation

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
<!--
  ACTION REQUIRED: This project uses frontend/backend separation (Constitution Principle VI).
  Specify which directories this feature will modify based on its scope.
-->

```
# Frontend/Backend Separation (CONSTITUTION REQUIREMENT)
backend/
├── src/
│   ├── models/        # Database models and schemas
│   ├── services/      # Business logic services
│   ├── api/           # API endpoints and routes
│   └── lib/           # Backend utilities
└── tests/
    ├── contract/      # API contract tests
    ├── integration/   # Backend integration tests
    └── unit/          # Backend unit tests

frontend/
├── src/
│   ├── components/    # UI components
│   ├── pages/         # Page-level components
│   ├── services/      # API client and frontend services
│   └── lib/           # Frontend utilities
└── tests/
    ├── integration/   # Frontend integration tests
    └── unit/          # Frontend unit tests

specs/[###-feature]/
└── contracts/         # API contracts (interface between frontend and backend)
```

**Feature Scope**: [Specify one of the following]
- **Frontend Only**: This feature only modifies code in `frontend/` directory
- **Backend Only**: This feature only modifies code in `backend/` directory
- **Frontend + Backend (API Contract Change)**: This feature requires changes to both `frontend/` and `backend/` directories and includes API contract definition in `specs/[###-feature]/contracts/`

**Directories Modified**: [List specific directories this feature will touch, e.g., `frontend/src/components/`, `backend/src/api/`]

## Complexity Tracking

*Fill ONLY if Constitution Check has violations that must be justified*

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| [e.g., 4th project] | [current need] | [why 3 projects insufficient] |
| [e.g., Repository pattern] | [specific problem] | [why direct DB access insufficient] |

