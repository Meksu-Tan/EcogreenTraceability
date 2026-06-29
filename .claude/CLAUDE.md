# CLAUDE.md — Project Convention Reference

> **Instruksi untuk AI Agent:** Baca file ini sebelum mengerjakan task apapun.
> Semua rules di sini bersifat WAJIB dan tidak boleh dilanggar.
> Last updated: 2026-06-18

---

## 1. Project Context

- **Project:** EODS Refactoring — PT Ecogreen Oleochemicals legacy Laravel MVC+Blade → Laravel 12 API + Vue 3 SPA
- **Legacy (READ-ONLY):** `reference-dont-change/` — source of truth for all business logic
- **Not a greenfield** — every feature already exists in reference. Migrate it, don't recreate.

**Using `reference-dont-change/`:**
- Run at `http://localhost:8001` (read-only reference backend)
- **DO:** Study existing business logic, validation rules, field mappings, calculation flows
- **DO NOT:** Copy code verbatim — understand the logic, then implement in new stack with proper architecture
- When unsure how a feature should work, check the reference first

**Reference → New Stack: Table Name Mapping**

The reference uses different table names for tank/storage data. **Always substitute immediately** when migrating or refactoring from the reference:

| Reference (`reference-dont-change/`) | New Stack |
|---------------------------------------|-----------|
| `m_tank` | `m_sloc` |
| `m_tank_detail` | `m_sloc_detail` |
| `id_tank` (PK/FK) | `id_sloc` |
| `id_tank_tail` (PK/FK in detail) | — (table flattened into `m_sloc`) |

- ❌ Never use `m_tank` or `m_tank_detail` in new migrations, queries, or models
- ✅ All new code must reference `m_sloc` and `m_sloc_detail` only
- ✅ If a reference query JOINs `m_tank`, rewrite to JOIN `m_sloc` on `id_sloc`
- ✅ If a reference query JOINs `m_tank_detail`, flatten the logic — the detail table no longer exists separately

---

## 2. Project Stack

| Layer       | Technology                                          |
|-------------|-----------------------------------------------------|
| Backend     | Laravel 12 + Composer (PHP 8.3, strict_types)       |
| Frontend    | Vue 3 + Vite + JavaScript + Pinia + Vuetify 3       |
| Database    | PostgreSQL 17 (dev: port 5432, database `eudr_dev`) |
| Cache       | Redis 7                                             |
| Styling     | Tailwind CSS v3 + Vuetify theme                     |
| Testing     | PHPUnit (backend), Vitest (frontend)                |
| CI/CD       | GitLab CI → auto-deploy ke staging saat merge ke `dev` |

---

## 3. Dev Commands

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

**Quick command reference:**

| Task | Command |
|------|---------|
| Run single test class | `php artisan test --filter TestClassName` |
| Run single test method | `php artisan test --filter "TestClass::method"` |
| Create new module | `php artisan module:make ModuleName && composer dump-autoload` |
| Run frontend tests | `npm run test:unit -- --testNamePattern="test name"` |
| Auto-fix linting issues | `npm run lint` |

---

## 4. Environment

- PostgreSQL 17 on Windows — port **5432**, database `eudr_dev`, user `eudr_app`, password `Ecogreen123!`
- psql CLI: `C:\Program Files\PostgreSQL\17\bin\psql.exe` (not in system PATH — use full path)
- PHP **8.3** required (check with `php -v`)
- Tests use SQLite in-memory (configured in `phpunit.xml`)
- `TANKFARM_API_URL` / `TANKFARM_API_TOKEN` in `backend/.env` for external tank farm API
- `declare(strict_types=1)` required on **every** PHP file — including generated factories and migrations

**Backend .env setup:**
```
cp backend/.env.example backend/.env
cd backend && php artisan key:generate
```

**Frontend env vars** (`frontend/.env`):
- `VITE_API_URL` — backend base URL (no fallback string — do NOT use `|| 'url'` pattern)
- `VITE_LABEL_BASE_URL` — external label print service URL

---

