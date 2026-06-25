---
name: migration
description: >
  Migrate a feature from reference-dont-change/ to the new Laravel 12 + Vue 3 architecture.
  Reads reference business logic, analyzes patterns, implements in new stack with correct
  module structure, table naming, and PostgreSQL conventions. Use when user says "migrate X",
  "implement X from reference", "pelajari X dari reference lalu implementasikan", or
  invokes /migration. Covers backend (Controller→Service→Repository→Model) and
  frontend (View→Store→Service→Axios).
---

# Migration Skill

Migrate one feature from `reference-dont-change/` to new architecture. Read reference → understand logic → implement in new stack. Never copy verbatim.

## Trigger

User says: "migrate X", "implement X from reference", "pelajari X dari reference", "buatkan X seperti reference", or invokes `/migration <module-name>`.

## Pre-flight

Before starting, confirm:
1. Which feature/module to migrate (ask if unclear)
2. Reference location: `reference-dont-change/` (read-only)
3. Target: `backend/Modules/<module>/` + `frontend/src/modules/<module>/`

## Workflow

### Step 1 — Read Reference

1. Find all reference files for the feature:
   - `reference-dont-change/app/Http/Controllers/` — controllers
   - `reference-dont-change/app/Models/` — models + table names
   - `reference-dont-change/database/migrations/` — schema
   - `reference-dont-change/resources/views/` — blade templates (UI logic)
   - `reference-dont-change/routes/web.php` — route definitions
2. Read controllers to understand request/response flow
3. Read models to understand table relationships and column names
4. Read blade views to understand UI structure and user interactions
5. **Document**: business logic summary, data flow, UI components, edge cases

### Step 2 — Map to New Architecture

Apply these mappings:

| Reference | New |
|-----------|-----|
| `m_tank` | `m_sloc` |
| `m_tank_detail` | `m_sloc_detail` |
| `id_tank` | `id_sloc` |
| Controller method | Service method → Repository method |
| Blade template | Vue 3 component (`<script setup>`) |
| `{{ }}` / `@foreach` | `v-for` / `{{ }}` (Vue syntax) |
| jQuery AJAX | Pinia store → Service → Axios |
| `DB::select()` | Repository raw query with DbCompatTrait |

### Step 3 — Backend Implementation

For each feature endpoint:

1. **Migration** — Create migration in `Modules/<module>/database/migrations/`
   - Use `m_sloc` not `m_tank`
   - `declare(strict_types=1)` on every PHP file
   - PostgreSQL: snake_case columns, no backticks, use `COALESCE` not `IFNULL`

2. **Model** — Create in `Modules/<module>/app/Models/`
   - `protected $table` explicitly set
   - No `protected array $fillable` (must be untyped)

3. **Repository Interface** — Create in `Modules/<module>/app/Repositories/`
   - Define contract methods
   - `ServiceProvider::register()` binds interface → implementation

4. **Repository Implementation** — Create in `Modules/<module>/app/Repositories/`
   - Use `DbCompatTrait` for PostgreSQL compatibility
   - `STRING_AGG` not `GROUP_CONCAT`
   - `ILIKE` not `LIKE` for case-insensitive search
   - Quote SQL aliases: `AS "camelCase"` (PostgreSQL case folding)
   - `DISTINCT ON` for deduplication (not `GROUP BY` hack)

5. **Service** — Create in `Modules/<module>/app/Services/`
   - Inject interface, not concrete class
   - No `new Class()` — use constructor injection
   - No `env()` — use `Config::get()`
   - Business logic here, not in controller

6. **Controller** — Create in `Modules/<module>/app/Http/Controllers/`
   - Inject Service via constructor
   - Use `ApiResponse` helper for responses
   - No business logic — delegate to Service
   - Catch `\Throwable` not just `\Exception`

7. **FormRequest** — Create in `Modules/<module>/app/Http/Requests/`
   - Validation rules here, not in controller

8. **Routes** — Add to `Modules/<module>/routes/api.php`
   - Under `auth:sanctum` + `plant.context` middleware
   - Prefix `api/v1`
   - Named routes before `{id}` wildcards

### Step 4 — Frontend Implementation

1. **Module entry** — `frontend/src/modules/<module>/module.js`
   - Export `{ name, routes, stores }`

2. **API service** — `frontend/src/modules/<module>/api/<feature>.js`
   - Axios calls through `@/api/axios.js`
   - No hardcoded URLs

3. **Pinia store** — `frontend/src/modules/<module>/stores/<feature>.js`
   - State, getters, actions
   - Actions call service layer

4. **Vue component** — `frontend/src/views/<module>/<Feature>View.vue`
   - `<script setup>` composition API
   - Vuetify 3 components
   - Design system: primary `#42B240`, Montserrat headings, Source Sans 3 body
   - No `console.log()` in committed code
   - No jQuery

5. **Router** — Auto-loaded via `import.meta.glob` in `core/router/index.js`

### Step 5 — Verify

1. Run `php artisan test --filter=<ModuleTest>` — backend tests pass
2. Run `npm run test:unit` — frontend tests pass
3. Run `npm run lint` — no lint errors
4. Manual check: API returns correct data, UI displays correctly
5. Check: no MySQL-specific SQL, no `m_tank` references, no `env()` in services

## PostgreSQL Quick Reference

| MySQL | PostgreSQL |
|-------|-----------|
| `IFNULL(col, val)` | `COALESCE(col, val)` |
| `GROUP_CONCAT(col)` | `STRING_AGG(col, ',')` via `dbGroupConcat()` |
| `LIKE '%x%'` | `ILIKE '%x%'` |
| `YEAR(col)` | `EXTRACT(YEAR FROM col)` |
| `CURDATE()` | `CURRENT_DATE` |
| `IF(cond, a, b)` | `CASE WHEN cond THEN a ELSE b END` |
| `` `col` `` | no backticks; double-quote if needed |
| `LEFT(str, N)` | `SUBSTRING(str FROM 1 FOR N)` |
| `ROUND(x AS type, N)` | `ROUND(CAST(x AS type), N)` |

## Stopping Condition

Feature is migrated when:
- Backend: endpoint returns correct data via API
- Frontend: UI displays and interacts correctly
- Tests pass (or test coverage added)
- No reference to `m_tank`/`m_tank_detail` in new code
- No MySQL-specific SQL in new code

## Anti-patterns to Avoid

- ❌ Copying reference code verbatim — understand then implement
- ❌ Business logic in controllers
- ❌ Validation in controllers/models
- ❌ `env()` outside config files
- ❌ `new Class()` in services
- ❌ Concrete class injection (use interfaces)
- ❌ API calls from Vue templates (use service layer)
- ❌ `console.log()` / `dd()` / `var_dump()` in committed code
