# Sprint 4 — Critical UI Bugfix

**Goal:** Fix broken UI styling and API error handling discovered during end-to-end testing.

**Bug discovered (screenshot evidence in evidence/screenshots/):**
Login page rendered as raw, unstyled HTML — no Tailwind styles applied. Submitting the login form threw:
> `Unexpected token 'T', "The page c"... is not valid JSON`

**Request to Hermes (#sprint-main):**
> "UI shows error on login: 'Unexpected token T, The page c... is not valid JSON' - this means the frontend's login API call is getting an HTML error page instead of JSON. Please dispatch to OpenClaw to diagnose and fix this."

**Root cause analysis & fixes:**
1. **Tailwind v3→v4 mismatch (critical):** `index.css` used Tailwind v3 directives (`@tailwind base; @tailwind components; @tailwind utilities;`), but the project had Tailwind v4 (`@tailwindcss/postcss`) installed. Tailwind v4 silently ignores v3 directives — zero utility classes were generated, so the entire UI rendered as unstyled HTML. **Fix:** replaced with `@import "tailwindcss";` (v4 syntax).
2. **Missing `Accept: application/json` header (critical):** `api.js` only sent `Content-Type: application/json`. Without an `Accept` header, Laravel returned HTML error pages on validation failures instead of JSON — the frontend's `JSON.parse()` then crashed on the HTML, producing the "Unexpected token" error seen in the screenshot. **Fix:** added the header to all API requests.
3. **UI polish (non-breaking):** Added `lucide-react` icons, gradient stat cards on the dashboard, SLA badge component, Conversation/History tabs on ticket detail, and an activity timeline.

**Verification:** Full flow re-tested end-to-end in the browser (login → dashboard → create ticket → ticket detail → post reply) — all working with proper styling.

**Outcome:**
- PR #6 merged (2026-06-27, 10:34 UTC)
- All CI checks passed: frontend (12s), backend (49s), GitGuardian security scan