## 5. Architecture

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
- `m-*` — master data → uses `mysql` connection (PostgreSQL `eudr_dev`)
- `ts-*` — transaction/balance ledger → uses `eudr_ts` exclusively (PostgreSQL `eudr_dev`)
- `trace-*` — traceability → uses `eudr_ts` (PostgreSQL `eudr_dev`)
- No prefix — core modules (Admin, Auth, Dashboard, Shared, Inquiry) → uses `mysql` (PostgreSQL `eudr_dev`)

**Database connections (all PostgreSQL 17 since Phase 3):**
- `mysql` — driver: `pgsql`, main app data (users, master data, plant tables)
- `eudr_ts` — driver: `pgsql`, transaction/balance ledger (`t_balance_header`, etc.)
- `eudr_ts_pg` — driver: `pgsql`, separate connection used for pgsql-only migrations at `database/migrations/pgsql/`
- See `.claude/POSTGRESQL-MIGRATION.md` for full migration details (Phases 1–5 complete, Phase 6 planned Sept 2026)
- `DbCompatTrait` (`Modules/Shared/Traits/DbCompatTrait.php`) — dual-DB helpers for GROUP_CONCAT, DATE_FORMAT, JSON ops

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

**Migration naming pattern:**
- Format: `YYYY_MM_DD_HHMMSS_action_table.php`
- Examples: `2026_06_15_120000_create_adjustments_table.php`, `2026_06_15_120015_add_status_to_transfers_table.php`
- Always use `php artisan make:migration <name>` to auto-generate timestamp

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

**Axios instance:** `@/api/axios.js` — uses `VITE_API_URL`, auto-injects `Authorization: Bearer` header, redirects to login on 401 (via router, NOT `window.location.href`).

**Architecture chain:**
```
Backend  : Controller → Service → Repository → Model
Frontend : View → Store → Service → Axios
```

---

## 6. Naming Conventions

| Artifact | Convention | Example |
|----------|------------|---------|
| PHP Controller | PascalCase + suffix | `AdjustmentController` |
| PHP Model | Singular PascalCase | `Adjustment` |
| PHP Service | PascalCase + suffix | `AdjustmentService` |
| PHP Repository | PascalCase + suffix | `AdjustmentRepository` |
| PHP Form Request | PascalCase + suffix | `StoreAdjustmentHeaderRequest` |
| DB Table | snake_case plural | `t_adjustment_headers` |
| DB Column | snake_case | `created_at`, `plant_code` |
| API Route | kebab-case | `/api/v1/master/adjustment` |
| Vue Component | PascalCase | `AdjustmentList.vue` |
| Vue Hook/Composable | camelCase + `use` | `useAdjustmentList.js` |
| Git Branch | kebab-case | `feature/PROJ-42-adjustment-fix` |

---

## 7. Code Standards

