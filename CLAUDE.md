# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Context

- **Project:** EODS Refactoring — PT Ecogreen Oleochemicals legacy Laravel MVC+Blade → Laravel 12 API + Vue 3 SPA
- **Legacy (READ-ONLY):** `reference-dont-change/` — source of truth for all business logic
- **Not a greenfield** — every feature already exists in reference. Migrate it, don't recreate.

---

## Dev Commands

**Start all servers (Windows):**
```
start-servers.bat
```

**Backend** (`cd backend`):
```
php artisan serve --port=8000
php artisan test
php artisan test --filter TestClassName
php artisan test --filter "TestClass::method"
php artisan migrate
php artisan module:make ModuleName    # follow with: composer dump-autoload
```

**Frontend** (`cd frontend`):
```
npm run dev          # Vite dev server (port 5173)
npm run build
npm run test:unit
npm run test:unit -- --testNamePattern="test name"
npm run lint         # ESLint + oxlint (auto-fix)
```

**Dev URLs:**
- Main backend: `http://localhost:8000`
- Reference backend (READ-ONLY): `http://localhost:8001`
- Frontend: `http://localhost:5173`

---

## Environment

- XAMPP on Windows — MySQL runs on port **3309** (not 3306)
- Tests use SQLite in-memory (configured in `phpunit.xml`)
- `TANKFARM_API_URL` / `TANKFARM_API_TOKEN` in `backend/.env` for external tank farm API
- `declare(strict_types=1)` required on **every** PHP file — including generated factories and migrations

**Frontend env vars** (`frontend/.env`):
- `VITE_API_URL` — backend base URL
- `VITE_LABEL_BASE_URL` — external label print service URL

---

## Architecture

### Backend — Modular Laravel (`nwidart/laravel-modules`)

Modules live at `backend/Modules/<ModuleName>/`. Each module follows:
```
app/
  Http/Controllers/   # thin — delegates to Service
  Http/Requests/      # ALL validation here
  Providers/          # registers interface→concrete bindings + loads routes
  Repositories/       # DB queries; Contracts/ holds the interface
  Services/           # business logic; Contracts/ holds the interface
routes/api.php        # all routes under middleware(['auth:sanctum', 'plant.context']) prefix('api/v1')
```

**Module namespace pattern:**
- Folder `m-adjustment` + `"name": "Adjustment"` → namespace `Modules\Adjustment`
- Folder `ts-blending` + `"name": "TsBlending"` → namespace `Modules\TsBlending`
- The prefix (`m-`, `ts-`, `trace-`) is dropped; remainder is PascalCase in the namespace.

**Module prefixes and DB connections:**
- `m-*` — master data → uses `mysql`
- `ts-*` — transaction/balance ledger → uses `eudr_ts` exclusively
- `trace-*` — traceability → uses `eudr_ts`
- No prefix — core modules (Admin, Auth, Dashboard, Shared, Inquiry) → uses `mysql`

**Two database connections:**
- `mysql` — main app data (users, master data, plant/tank/material tables)
- `eudr_ts` — transaction/balance ledger (`t_balance_header`, etc.)

**Current modules (backend `Modules/` ↔ frontend `resources/js/modules/`):**
```
Core:    Admin  Auth  Dashboard  Inquiry  Shared
Master:  m-adjustment  m-manufacturer  m-material  m-plant
         m-quantifier  m-storage  m-supplier  m-tank
Ledger:  ts-blending  ts-package  ts-raw  ts-rmreport
         ts-shipment  ts-stock  ts-transfer  ts-tsreport  ts-wip
Trace:   trace-backward  trace-forward
```

**Shared module** (`backend/Modules/Shared/`):
- `PlantContextService` + `PlantContextMiddleware` (`plant.context`) — resolves plant from request into `code_3` format; null = all plants
- `PlantScopeMiddleware` (`plant.scope`) — like `plant.context` but **rejects** requests with no plant
- `PlantFilterTrait` — repository-level plant scoping
- `AuditService`, `PeriodLockService` — cross-module concerns

**Plant context flow:**
- Frontend sends plant via `X-Plant-Id` header (preferred), `id_plant` query param, or `id_plant` body field
- Middleware sets `$request->get('plant_context')['plant_code']` (resolved `code_3`) and merges `_plant_code` into request
- `null` plant_code = "all plants" (no filter applied)

**`ApiResponse` helper** — lives at `backend/app/Helpers/ApiResponse.php` (app-level, `App\Helpers\ApiResponse`):
```php
ApiResponse::success($data, $message, $code)
ApiResponse::error($message, $code, $errors)
ApiResponse::paginated($data, $total, $page, $perPage, $message)
```

**Route ordering rule:** Exact named routes MUST come before `{id}` wildcard routes in `routes/api.php`.

### Frontend — Vue 3 + Pinia + Vuetify 3

Source lives at `frontend/resources/js/` (not `src/`). Alias `@` maps to this directory.

**Module system:** each module at `resources/js/modules/<name>/` exports a `module.js`:
```js
export default { name, routes: [...], stores: [...] }
```
Modules are dynamically registered into `core/router/index.js` via `createAppRouter(moduleRoutes)`.

**Within each module:**
```
views/      # page-level Vue components
stores/     # Pinia store (defineStore, composition API)
services/   # axios calls via @/api/axios — one file per module, exports plain object
components/ # optional sub-components
```

**Auth:** Sanctum Bearer token stored in `localStorage('auth_token')`, injected by axios interceptor in `@/api/axios.js`.

