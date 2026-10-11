# ProjectPulse

A full-stack **Laravel 12** project & task manager built as a demo for a Laravel Developer role.
Session-authenticated web app with a drag-and-drop Kanban board, plus a token-secured REST API,
a third-party API integration, policies, validation, caching and an automated test suite.

## Quick start

Requirements: PHP 8.2+, Composer, and SQLite (default) or MySQL/PostgreSQL.

```bash
./setup.sh projectpulse        # scaffolds Laravel, applies this code, migrates, seeds, runs tests
cd projectpulse && php artisan serve
```

Open http://127.0.0.1:8000 and log in with `demo@projectpulse.test` / `password`
(teammate: `sara@projectpulse.test` / `password`).

> The repo ships the **application layer** (`overlay/`) rather than the whole `vendor/` tree.
> `setup.sh` runs `composer create-project laravel/laravel`, `php artisan install:api` (Sanctum),
> then copies `overlay/` on top. Push the resulting folder to GitHub as your portfolio repo.

### MySQL instead of SQLite
`docker compose up -d`, then set `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=projectpulse`,
`DB_USERNAME=pulse`, `DB_PASSWORD=secret` in `.env` and run `php artisan migrate:fresh --seed`.

## Features

- Register / login / logout, login throttling, session regeneration
- Projects with owner + invited members (many-to-many)
- Tasks with status, priority, due date, assignee, comments
- Kanban board (HTML5 drag & drop) talking to JSON endpoints, CSRF protected, XSS-escaped rendering
- Dashboard: my open/overdue tasks, upcoming public holidays
- **Third-party API**: Nager.Date public holidays (cached 24h, timeout + retry, graceful fallback).
  Tasks due on a holiday are flagged on the board
- REST API v1 with Sanctum personal access tokens
- GitHub Actions CI

## REST API

```bash
# 1. get a token
curl -X POST http://127.0.0.1:8000/api/v1/auth/token \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"demo@projectpulse.test","password":"password"}'

# 2. use it
curl http://127.0.0.1:8000/api/v1/projects -H "Authorization: Bearer <token>" -H 'Accept: application/json'
```

| Method | Endpoint | Notes |
|---|---|---|
| POST | `/api/v1/auth/token` | issue token (throttled) |
| DELETE | `/api/v1/auth/token` | revoke current token |
| GET/POST | `/api/v1/projects` | list / create |
| GET/DELETE | `/api/v1/projects/{id}` | show / delete (owner only) |
| GET/POST | `/api/v1/projects/{id}/tasks` | list (`?status=`, `?search=`, `?per_page=`) / create |
| GET/PATCH/DELETE | `/api/v1/tasks/{id}` | show / update / delete |
| POST | `/api/v1/tasks/{id}/comments` | add comment |

## Architecture notes (talking points)

| Job requirement | Where to see it |
|---|---|
| Laravel, MVC, OOP | `app/Models`, `app/Http/Controllers`, Blade views, enums (`app/Enums`) |
| Relational DB (MySQL/PostgreSQL) | `database/migrations` - FKs, cascade deletes, composite unique + indexes, pivot table |
| REST APIs | `routes/api.php`, API Resources (`app/Http/Resources`), Sanctum, consistent 401/403/422 |
| Third-party integration | `app/Services/HolidayService.php` (+ `Http::fake` tests) |
| Security | Policies, FormRequest validation, `Rule::in(team)` for assignees, CSRF, hashed passwords, throttling, escaped output, mass-assignment whitelists |
| Performance | Eager loading + `withCount` (no N+1), DB indexes, API pagination, cached external calls |
| Clean, reusable code | One `TaskController` serves both the token API and the session-backed AJAX routes |
| Testing (PHPUnit) | `tests/Feature`, `tests/Unit` - run `php artisan test` |
| Git / deployment | CI workflow, `docker-compose.yml`, `.env`-driven config |
| Front-end | Blade + vanilla JS Kanban. Easy next step: port the board to Vue/React via Vite + Inertia |

## Ideas to extend before the interview
Queued email notifications on assignment, a `TaskObserver` activity log, Inertia + Vue board, Redis cache/queue, Deployment on a VPS with Nginx + Supervisor.

## Application email

**To:** support@islootech.com
**Subject:** Laravel Developer Application – Islamabad

> Dear IslooTech Hiring Team,
>
> I'm applying for the Laravel Developer position in Islamabad. I'm a final-year Electrical Engineering student at NUST (CEME) with hands-on full-stack experience. To demonstrate my Laravel skills I built **ProjectPulse**, a project/task manager with a Kanban board, Sanctum-secured REST API, policy-based authorization, third-party API integration and a PHPUnit suite: <GitHub link>.
>
> My resume and portfolio (https://asad-portfolio-flax-pi.vercel.app/) are attached. I would welcome the chance to discuss how I can contribute to your team.
>
> Best regards,
> Asad Hussain