- PHP: `declare(strict_types=1)` on **every** file (including generated factories and migrations)
- PHP: max **200 lines/class**, max **20 lines/method**
- PHP: typed properties and return types on all methods
- PHP: `protected $fillable` without type hints (Eloquent parent doesn't declare types)
- PHP: use `Config::get()` in Service classes — NOT `env()` directly (breaks after `config:cache`)
- PHP: only `config/*.php` files may call `env()`
- PHP: use `filter_var(env('VAR', false), FILTER_VALIDATE_BOOLEAN)` for boolean env flags
- Vue: composition API + `<script setup>` only — no Options API, no class components
- Vue: always handle **loading**, **error**, and **empty** states in UI components
- No `console.log()`, `dd()`, `var_dump()`, `die()` in committed code
- No `TODO` comments in code going to review

---

## 8. Restrictions — WAJIB DIPATUHI

> ❌ = Tidak boleh dilakukan dalam kondisi apapun

- ❌ Jangan buat logic yang tidak ada di `reference-dont-change/`
- ❌ Jangan modifikasi migration yang sudah berjalan — buat migration baru
- ❌ Jangan hardcode credentials, URL, atau environment values (termasuk fallback `|| 'url'` di frontend)
- ❌ Jangan gunakan `m_tank` atau `m_tank_detail` di kode baru
- ❌ Jangan panggil API langsung dari Vue template/component — gunakan service layer
- ❌ Jangan gunakan `document.documentElement.*` atau DOM API untuk mengubah Vuetify state — gunakan `useTheme()`, `useLayout()`
- ❌ Jangan gunakan `window.location.href` di Axios interceptor untuk redirect — biarkan router guard yang handle
- ❌ Jangan panggil `fetchMe()` di public/guest routes
- ❌ Jangan gunakan jQuery atau library yang tidak disetujui
- ❌ Jangan merge ke `main` tanpa CI passing dan minimal 1 reviewer approval
- ❌ Jangan force-push ke `dev` atau `main`
- ❌ Jangan tinggalkan komentar `TODO` di kode yang masuk review
- ❌ Jangan `new Class()` langsung di Service — gunakan constructor injection via DI
- ❌ Jangan inject concrete class di Service — inject interface (ServiceProvider binds interface → concrete)
- ❌ Jangan eksekusi Phase 2 tanpa konfirmasi eksplisit dari user ("ya / ok")
- ✅ Repository binding WAJIB di `ServiceProvider::register()`, bukan `boot()`
- ✅ Logo assets dari `.claude/.design/assets/` → copy ke `frontend/public/`
- ✅ Axios baseURL: `import.meta.env.VITE_API_URL` — tanpa fallback string

---

## 9. Testing Requirements

- Setiap **API endpoint** WAJIB punya Feature test
- Setiap **Service class method** WAJIB punya Unit test
- Gunakan **factories** untuk test data — jangan gunakan data real/seeded
- Penamaan test: `test_it_[does_something]` (snake_case, deskriptif)
- Minimum **80% coverage** untuk kode baru
- Test harus cover: happy path, error cases, edge cases, unauthorized access
- Pinia stores: explicit `import { ref, computed } from 'vue'` — tidak ada auto-import di Vitest
- Unit test Service class harus menggunakan mock Repository + Strategies (Mockery)

---

## 10. Git Workflow

```
Branches:
  feature/PROJ-{id}-{short-desc}     → fitur baru
  fix/PROJ-{id}-{short-desc}         → bug fix
  refactor/{short-desc}              → refactoring tanpa fitur baru
  
Commit types (wajib lowercase):
  [feat]      fitur baru yang ditambahkan ke codebase
  [fix]       bug fix
  [test]      menambah atau update test
  [refactor]  refactor tanpa mengubah behavior
  [docs]      perubahan dokumentasi saja
  [chore]     dependency, config, tooling
  [progress]  update progress sprint — WAJIB diikuti AB#ID (lihat §9 documentation 21)
  
Commit format:
  [type] deskripsi singkat dalam bahasa Indonesia atau Inggris
  
Contoh:
  [feat] add department CRUD with soft delete
  [fix] correct unique constraint on section code per department
  [test] add feature tests for DepartmentController
  [progress] sprint-01 brief-1 done — Organization module backend AB#292 AB#293 AB#294
  
AB#ID Convention:
  - Format: AB#{work-item-id} (contoh: AB#292)
  - Prefix `AB#` dikenali Azure DevOps secara otomatis → link commit ke work item
  - Wajib dicantumkan di commit `[progress]` untuk semua Issue + Task IDs yang selesai
  - Contoh: `[progress] ... AB#292 AB#293 AB#294`
  
Rules:
  - Jangan merge ke main tanpa CI passing dan 1 reviewer approval
  - Jangan force-push ke branch bersama
  - Jangan skip membuat migration untuk setiap perubahan schema
  - Commit [progress] WAJIB menyertakan AB#ID semua task yang selesai
  - Link Azure Repo ke Azure DevOps Project untuk auto-linking
```

---

## 11. Anti-Patterns — JANGAN LAKUKAN INI

```php
// ❌ BURUK — business logic di Controller, validasi di Controller
class AdjustmentController {
    public function store(Request $request) {
        $data = $request->validate([...]); // validasi di controller
        $adjustment = Adjustment::create($data); // langsung model
        Mail::to($user)->send(new Notification()); // logic di controller
        return response()->json($adjustment);
    }
}

// ✅ BENAR — delegasi ke Service, validasi di FormRequest
class AdjustmentController {
    public function store(StoreAdjustmentHeaderRequest $request): JsonResponse {
        $result = $this->adjustmentService->create($request);
        return ApiResponse::success($result, 'Adjustment created');
    }
}
```

```php
// ❌ BURUK — env() langsung di Service (breaks after config:cache)
class AdjustmentService {
    public function isEnabled(): bool {
        return env('FEATURE_ENABLED', false); // ❌
    }
}

// ✅ BENAR — Config::get() di Service
class AdjustmentService {
    public function isEnabled(): bool {
        return Config::get('adjustment.feature_enabled', false); // ✅
    }
}
```

```php
// ❌ BURUK — inject concrete class
class AdjustmentService {
    public function __construct(
        private EloquentAdjustmentRepository $repo // ❌ concrete
    ) {}
}

// ✅ BENAR — inject interface
class AdjustmentService {
    public function __construct(
        private AdjustmentRepositoryInterface $repo // ✅ interface
    ) {}
}
```

```js
// ❌ BURUK — API call langsung di Vue component
function handleSubmit() {
    const res = await fetch('/api/v1/adjustments', {...}); // langsung di component
}

// ✅ BENAR — via service layer → store → component
import { useAdjustmentStore } from '@/modules/m-adjustment/stores/adjustmentStore';
const store = useAdjustmentStore();
store.create(payload); // store calls service, service calls axios
```

```js
// ❌ BURUK — Axios interceptor pakai window.location
axios.interceptors.response.use(null, error => {
    if (error.response?.status === 401) {
        window.location.href = '/login'; // ❌ full page reload, infinite loop risk
    }
});

// ✅ BENAR — clear token, reject, biarkan router guard handle
axios.interceptors.response.use(null, error => {
    if (error.response?.status === 401) {
        localStorage.removeItem('auth_token');
        return Promise.reject(error); // ✅ router beforeEach handles redirect
    }
});
```

---

## 12. Frontend Component Building Guide

**Before building any new Vue component, follow this mandatory process:**

### Step 1: Read Design System First

Open **`.claude/.design/DESIGN-SYSTEM.md`** — PRIMARY reference for:
- Brand colors, typography, spacing, radius
- Visual foundation tokens
- Ecogreen color palette mapping

### Step 2: Find UI Template Pattern

Open **`.claude/.template/TEMPLATE.md`** — find matching component pattern:

| Component Type | Location in `.claude/.template/` |
|----------------|----------------------------------|
| CRUD list pages | `frontend/resources/js/pages/user-management/` |
| Create/Edit forms | `frontend/resources/js/pages/forms/` |
| Data tables | `frontend/resources/js/components/tables/` |
| Dialogs/Modals | `frontend/resources/js/components/dialogs/` |
| Stat/KPI cards | `frontend/resources/js/components/cards/` |
| Charts | `frontend/resources/js/pages/charts/` |
| Layouts/Sidebar | `frontend/resources/js/@core/components/` |

### Step 3: Adapt Using Bridge Document

Read **`.claude/.docs/TEMPLATE-ADAPTATION.md`** — understand what's automatic vs requires manual changes:

**✅ Boleh:**
- Copy Vuetify props dan layout structure
- Gunakan warna `primary`, `success`, `error` (otomatis via Vuetify theme)
- Ikuti component patterns dari `.claude/.template/`
- Semua perubahan state Vuetify (theme, drawer, dll) WAJIB melalui Vuetify composables (`useTheme()`, `useLayout()`)
- Logo assets WAJIB dicopy dari `.claude/.design/assets/` ke `frontend/public/` — akses via `/logo-*` path, jangan import via JS bundler
  - `logo-symbol.svg` → favicon
  - `logo-stacked.jpg` → sidebar/login
  - `logo-horizontal.jpg` → app bar

**❌ Jangan:**
- Copy import paths `@core/`, `@layouts/`, `@images/`, `@core-scss/`
- Copy Materio-specific utility classes (`text-base`, `text-high-emphasis`)
- Copy emoji — design system melarang emoji
- Copy SCSS imports dari `@core-scss/template/`
- Gunakan `document.documentElement.classList` atau DOM API apapun untuk mengubah Vuetify state

**⚠️ Conflict Resolution:** Jika `.design/` bertentangan dengan `.template/` → **`.design/` selalu menang**

---

## 13. Documentation Reference Structure

**Urutan baca wajib sebelum mengerjakan task apapun:**

### Layer 0: Session Memory

1. **[MEMORY.md](.context/MEMORY.md)** ⭐ BACA PERTAMA — session memory: sprint status, feedback, lessons (junction ke Claude auto-memory)
   - Baca `.context/MEMORY.md` dulu, lalu file memory terkait yang tercantum di dalamnya
   - `.context/` adalah junction ke `~/.claude/projects/{slug}/memory/` — personal memory per developer
   - Lihat `.claude/.wiki/20-ai-project-enhancement.md` untuk detail memory system

### Layer 1: Convention Files

2. **[CLAUDE.md](.claude/CLAUDE.md)** — daily conventions, naming, restrictions, testing rules
3. **[ARCHITECTURE.md](.claude/.docs/ARCHITECTURE.md)** — system architecture, technical patterns, module structure
4. **[DESIGN-SYSTEM.md](.claude/.design/DESIGN-SYSTEM.md)** — Ecogreen brand tokens, Vuetify theme mapping, visual specs
5. **[TEMPLATE-ADAPTATION.md](.claude/.docs/TEMPLATE-ADAPTATION.md)** — how to adapt `.claude/.template/` components to design system
6. **[POSTGRESQL-MIGRATION.md](.claude/POSTGRESQL-MIGRATION.md)** — MySQL→PostgreSQL migration guide (Phases 1–5 done)

### Layer 2: Sprint Blueprint

7. **[sprint-roadmap.md](.claude/.docs/sprints/sprint-roadmap.md)** — dependency graph between sprints
8. **[sprint-XX.md](.claude/.docs/sprints/)** — active sprint details (replace XX with sprint number)

### Layer 3: Collaboration & Skills

9. **[COLLABORATION.md](.claude/.docs/COLLABORATION.md)** — executor workflow, token economics, delegate rules
10. **[Skills (see table below)](.claude/.skills/)** — process skills sesuai jenis task

| Task Type | Skill to Read |
|-----------|---------------|
| New feature implementation | `.claude/.skills/test-driven-development/SKILL.md` |
| Bug fix / failing tests | `.claude/.skills/systematic-debugging/SKILL.md` |
| Complex multi-file task | `.claude/.skills/writing-plans/SKILL.md` |
| Before claiming completion | `.claude/.skills/verification-before-completion/SKILL.md` |
| Status/state transitions | `.claude/.skills/atomic-state-transition/SKILL.md` |
| Before creating PR | `.claude/.skills/finishing-a-development-branch/SKILL.md` |
| Brainstorming ideas | `.claude/.skills/brainstorming/SKILL.md` |
| Parallel agent dispatching | `.claude/.skills/dispatching-parallel-agents/SKILL.md` |
| Code review feedback | `.claude/.skills/receiving-code-review/SKILL.md` |
| Requesting code review | `.claude/.skills/requesting-code-review/SKILL.md` |

> ✅ **Priority rule:** `CLAUDE.md` always wins over any skill guidelines. Project-specific rules cannot be overridden by general methodology skills.

### Additional Documentation

| Path (from project root) | Purpose |
|--------------------------|---------|
| `.context/` | Session memory junction → Claude auto-memory (gitignored) |
| `.claude/.docs/` | Architecture & planning, sprint docs |
| `.claude/.design/` | Brand & UI assets (design tokens, logos) |
| `.claude/.template/` | READ-ONLY UI references (Materio Vuetify Admin) — jangan modifikasi |
| `.claude/.wiki/` | Training modules & workflow guides |
| `.claude/.skills/` | Agent process skills library (TDD, debugging, review, etc.) |
| `.agents/` | Multi-agent workflow definitions (root level) |
| `.claude/` | Main config: `CLAUDE.md`, `settings.json`, `POSTGRESQL-MIGRATION.md`, `business-process.md` |

### Reading Order Rule

**Priority stack:** `CLAUDE.md` always wins over any skill guidelines. Project-specific rules cannot be overridden by general methodology skills.

---

## 14. Pre-Flight Checklist (Before Coding Any Feature)

Selalu verifikasi sebelum mulai:

1. ✅ Service layer sudah ada untuk modul ini
2. ✅ Tidak ada hardcoded env-dependent values (`import.meta.env.*` / `Config::get()`) — termasuk fallback `|| 'url'` di axios config
3. ✅ `declare(strict_types=1)` di SETIAP file PHP (termasuk generated factories dan migrations)
4. ✅ Methods di bawah 20 baris
5. ✅ Vue theme/layout state via Vuetify composables only (`useTheme()`, `useLayout()`) — tidak ada `document.documentElement.*`
6. ✅ Unit tests direncanakan untuk service classes
7. ✅ Interface diinjeksi, bukan concrete class
8. ✅ Repository binding di `ServiceProvider::register()`, bukan `boot()`
9. ✅ ExampleTest diupdate/dihapus jika web routes dikosongkan di sprint awal
10. ✅ UserFactory mencakup semua kolom baru agar test data konsisten

---

## 15. Convention Audit Checklist (Akhir Setiap Fitur)

Sisipkan audit ini setiap selesai fitur — bukan hanya akhir sprint:

- [ ] Naming convention sesuai §6
- [ ] Business logic ada di Service, bukan Controller
- [ ] Validasi ada di FormRequest, bukan Controller atau Model
- [ ] Tidak ada typed properties Eloquent (`protected array $fillable` → harus tanpa type)
- [ ] Tidak ada hardcoded values / axios fallback URL
- [ ] `declare(strict_types=1)` di semua file PHP termasuk generated
- [ ] Vuetify state tidak diubah via DOM API
- [ ] Tidak ada `console.log()`, `dd()`, `var_dump()`, `die()`, TODO comment
- [ ] Feature test untuk semua endpoints
- [ ] Unit test untuk semua Service methods

---

## 16. Lessons Learned — Sprint 01

| Sprint | Lesson |
|--------|--------|
| 01 | **Module namespace trap:** `nwidart/laravel-modules` v12 generates files in `app/` subfolder but stub namespace is `Modules\Auth\Providers\` (no `App`). Fix `composer.json` PSR-4 to `"Modules\\Auth\\App\\": "app/"` and update `module.json` providers. |
| 01 | **Hardcoded values:** Always use `import.meta.env.*` (frontend) and `env('VAR', 'default')` (backend config files only) — never hardcode URLs, domains, or credentials even in defaults. |
| 01 | **Service layer wajib:** ARCHITECTURE.md §6.1 requires `modules/<name>/services/` for API calls. Store must call service, not axios directly. |
| 01 | **Vue auto-import not reliable in tests:** Pinia stores need explicit `import { ref, computed } from 'vue'` — Vitest has no auto-import plugin. |
| 01 | **ExampleTest breaks after SPA setup:** Default `GET /` test fails when web routes emptied. Delete or update ExampleTest at sprint start. |
| 01 | **Always update factories after migration:** Factory must include new columns to avoid inconsistent test data. |
| 01 | **Typed class constants need PHP 8.3+:** `private const string FOO = 'bar'` crashes on PHP 8.2. Use `private const FOO = 'bar'` (no type) until upgrade. |
| 01 | **Eloquent properties must be untyped:** `protected array $fillable` in model causes fatal error — Eloquent parent class doesn't declare types. Use `protected $fillable` without type hints. |
| 01 | **Boolean env vars need filter_var:** Always use `filter_var(env('VAR', false), FILTER_VALIDATE_BOOLEAN)` for boolean flags — bare `env('VAR')` returns string `"false"` which is truthy. |
| 01 | **Repository binding in ServiceProvider::register():** Interface-to-implementation binding must happen in `register()`, not `boot()`. |
| 01 | **Vue form field names must match backend validation:** Always align field keys between Vue form object and FormRequest `rules()`. |
| 01 | **config/auth.php overrides app config:** Module's `config/auth.php` replaces `config/auth.php` via `mergeConfigFrom()`. Ensure LDAP config lives in module config to avoid confusion. |
| 01 | **ApiResponse helper location:** Shared helper in `app/Helpers/ApiResponse.php` — not module-specific, used by all modules. |
| 01 | **Avoid `window.location.href` in Axios interceptor:** Causes full page reload and infinite loop. Let router guard handle redirects — Axios should only clear token and `return Promise.reject(error)`. |
| 01 | **Don't call `fetchMe()` on public routes:** Use `to.meta.requiresAuth` (strict) to avoid unnecessary /me API calls on guest routes that trigger 401 → redirect loops. |
| 01 | **Don't modify migrations that already ran:** Always create a new migration file for schema changes. SQLite's limited ALTER TABLE may fail on columns with indexes. |
| 01 | **Vite `src/` vs `resources/js/` path mismatch:** Update `index.html` entry point and vite resolve aliases at project init. |
| 01 | **Module route loading needs explicit prefix:** `Route::prefix('api')->group(...)` required in ServiceProvider `boot()`. Bare `loadRoutesFrom()` registers at root. |
| 01 | **AuthService requires dedicated unit tests:** Feature tests verify endpoint behavior, but Service methods need isolated unit tests via mock Repository + Strategies. |
| 01 | **Axios baseURL fallback tetap dianggap hardcoded:** `baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api'` melanggar §8. Wajib tanpa fallback. |
| 01 | **UserFactory dan file generated tidak exempt dari `strict_types`:** Tambahkan manual setelah generated. |
| 01 | **Logo assets wajib dicopy dari `.claude/.design/assets/` ke `public/`:** Gunakan `v-img` component, jangan import lewat JS bundler. |
| 01 | **Inject interface, bukan concrete class di Service:** Memudahkan unit test via Mockery dan swap implementation via DI container. |
| 01 | **Jangan `new Class()` di Service — pakai constructor injection:** Terima dependency via constructor, binding di ServiceProvider. |
| 01 | **Gunakan `Config::get()` bukan `env()` di Service class:** `env()` tidak jalan setelah `php artisan config:cache`. |

---

## 17. Mandatory AI Tools — Setiap Eksekusi

> Berlaku untuk: **Claude Code, OpenCode, Antigravity, semua AI agent**

### Caveman Mode (WAJIB)

- Semua respons agent HARUS dalam **Caveman mode full** — terse, drop filler/article/pleasantries
- Pattern: `[thing] [action] [reason]. [next step].`
- Untuk Claude Code: hook aktif otomatis via `UserPromptSubmit` di `.claude/settings.json`
- Untuk OpenCode/Antigravity/Agents: ikuti caveman conventions yang didokumentasikan di `AGENTS.md`
- Off hanya dengan: `"stop caveman"` atau `"normal mode"`

### Graphify — Knowledge Graph Query (WAJIB sebelum grep/read)

- Jika `graphify-out/graph.json` ada → **HARUS** tanya graphify sebelum grep/glob/read kode sumber
- Untuk lokasi kode: `graphify query "di mana X didefinisikan?"`
- Untuk pemahaman arsitektur: `graphify explain "konsep ini"`
- Untuk relasi antar file: `graphify path "FileA" "FileB"`
- Baca file source langsung HANYA untuk: modifikasi kode spesifik, debug detail baris, atau saat graphify tidak cukup detail
- Hooks graphify sudah aktif di `settings.json` (Claude Code) dan `opencode.json` (OpenCode)

### PostgreSQL — Jangan Gunakan MySQL Syntax

- Semua koneksi DB sekarang PostgreSQL 17 — driver `pgsql`
- Gunakan `DbCompatTrait` untuk query yang pernah pakai MySQL-specific functions
- `COALESCE()` bukan `IFNULL()`, `STRING_AGG()` bukan `GROUP_CONCAT()`, `ILIKE` bukan `LIKE` (case-insensitive)
- Detail lengkap: `.claude/POSTGRESQL-MIGRATION.md`

---

@.docs/ARCHITECTURE.md
@.design/DESIGN-SYSTEM.md
@business-process.md
