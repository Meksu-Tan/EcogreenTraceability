# AGENTS.md — Agent Guide for EODS Refactoring

> **Read `.claude/CLAUDE.md` before any task — it overrides everything.**

---

## Mandatory AI Tools (Every Execution)

> Berlaku untuk: **Claude Code, OpenCode, Antigravity, semua AI agent**

### Caveman Mode (WAJIB)

- Semua respons HARUS dalam **Caveman mode full** — terse, drop filler/article/pleasantries
- Pattern: `[thing] [action] [reason]. [next step].`
- Drop: articles (a/an/the), filler (just/really/basically), pleasantries (sure/certainly/happy to), hedging
- Fragments OK. Short synonyms (big not "extensive", fix not "implement a solution for")
- Technical terms exact. Code blocks unchanged.
- Off hanya jika user ketik: `"stop caveman"` atau `"normal mode"`

### Graphify — Knowledge Graph (WAJIB sebelum grep/read)

- Jika `graphify-out/graph.json` ada → tanya graphify SEBELUM grep/glob/read source code
- `graphify query "where is X defined"` — cari lokasi kode
- `graphify explain "concept"` — pahami arsitektur
- `graphify path "A" "B"` — relasi antar file
- Baca file source langsung HANYA untuk: modifikasi spesifik, debug detail, atau graphify kurang detail

---

## What Is This?

Legacy Laravel MVC+Blade → Laravel 12 REST API + Vue 3 SPA migration. **Every feature already exists in `reference-dont-change/`** — study it for business logic, but never copy code verbatim.

## Quick Reference

| Service | URL | Purpose |
|---------|-----|---------|
| Frontend | `http://localhost:5173` | Vue 3 SPA (Vite dev server) |
| Backend | `http://localhost:8000` | Laravel 12 API |
| Reference | `http://localhost:8001` | Read-only legacy backend (DO NOT modify) |

## Dev Commands

**Start all (Windows):**
```
start-servers.bat
```

**Backend** (`cd backend`):
```
php artisan serve --port=8000
php artisan test                                          # all tests (SQLite :memory:)
php artisan test --filter TestClassName                   # single class
php artisan test --filter "TestClass::method"             # single method
php artisan module:make ModuleName && composer dump-autoload  # new module
```

**Frontend** (`cd frontend`):
```
npm run dev              # Vite dev server
npm run build            # production build
npm run test:unit        # Vitest
npm run test:unit -- --testNamePattern="test name"  # single test
npm run lint             # oxlint + eslint (auto-fix)
```

## Architecture

```
Backend  : Controller → Service → Repository → Model
Frontend : View → Store → Service → Axios
```

### Backend Modules (`backend/Modules/`)

- `nwidart/laravel-modules` — each module in its own folder
- Module structure: `Http/Controllers`, `Http/Requests`, `Services`, `Repositories`, `Providers`
- **Repository binding MUST be in `ServiceProvider::register()`, NOT `boot()`**
- **Injection rule:** inject interfaces, not concrete classes. No `new Class()` in services.
- All routes go under `auth:sanctum` + `plant.context` middleware, prefix `api/v1`
- Route ordering: named routes before `{id}` wildcards

### Module Prefix → DB Connection

| Prefix | DB Connection | Purpose |
|--------|---------------|---------|
| `m-*` | `mysql` (PostgreSQL) | Master data |
| `ts-*` | `eudr_ts` (PostgreSQL) | Transaction/balance ledger |
| `trace-*` | `eudr_ts` (PostgreSQL) | Traceability |
| (none) | `mysql` (PostgreSQL) | Core modules (Admin, Auth, Dashboard, Shared, Inquiry) |

**DB driver is `pgsql` for all connections since Phase 3 migration. See `.claude/POSTGRESQL-MIGRATION.md`.**

### Frontend Modules (`frontend/resources/js/modules/`)

- Each module exports `module.js` with `{ name, routes, stores }`
- Auto-loaded via `import.meta.glob` in `core/router/index.js`
- Source at `resources/js/` — alias `@` maps here

## Critical Table Name Mapping

When migrating from reference, **always substitute**:

