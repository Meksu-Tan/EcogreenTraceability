# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> **Full conventions live at `.claude/CLAUDE.md` — it always wins over this file.**
> Read `.claude/CLAUDE.md` before any task. AGENTS.md is the multi-agent variant.

---

## What Is This Repo

Legacy Laravel MVC+Blade → Laravel 12 REST API + Vue 3 SPA migration for PT Ecogreen Oleochemicals (EODS).
`reference-dont-change/` is the read-only source of truth for all business logic — study it, never copy verbatim.

## Dev Commands

**Start everything (Windows):** `start-servers.bat`

**Backend** (`cd backend`):
```
php artisan serve --port=8000
php artisan test
php artisan test --filter TestClassName
php artisan test --filter "TestClass::method"
php artisan module:make ModuleName && composer dump-autoload
php artisan migrate
```

**Frontend** (`cd frontend`):
```
npm run dev
npm run build
npm run test:unit
npm run test:unit -- --testNamePattern="test name"
npm run lint
```

**URLs:** Frontend `localhost:5173` · Backend `localhost:8000` · Reference (read-only) `localhost:8001`

**psql:** `"C:\Program Files\PostgreSQL\17\bin\psql.exe"` — not in PATH, use full path.

## Architecture

```
Backend  : Controller → Service → Repository → Model
Frontend : View → Store → Service → Axios
```

Backend uses `nwidart/laravel-modules`. Each module lives at `backend/Modules/<Name>/`.

**Module prefix → DB connection:**

| Prefix | Connection | Driver |
|--------|-----------|--------|
| `m-*` | `mysql` | pgsql (PostgreSQL 17) |
| `ts-*` | `eudr_ts` | pgsql |
| `trace-*` | `eudr_ts` | pgsql |
| (none) | `mysql` | pgsql |

**Module namespace:** drop folder prefix, PascalCase remainder. `m-adjustment` → `Modules\Adjustment`. `ts-blending` → `Modules\TsBlending`.

**Frontend modules** live at `frontend/resources/js/modules/<name>/`. Each exports `module.js` with `{ name, routes, stores }`. Auto-loaded via `import.meta.glob` in `core/router/index.js`. Alias `@` maps to `resources/js/`.

## Critical Rules

- `declare(strict_types=1)` on **every** PHP file including generated migrations and factories.
- Repository binding in `ServiceProvider::register()` — never `boot()`.
- Inject interfaces not concrete classes. No `new Class()` in Services.
- All validation in `FormRequest` — never in Controller or Model.
- `env()` only in `config/*.php`. Services use `Config::get()`.
- Boolean env: `filter_var(env('VAR', false), FILTER_VALIDATE_BOOLEAN)`.
- No `m_tank`/`m_tank_detail` in new code — use `m_sloc`/`m_sloc_detail`.
- PostgreSQL only: `COALESCE` not `IFNULL`, `STRING_AGG` not `GROUP_CONCAT`, `ILIKE` not `LIKE`. Use `DbCompatTrait` (`Modules/Shared/Traits/DbCompatTrait.php`).
- Axios baseURL: `import.meta.env.VITE_API_URL` — no fallback string.
- 401 in Axios: clear token + `Promise.reject` — never `window.location.href`.
- Vuetify state via `useTheme()`/`useLayout()` — never `document.documentElement.*`.
- `useRouter`/`useRoute` must be explicitly imported from `vue-router` — not auto-imported.

## Key Shared Utilities

| Utility | Location | Purpose |
|---------|----------|---------|
| `ApiResponse` | `app/Helpers/ApiResponse.php` | Standardized JSON responses |
| `DbCompatTrait` | `Modules/Shared/Traits/DbCompatTrait.php` | PostgreSQL-safe query helpers |
| `PlantContextMiddleware` | `Modules/Shared/` | Resolves plant from `X-Plant-Id` header |
| Axios instance | `frontend/resources/js/api/axios.js` | Bearer token injection + 401 handling |
| Auth store | `frontend/resources/js/stores/auth.js` | Login/logout/fetchMe/token |

## Testing

- PHPUnit feature tests use SQLite `:memory:` (configured in `phpunit.xml`) — not PostgreSQL.
- Factories must include all columns — update after every migration.
- Pinia stores in Vitest need explicit `import { ref, computed } from 'vue'` — no auto-import.
- Mock Repository + Strategies (Mockery) in Service unit tests.

## Documentation Map

| Path | Purpose |
|------|---------|
| `.claude/CLAUDE.md` | **Primary conventions** (always wins) |
| `.claude/.docs/ARCHITECTURE.md` | System patterns, module setup steps |
| `.claude/.design/DESIGN-SYSTEM.md` | Ecogreen brand tokens, Vuetify theme |
| `.claude/.template/` | READ-ONLY Materio UI reference |
| `.claude/POSTGRESQL-MIGRATION.md` | MySQL→PostgreSQL migration (Phases 1–5 done) |
| `.claude/.docs/sprints/` | Sprint details and roadmap |
| `reference-dont-change/` | Legacy source of truth for business logic |
