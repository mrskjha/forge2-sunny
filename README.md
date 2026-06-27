# PulseDesk

Multi-tenant helpdesk ticketing system. Laravel 11 API backend + React 19 SPA frontend.

Built as a 3-sprint project (Sprint 1: scaffolding → Sprint 2: backend → Sprint 3: frontend) with a critical bugfix PR after Sprint 3.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 11, PHP 8.4, Laravel Sanctum 4.3 (token auth) |
| **Database** | MySQL 8.0+ |
| **Frontend** | React 19.2, Vite 8.1, Tailwind CSS 4.3, react-router-dom 7.18 |
| **Icons** | lucide-react |
| **CI** | GitHub Actions (PHP 8.4 + MySQL 8.0 for backend; Node 20 + Vite build for frontend) |
| **Deploy** | Vercel (frontend auto-deploy from main) |
| **AI tools used** | OpenClaw (coding agent), Hermes Agent (session orchestration, GLM-5.1 model) |

---

## Prerequisites

- PHP 8.2+ (8.4 used in CI and locally)
- Composer
- MySQL 8.0+ (or MariaDB compatible)
- Node.js 20+
- npm

---

## Quick Start

### 1. Clone

```bash
git clone https://github.com/mrskjha/forge2-sunny.git
cd forge2-sunny
```

### 2. Backend Setup

```bash
cd backend

# Install PHP dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env — set your MySQL credentials:
#   DB_CONNECTION=mysql
#   DB_HOST=127.0.0.1
#   DB_PORT=3306
#   DB_DATABASE=pulsedesk
#   DB_USERNAME=root
#   DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# (Optional) Seed demo data: 1 org, 1 admin, 2 agents, 2 customers, 12 tickets
php artisan db:seed

# Start the API server
php artisan serve --port=8000
```

The API is now running at `http://localhost:8000`.

### 3. Frontend Setup

```bash
cd frontend

# Install Node dependencies
npm install

# Start the dev server
npm run dev
```

The frontend is now running at `http://localhost:5173`.

### 4. Use the App

1. Open `http://localhost:5173` in your browser
2. Register a new organization (creates your org + first admin account)
3. You'll be redirected to the Dashboard
4. Create tickets, view ticket details, post replies

**Seeded demo accounts** (if you ran `db:seed`):
- Admin: `admin@techcorp.com` / `password`
- Agent: `agent1@techcorp.com` / `password`

---

## Running Tests

```bash
cd backend
php artisan test
```

Test suites:
- `AuthTest` — registration, login, logout
- `TicketCrudTest` — ticket CRUD operations
- `MultiTenantIsolationTest` — verifies org-scoped data isolation

---

## API Endpoints

### Public
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Register org + admin user |
| POST | `/api/login` | Login, returns Sanctum token |

### Authenticated (Bearer token)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/logout` | Logout |
| GET | `/api/tickets` | List org tickets |
| POST | `/api/tickets` | Create ticket |
| GET | `/api/tickets/{id}` | Ticket detail |
| PUT | `/api/tickets/{id}` | Update ticket |
| DELETE | `/api/tickets/{id}` | Delete (admin only) |
| POST | `/api/tickets/{id}/replies` | Add reply |

See [ARCHITECTURE.md](./ARCHITECTURE.md) for full data model and multi-tenancy details.

---

## Project Structure

```
forge2-sunny/
├── backend/                    # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── AuthController.php       # register, login, logout
│   │   │   └── TicketController.php     # ticket CRUD + replies
│   │   ├── Http/Middleware/
│   │   │   └── RoleMiddleware.php       # role-based access
│   │   └── Models/
│   │       ├── Organization.php
│   │       ├── User.php
│   │       ├── Ticket.php
│   │       ├── TicketReply.php
│   │       └── TicketActivity.php
│   ├── database/
│   │   ├── migrations/                  # 10 migration files
│   │   └── seeders/
│   │       └── DatabaseSeeder.php       # demo data
│   ├── routes/api.php                   # API route definitions
│   └── tests/Feature/                   # PHPUnit feature tests
├── frontend/                   # React 19 SPA
│   ├── src/
│   │   ├── context/AuthContext.jsx      # Auth state management
│   │   ├── lib/api.js                   # API client
│   │   ├── components/                  # Layout, Badge, ProtectedRoute
│   │   └── pages/                       # Login, Register, Dashboard, TicketDetail
│   ├── package.json
│   └── vite.config.js
├── .github/workflows/ci.yml    # GitHub Actions CI
├── ARCHITECTURE.md             # Data model, API routes, multi-tenancy
├── agent-log.md                # Sprint history (from git log + PRs)
└── SUBMISSION.md               # Submission checklist
```

---

## CI/CD

Every PR and push to `main` triggers:
- **Backend job:** PHP 8.4 + MySQL 8.0 service → `composer install` → `migrate` → `php artisan test`
- **Frontend job:** Node 20 → `npm ci` → `npm run build`
- **Vercel:** Auto-deploys frontend preview on every PR

---

## Models Used

- **OpenClaw** — coding agent for all implementation (sprints 1–3 + bugfix). Used the `forge2` agent profile.
- **Hermes Agent** — session orchestration, debugging, testing, and PR management. Model: GLM-5.1 (via z-ai provider).

---

## License

MIT
