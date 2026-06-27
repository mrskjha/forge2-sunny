# Sprint 1 — Project Scaffolding

**Goal:** Set up monorepo with Laravel 11 backend, React 19 + Vite + Tailwind frontend, and a CI workflow.

**Request to Hermes (#sprint-main, 11:49 AM):**
> "We're building PulseDesk - multi-tenant Laravel 11 + MySQL + React 19 + Vite + Tailwind support ticket app. Plan sprint 1 (project scaffolding) and dispatch it to OpenClaw. Requirements: Laravel in backend/, React in frontend/, basic GitHub Actions CI workflow, report progress in #agent-coder."

**Issues / Tasks:**
- Initialize Laravel 11 backend (Sanctum, PHPUnit, MySQL config)
- Initialize React 19 + Vite + Tailwind frontend
- Set up GitHub Actions CI (backend job: composer install + migrate + test; frontend job: npm install + build)
- Both projects scaffolded inside `backend/` and `frontend/` subfolders (not repo root)

**Follow-up (12:15 PM):**
> "Sprint 1 scaffolding looks complete. Please have OpenClaw open a PR (not merge to main directly), report PR link in #human-review, and CI result in #ci-cd."

**Outcome:**
- PR #1 opened and merged (2026-06-27, 07:06 UTC)
- CI initially failed due to PHP version mismatch (composer.lock required PHP ≥8.4, workflow pinned 8.2) — fixed by bumping to PHP 8.4
- Second CI failure: missing `bootstrap/cache` and `storage` directories — fixed by creating them before `composer install`
- Final result: CI green (backend + frontend), PR merged to main