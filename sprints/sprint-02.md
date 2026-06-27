# Sprint 2 — Multi-Tenancy + Auth + Tickets CRUD

**Goal:** Core backend: multi-tenant data model, Sanctum authentication, ticket CRUD with org-scoping.

**Request to Hermes (#sprint-main, 12:50 PM):**
> "Sprint 2: Core PulseDesk features. GOAL: Multi-tenancy + Auth + Tickets CRUD (backend only, skip React UI for now).
>
> Tasks for OpenClaw: (1) Migrations for organizations, users (org_id+role), tickets, ticket_replies. (2) Sanctum auth — register/login, role-based middleware. (3) Org-scoped Tickets CRUD with a test proving cross-tenant access is blocked. (4) Seeders: 1 org, 1 admin, 2 agents, 2 customers, ~12 tickets. (5) Feature tests for auth, CRUD, and multi-tenant isolation.
>
> Communication protocol: task breakdown in #agent-coder, structured progress reports in #agent-log, CI results in #ci-cd, PR link in #human-review for my approval. Commit incrementally."

**Follow-up (12:58 PM):**
> "Quick clarification: the project is already at C:\Users\jhasu\forge2-sunny (backend/ and frontend/ subfolders, merged from PR #1). Make sure OpenClaw works directly in that path and doesn't search elsewhere."

**Issues / Tasks:**
- Migrations: `organizations`, `users` (org_id + role), `tickets`, `ticket_replies`, `personal_access_tokens` (Sanctum)
- Models: `Organization`, `Ticket`, `TicketReply` + updated `User` with org_id/role
- Sanctum auth: `POST /api/register`, `POST /api/login`, `POST /api/logout`, `RoleMiddleware`
- Tickets API: full CRUD at `/api/tickets`, org-scoped via `Ticket::forOrganization($org_id)`
- Seeder: TechCorp Inc. — 1 admin, 2 agents, 2 customers, 12 tickets with replies
- Feature tests: `AuthTest`, `TicketCrudTest`, `MultiTenantIsolationTest`

**Outcome:**
- PR #2 merged (2026-06-27, 08:10 UTC)
- All tests passing in CI, including the multi-tenant isolation test (confirms users cannot access another organization's tickets)