**Axios instance:** `@/api/axios.js` — uses `VITE_API_URL`, auto-injects `Authorization: Bearer` header, redirects to login on 401.

---

## Key Conventions

### Naming

| Artifact | Convention | Example |
|----------|------------|---------|
| PHP Controller | PascalCase + suffix | `AdjustmentController` |
| PHP Service | PascalCase + suffix | `AdjustmentService` |
| PHP Repository | PascalCase + suffix | `AdjustmentRepository` |
| PHP Form Request | PascalCase + suffix | `StoreAdjustmentHeaderRequest` |
| PHP Model | Singular PascalCase | `Adjustment` |
| DB Table | snake_case plural | `t_adjustment_headers` |
| DB Column | snake_case | `created_at`, `plant_code` |
| API Route | kebab-case | `/api/v1/master/adjustment` |
| Vue Component | PascalCase | `AdjustmentList.vue` |
| Git Branch | kebab-case | `feature/PROJ-42-adjustment-fix` |

### Architecture Chain
```
Backend  : Controller → Service → Repository → Model
Frontend : View → Store → Service → Axios
```

### Code Standards

- PHP: `declare(strict_types=1)` on every file (including factories and migrations)
- PHP: max 200 lines/class, max 20 lines/method, typed properties & return types
- PHP: use `Config::get()` in Service classes — NOT `env()` directly
- PHP: `protected $fillable` without type hints (Eloquent parent doesn't declare types)
- Vue: composition API + `<script setup>`, handle loading/error/empty states
- No `console.log()`, `dd()`, `var_dump()`, `die()` in committed code
- No TODO comments in code going to review

### Critical Restrictions

- ❌ Don't create logic not present in `reference-dont-change/`
- ❌ Don't modify existing table schema — create a new migration
- ❌ Don't hardcode credentials, URLs, or env values (including fallback `|| 'url'` in frontend)
- ❌ Don't call API directly from Vue template — use service layer
- ❌ Don't use `document.documentElement.*` for Vuetify state — use `useTheme()`, `useLayout()`
- ❌ Don't use jQuery or unapproved libraries
- ❌ Don't merge to `main` without CI passing and 1 reviewer
- ❌ Don't execute Phase 2 without explicit user confirmation ("ya / ok")
- ✅ Inject interface, not concrete class (ServiceProvider binds interface → concrete)
- ✅ Logo assets from `.design/assets/` → copy to `frontend/public/`
- ✅ Axios baseURL: `import.meta.env.VITE_API_URL` — without fallback string
- ✅ Repository binding in `ServiceProvider::register()`, not `boot()`

---

## Testing Requirements

- Every **API endpoint** MUST have a Feature test
- Every **Service class method** MUST have a Unit test
- Use **factories** for test data — never real/seeded data
- Test naming: `test_it_[does_something]` (snake_case, descriptive)
- Minimum **80% coverage** for new code
- Tests must cover: happy path, error cases, edge cases, unauthorized access
- Pinia stores: explicit `import { ref, computed } from 'vue'` — no auto-import in Vitest

---

## Git Workflow

```
Branches:
  feature/PROJ-{id}-{short-desc}
  fix/PROJ-{id}-{short-desc}
  refactor/{short-desc}

Commit format:
  [feat]      add adjustment import endpoint
  [fix]       correct plant context null handling
  [test]      add feature tests for blending API
  [refactor]  extract validation to FormRequest
  [docs]      update business-process reference

Rules:
  - CI must pass before merge
  - Minimum 1 reviewer approval
  - No force-push to dev or main
```

---

## Frontend Component Checklist

**Before building any new Vue component, read in this order:**

1. `ai-assisted laravel-vue onboarding/.design/DESIGN-SYSTEM.md` — brand, colors, fonts, spacing (PRIMARY)
2. `ai-assisted laravel-vue onboarding/.template/TEMPLATE.md` — find matching component pattern (card, table, form, dialog)
3. `ai-assisted laravel-vue onboarding/.docs/TEMPLATE-ADAPTATION.md` — what's automatic via theme vs needs manual change

**When adapting from template:**
- ✅ Copy Vuetify props and layout structure
- ✅ Colors (`primary`, `success`, `error`) are automatic via Vuetify theme
- ❌ Don't copy `@core/`, `@layouts/`, `@images/`, `@core-scss/` import paths
- ❌ Don't copy Materio utility classes (`text-base`, `text-high-emphasis`)
- ❌ Don't copy emoji — design system forbids it
- ❌ Don't copy SCSS imports from `@core-scss/template/`

If `.design/` and `.template/` conflict → **`.design/` always wins.**

---

## Additional Guides

The `.claude/` folder contains detailed guides:

| File | Purpose |
|------|---------|
| `.claude/CLAUDE.md` | **Standard conventions** — naming, architecture, code standards, testing, git, frontend refs, anti-patterns, lessons learned. Auto-loaded every session via `@` import below. |
| `.claude/graphify.md` | Graphify session-start check + commands reference. Auto-loaded every session via `@` import below. |
| `.claude/business-process.md` | Domain reference — document numbers, movement types, plant codes, WIP flows |
| `.claude/executor.md` | Migration guide — reference → new stack patterns (refactor/eksekusi modes) |
| `.claude/auditor.md` | Audit checklist — backend, frontend, domain accuracy |

---

@.claude/CLAUDE.md
@.claude/graphify.md
