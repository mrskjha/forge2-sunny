# PulseDesk

A multi-tenant support ticket application.

## Architecture

Monorepo with two subprojects:
- **backend/** — Laravel 11 API (PHP 8.2 + MySQL)
- **frontend/** — React 19 SPA (Vite + Tailwind CSS)

## Prerequisites

- PHP 8.2+
- Composer
- Node 20+
- MySQL 8.0

## Quick Start

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Backend runs on http://localhost:8000

### Frontend

```bash
cd frontend
npm install
npm run dev
```

Frontend dev server runs on http://localhost:5173

## Running Tests

### Backend
```bash
cd backend
vendor/bin/pest
# or
php artisan test
```

### Frontend
```bash
cd frontend
npm run build
npm run lint  # if configured
```

## CI/CD

GitHub Actions workflow in `.github/workflows/ci.yml` runs on:
- Pushes to `main`
- Pull requests

It validates:
- Backend: Composer install, migrations, Pest tests
- Frontend: npm ci, build

## License

Proprietary — Internal Use