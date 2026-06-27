# Architecture — PulseDesk

Multi-tenant helpdesk ticketing system. Laravel 11 API + React 19 SPA.

---

## System Overview

```
┌─────────────────────┐     ┌──────────────────────────┐     ┌─────────────┐
│   React 19 SPA      │     │   Laravel 11 API          │     │  MySQL 8/9  │
│   (Vite, Tailwind 4)│────▶│   (Sanctum token auth)    │────▶│             │
│                     │     │                           │     │             │
│  Login              │  JSON│  /api/register            │     │ organizations│
│  Register           │◀───▶│  /api/login               │     │ users       │
│  Dashboard          │     │  /api/tickets (CRUD)      │     │ tickets     │
│  TicketDetail       │     │  /api/tickets/{id}/replies│     │ ticket_replies│
│                     │     │                           │     │ ticket_activities│
└─────────────────────┘     └──────────────────────────┘     └─────────────┘
        Vercel                        GitHub Actions CI              ☁️
```

---

## Data Model

### organizations
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint (PK) | |
| `name` | string | Org display name |
| `slug` | string (unique) | URL-safe identifier |
| `created_at`, `updated_at` | timestamps | |

### users
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint (PK) | |
| `name` | string | |
| `email` | string (unique) | |
| `password` | string | Bcrypt hashed |
| `org_id` | FK → organizations | **Tenant scope** |
| `role` | enum | `admin`, `agent`, `customer` |
| `remember_token` | string | Laravel session |
| timestamps | | |

### tickets
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint (PK) | |
| `org_id` | FK → organizations | **Tenant scope** |
| `requester_id` | FK → users | Who created the ticket |
| `assignee_id` | FK → users (nullable) | Agent assigned |
| `subject` | string | |
| `description` | text | |
| `status` | enum | `open`, `in_progress`, `resolved`, `closed` |
| `priority` | enum | `low`, `medium`, `high`, `urgent` |
| `tags` | JSON (nullable) | Array of tag strings |
| `sla_due_at` | datetime (nullable) | SLA deadline |
| timestamps | | |

### ticket_replies
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint (PK) | |
| `ticket_id` | FK → tickets (cascade delete) | |
| `user_id` | FK → users | Author of reply |
| `body` | text | |
| timestamps | | |

### ticket_activities
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint (PK) | |
| `ticket_id` | FK → tickets | |
| `user_id` | FK → users | Who made the change |
| `action` | string | Human-readable description |
| timestamps | | |

### personal_access_tokens (Laravel Sanctum)
Standard Sanctum token table for API authentication.

---

## Entity Relationships

```
Organization 1───∗ User (org_id)
Organization 1───∗ Ticket (org_id)
User 1───∗ Ticket (requester_id)
User 1───∗ Ticket (assignee_id, nullable)
Ticket 1───∗ TicketReply (ticket_id)
Ticket 1───∗ TicketActivity (ticket_id)
User 1───∗ TicketReply (user_id)
```

---

## API Routes

### Public (no auth)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/register` | Create org + first admin user. Body: `name`, `email`, `password`, `org_name`, `org_slug` |
| `POST` | `/api/login` | Authenticate. Returns Sanctum token. Body: `email`, `password` |

### Protected (requires `Authorization: Bearer <token>`)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/logout` | Revoke current token |
| `GET` | `/api/tickets` | List all tickets in user's org (with requester, assignee) |
| `POST` | `/api/tickets` | Create ticket. Body: `subject`, `description`, `priority`, `tags[]`, `assignee_id` |
| `GET` | `/api/tickets/{id}` | Show ticket detail (with replies.user, activities.user) |
| `PUT/PATCH` | `/api/tickets/{id}` | Update ticket (status, priority, assignee, etc.) |
| `DELETE` | `/api/tickets/{id}` | Delete ticket (**admin only**) |
| `POST` | `/api/tickets/{id}/replies` | Add reply. Body: `body` |

---

## Multi-Tenancy Approach

**Strategy: Shared database, tenant isolation via `org_id` column.**

Every tenant-aware table (`users`, `tickets`) has an `org_id` foreign key to `organizations`. All data access is scoped:

```php
// Ticket model
public function scopeForOrganization($query, $orgId)
{
    return $query->where('org_id', $orgId);
}

// TicketController — every query method
$tickets = Ticket::forOrganization($request->user()->org_id)
    ->with(['requester', 'assignee'])
    ->get();
```

**How it works:**
1. On register, an `Organization` is created and the first user becomes its `admin`
2. The user's `org_id` is set at creation; it never changes
3. Every ticket query is scoped through `forOrganization($request->user()->org_id)`
4. A user from Org A **cannot** read, create, update, or delete tickets in Org B — the query simply never returns them (`findOrFail()` throws 404)

**Role-based access:**
- **admin** — full CRUD on all org tickets, can delete
- **agent** — can update/reply to tickets
- **customer** — can create tickets, reply to own tickets only

Enforced via `RoleMiddleware` and inline checks in `TicketController`.

---

## Authentication Flow

1. Client sends `POST /api/register` or `POST /api/login` with `Accept: application/json` header
2. Laravel Sanctum validates credentials, returns a plain-text API token
3. Frontend stores token in `localStorage`
4. Every subsequent request includes `Authorization: Bearer <token>`
5. `POST /api/logout` revokes the token server-side

---

## Frontend Architecture

```
frontend/src/
├── main.jsx              # React entry, renders <App>
├── App.jsx               # Router (react-router-dom v7)
├── index.css             # Tailwind v4 import (@import "tailwindcss")
├── context/
│   └── AuthContext.jsx   # Auth state: user, token, login, logout
├── lib/
│   └── api.js            # Centralized fetch wrapper with auth header
├── components/
│   ├── Layout.jsx        # Nav bar + page wrapper
│   ├── ProtectedRoute.jsx # Redirects to /login if not authed
│   └── Badge.jsx         # StatusBadge, PriorityBadge, SlaBadge
└── pages/
    ├── Login.jsx         # Email/password form
    ├── Register.jsx      # Org + user registration form
    ├── Dashboard.jsx     # Stat cards + ticket table + create modal
    └── TicketDetail.jsx  # Ticket info + status changer + reply thread + activity feed
```

---

## CI/CD Pipeline

**GitHub Actions (`.github/workflows/ci.yml`):**

| Job | Runner | Steps |
|-----|--------|-------|
| **backend** | ubuntu-latest + MySQL 8.0 | PHP 8.4 → composer install → key:generate → migrate → `php artisan test` |
| **frontend** | ubuntu-latest | Node 20 → `npm ci` → `npm run build` |

**Deployment:** Vercel auto-deploys frontend from `main` branch on every merge.

---

## Tech Stack Summary

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | 11.x |
| Auth | Laravel Sanctum | 4.3 |
| PHP | 8.4 | (8.2+ required) |
| Frontend | React | 19.2 |
| Build tool | Vite | 8.1 |
| CSS | Tailwind CSS | 4.3 |
| Router | react-router-dom | 7.18 |
| Icons | lucide-react | 1.21 |
| Database | MySQL | 8.0 (CI) / 9.5 (local) |
| CI | GitHub Actions | |
| Deploy | Vercel | |
