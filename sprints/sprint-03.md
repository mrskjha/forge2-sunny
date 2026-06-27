# Sprint 3 — React Frontend UI

**Goal:** Build a working React UI connected to the backend API — auth flow, dashboard, and ticket detail view.

**Request to Hermes (#sprint-main):**
> "Sprint 2 backend is done — multi-tenancy, auth, and ticket CRUD are working. Now build the React frontend: a login/register flow, a dashboard showing ticket stats and a filterable ticket list, and a ticket detail page with status management and a reply thread. Connect everything to the existing /api routes."

**Issues / Tasks:**
- `AuthContext` for token storage and auth state management
- `ProtectedRoute` wrapper redirecting unauthenticated users to login
- Login + Register pages
- `Layout` component (nav bar with brand, dashboard link, user info, logout)
- Dashboard: stat cards (Total/Open/etc.) + ticket list table
- Ticket Detail page: info panel, status dropdown, SLA badge, Conversation/History tabs, reply box
- `lib/api.js` — centralized API client using Sanctum token auth
- Bug discovered during testing: PHP 8.4 PDO SSL constant deprecation warnings were leaking into JSON API responses, breaking frontend `JSON.parse()` — fixed by suppressing deprecation output in the API layer

**Outcome:**
- PR #3 merged (2026-06-27, 08:48 UTC) — initial React UI
- PR #4 merged (2026-06-27, 09:42 UTC) — Auth + Dashboard + Ticket Detail completed
- PR #5 merged (2026-06-27, 09:42 UTC) — PHP 8.4 deprecation warning fix
- Full flow working end-to-end: register → login → dashboard → ticket detail → reply