| Reference | New Stack |
|-----------|-----------|
| `m_tank` | `m_sloc` |
| `m_tank_detail` | `m_sloc_detail` |
| `id_tank` | `id_sloc` |

❌ Never use `m_tank` or `m_tank_detail` in new code.

## Environment

- PostgreSQL 17 — port **5432**, database `eudr_dev`, user `eudr_app`, password `Ecogreen123!`
- psql CLI: `C:\Program Files\PostgreSQL\17\bin\psql.exe` (not in system PATH — use full path)
- PHP 8.3 required — `declare(strict_types=1)` on every PHP file (including generated)
- Tests use SQLite in-memory (`phpunit.xml` configures this)
- Boolean env vars: use `filter_var(env('VAR', false), FILTER_VALIDATE_BOOLEAN)`, not bare `env()`
- `env()` only in `config/*.php` — services use `Config::get()`

## PostgreSQL — Query Conventions

- Use `DbCompatTrait` (`Modules/Shared/Traits/DbCompatTrait.php`) for DB-specific SQL
- `COALESCE()` not `IFNULL()` (works in both, but IFNULL is MySQL-only)
- `STRING_AGG()` not `GROUP_CONCAT()` — use `$this->dbGroupConcat()`
- `ILIKE` not `LIKE` for case-insensitive search in PostgreSQL
- `EXTRACT(YEAR FROM col)` not `YEAR(col)`
- `CURRENT_DATE` not `CURDATE()`
- No backtick identifiers — use double quotes if needed (avoid entirely)
- No `COLLATE utf8mb4_unicode_ci`
- Use `CASE WHEN ... THEN ... ELSE ... END` not `IF(cond, a, b)`

## Restrictions

- ❌ No business logic in controllers — use Service layer
- ❌ No validation in controllers/models — use FormRequest
- ❌ No hardcoded URLs/credentials — use `import.meta.env.*` (frontend) / `Config::get()` (backend)
- ❌ No `window.location.href` in Axios interceptor — let router guard handle 401
- ❌ No `console.log()`, `dd()`, `var_dump()`, `die()` in committed code
- ❌ No jQuery or unapproved libraries
- ❌ No `document.documentElement.*` for Vuetify state — use `useTheme()`, `useLayout()`
- ❌ No modifying already-run migrations — create new ones
- ❌ No MySQL-specific functions (`IFNULL`, `GROUP_CONCAT`, `DATE_FORMAT`, `IF(cond)`, backticks)

## Testing Requirements

- Every API endpoint needs a Feature test
- Every Service method needs a Unit test (mock Repository + Strategies via Mockery)
- Use factories for test data — no real/seeded data
- Test naming: `test_it_[does_something]`
- Minimum 80% coverage for new code

## Git Conventions

- Branch: `feature/PROJ-{id}-{desc}`, `fix/PROJ-{id}-{desc}`, `refactor/{desc}`
- Commit types: `[feat]`, `[fix]`, `[test]`, `[refactor]`, `[docs]`, `[chore]`, `[progress]`
- `[progress]` commits MUST include `AB#{id}` for all completed tasks
- No force-push to `dev` or `main`. No merge to `main` without CI + 1 review.

## Documentation Priority

| Priority | Path (from project root) | Purpose |
|----------|--------------------------|---------|
| 1 (wins) | `.claude/CLAUDE.md` | Conventions (always overrides) |
| 2 | `.claude/.docs/ARCHITECTURE.md` | System patterns |
| 3 | `.claude/.design/DESIGN-SYSTEM.md` | Brand tokens, Vuetify theme |
| 4 | `.claude/.template/` | UI reference (READ-ONLY, never modify) |
| 5 | `.claude/POSTGRESQL-MIGRATION.md` | DB migration guide |
| 6 | `.claude/.context/` | Session memory & decisions |

## Anti-Patterns

- ❌ `env()` in Service → breaks after `config:cache`
- ❌ `new Class()` in Service → use constructor injection
- ❌ Concrete class injection → inject interface
- ❌ API call in Vue component → go through store → service → axios
- ❌ `protected array $fillable` in Eloquent model → must be untyped
- ❌ MySQL-specific SQL in repositories → use DbCompatTrait

---

@.claude/CLAUDE.md
@.claude/.docs/ARCHITECTURE.md
