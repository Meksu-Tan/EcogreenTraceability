# ARCHITECTURE.md — [Project Name]
## PT Ecogreen Oleochemicals — Automation & Digitalization Department
## Standard Technical Architecture for App Development

<!-- markdownlint-disable MD013 -->

> **Untuk AI Agent:** Urutan baca wajib:
> 1. `.claude/CLAUDE.md` — konvensi & restrictions
> 2. File ini — arsitektur & patterns
> 3. `.design/DESIGN-SYSTEM.md` — brand tokens & Vuetify theme
> 4. `.template/TEMPLATE.md` — referensi pola UI
> 5. `.docs/TEMPLATE-ADAPTATION.md` — cara adaptasi template ke design
> 6. `.docs/sprints/sprint-XX.md` — sprint aktif
>
> File ini berisi standar teknikal A&D yang berlaku untuk **semua project** — bukan hanya project ini.
> Bagian yang project-specific diberi label `<!-- PROJECT-SPECIFIC -->`.
> Last updated: 2026-05-24

---

## Daftar Isi

| § | Judul |
|---|---|
| §1 | System Overview |
| §2 | Arsitektur Aplikasi & Module Types |
| §3 | Tech Stack Detail |
| §4 | Pre-Requisites |
| §5 | Architecture Patterns — Backend |
| §6 | Architecture Patterns — Frontend |
| §7 | Auth & Authorization |
| §8 | API Design |
| §9 | Frontend UI Architecture |
| §10 | State Management |
| §11 | Database Design Principles |
| §12 | Migration Strategy |
| §13 | Code Conventions |
| §14 | Testing Strategy |
| §15 | Concurrency & Bulk Operation Patterns |
| §16 | Cara Menambah Modul Baru |
| §17 | Domain Hierarchy (Project-Specific) |
| §18 | Sprint Roadmap (Project-Specific) |
| §19 | Onboarding Training Modules |

---

## 1. System Overview

Decoupled architecture: Vue 3 SPA (port 5173) → Axios → Laravel 12 REST API (port 8000) → MySQL.

```
┌─────────────────┐   Axios + Bearer Token   ┌──────────────────┐   ┌──────────────┐
│  Vue 3 SPA      │ ───────────────────────► │  Laravel 12 API  │──►│  MySQL       │
│  localhost:5173 │ ◄─────────────────────── │  localhost:8000  │   │  port 3308   │
└─────────────────┘      JSON Response       └──────────────────┘   └──────────────┘
```

**Prinsip utama:**
- API-only backend (SPA) — Laravel hanya mengembalikan JSON, tidak ada server-side rendering
- Semua UI di frontend Vue — tidak ada Blade views di production
- Bearer token di localStorage — dikirim via Axios interceptor
- Modular architecture — backend dan frontend sama-sama folder-per-bisnis-proses

---

## 2. Arsitektur Aplikasi & Module Types

### 2.1 Alur Mandatory

Setiap sistem yang dibangun tim A&D **WAJIB** mengikuti alur berikut:

```
DOMAIN → PRODUCT → MODULES → FEATURES
```

### 2.2 Business Modules vs Technical Features

| **Business Modules** (Fungsi Bisnis) | **Technical Features** (Toolbox Pengembangan) |
|---|---|
| Kategori domain bisnis yang tampil sebagai menu navigasi | Kapabilitas teknikal yang bisa digunakan lintas business module |
| Satu module = satu folder di `backend/Modules/` dan `frontend/resources/js/modules/` | Satu feature bisa digunakan oleh banyak business module |
| Contoh: Auth, Organization, Employee, Assessment | Contoh: CRUD, Pagination, Error Handling, File Upload |

### 2.3 Business Module Types

| Master Module | Transaction Module | Inquiry Module | Dashboard |
|---|---|---|---|
| Data referensi & konfigurasi sistem | Proses bisnis utama (input, approval, posting) | Read-only reporting & pencarian data | Agregasi KPI & visualisasi real-time |
| Contoh: User, Role, Department | Contoh: Assessment Session, Training Plan | Contoh: Audit Log, History | Contoh: KPI Cards, Charts |

---

## 3. Tech Stack Detail

| Layer | Technology | Version | Implementation |
|---|---|---|---|
| Backend | PHP / Laravel | ^8.2 / ^12 | API-only, module-based via nwidart/laravel-modules |
| Auth | Laravel Sanctum | ^4 | Token-based (Bearer in localStorage), LDAP fallback via directorytree/ldaprecord |
| RBAC | spatie/laravel-permission | ^6 | Role + scope-based access via `user_organization_scopes` |
| Frontend | Vue 3 + Vite | ^3 / ^5 | Composition API, `<script setup>` |
| UI Framework | Vuetify | ^3.7 | Primary UI component system (Materio template reference at `.template/`) |
| CSS styling | Tailwind CSS | v4 | Base CSS utility (resets in `main.css` only) |
| State | Pinia | ^2 | Stores per module + global stores (`auth`, `toast`) |
| HTTP | Axios | latest | Instance at `frontend/resources/js/api/axios.js` |
| Icons | Remix Icon | `@iconify-json/ri` | **Not MDI** |
| Charts | ApexCharts | `vue3-apexcharts` | Theme-aware radar/bar/donut |
| Lint (FE) | ESLint | ^8 | `semi: never`, 2-space indent, trailing commas |
| Lint (BE) | Laravel Pint | ^1 | |
| Test (BE) | PHPUnit | ^11 | SQLite `:memory:` in-memory database |
| Test (FE) | Vitest | latest | jsdom environment, localStorage mock |

---

## 4. Pre-Requisites

| Tool | Minimum Version | Notes |
|---|---|---|
| PHP | `>= 8.2` | Laravel 12 requires ^8.2 |
| Composer | Latest | |
| Node.js | `>= 18` | |
| npm | `>= 9` | |
| Database | MySQL / MariaDB | Dev: port 3308. Deteksi via `netstat -an \| findstr "330"` |
| Git | Latest | |
| IDE | VS Code | Extensions: PHP Intelephense, Volar |

---

## 5. Architecture Patterns — Backend

### 5.1 Modular Laravel (nwidart/laravel-modules)

```
backend/
├── app/                    ← Laravel core (middleware, providers, global helpers)
├── Modules/                ← nwidart/laravel-modules (sejajar app/)
│   ├── Auth/               ← Sprint 0
│   ├── [NamaModul]/        ← Sprint N+
│   └── ...
├── database/migrations/    ← SEMUA migration (never inside modules)
├── database/seeders/
├── routes/api.php          ← core routes
└── composer.json           ← merge-plugin: "include": ["Modules/*/composer.json"]
```

**Per-module structure:**
```
Modules/<Name>/
├── app/
│   ├── Http/
│   │   ├── Controllers/     ← inject Service, handle HTTP only
│   │   ├── Requests/        ← FormRequest — SEMUA validasi di sini
│   │   └── Resources/       ← API Resource responses
│   ├── Models/              ← Eloquent (SoftDeletes, uuid, fillable, relasi)
│   ├── Services/            ← Business logic (inject RepositoryInterface)
│   ├── Repositories/
│   │   ├── <Entity>RepositoryInterface.php    ← contract
│   │   └── Eloquent<Entity>Repository.php     ← Eloquent implementation
│   ├── Policies/            ← Authorization
│   └── Providers/
│       └── <Name>ServiceProvider.php         ← binding Interface → Implementation
├── config/                  ← Module config (merged via ServiceProvider)
├── database/
│   ├── seeders/             ← Module-specific seeders
│   └── factories/           ← Module-specific factories
├── routes/
│   ├── api.php              ← API routes (auth:sanctum)
│   └── web.php              ← KOSONG — SPA
└── module.json
```

### 5.2 Controller → Service → Repository (WAJIB)

```
HTTP Request
    │
    ▼
Controller  (HTTP layer only — inject Service, jangan panggil Model langsung)
    │
    ▼
Service     (Business logic — inject RepositoryInterface via constructor)
    │
    ▼
RepositoryInterface  (contract — bound via ServiceProvider::register())
    │
    ▼
EloquentRepository   (Eloquent query implementation)
    │
    ▼
Model + Database
```

**Aturan:**
1. Controller hanya handle HTTP (request/response). Inject Service.
2. Service handle business logic. Inject RepositoryInterface (bukan concrete class).
3. Repository hanya query database (Eloquent / QueryBuilder). Tidak ada logic.
4. Binding Interface → Implementation di `register()` method ServiceProvider.
5. Exception: read-only query kompleks non-standard CRUD boleh langsung ke Model di Service.

**Contoh binding di ServiceProvider:**
```php
public function register(): void {
    $this->app->bind(
        \Modules\[Name]\App\Repositories\[Entity]RepositoryInterface::class,
        \Modules\[Name]\App\Repositories\Eloquent[Entity]Repository::class,
    );
}
```

**Contoh Service inject Repository:**
```php
class [Name]Service {
    public function __construct(
        protected [Entity]RepositoryInterface $repository,
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator {
        return $this->repository->paginate($filters);
    }
}
```

**Contoh Controller inject Service:**
```php
class [Name]Controller extends Controller {
    public function __construct(
        protected [Name]Service $service,
    ) {}

    public function index(Request $request): JsonResponse {
        return response()->json($this->service->paginate($request->only(['search'])));
    }
}
```

**Repository method naming:**
- `paginate(array $filters)` — listing with pagination + filters
- `create(array $data)` — insert, return model
- `update(Model $model, array $data)` — update, return refreshed model
- `delete(Model $model)` — soft/hard delete

### 5.3 Pattern Rules

- Semua migrations di `backend/database/migrations/` (JANGAN di dalam Modules/)
- `routes/web.php` per modul = **kosong** (SPA)
- Entitas core: `id` (bigint PK) + `uuid` (unique, public API) + `timestamps()`
- Master data: `is_active` (bool, default true) + `softDeletes()`
- Multi-table mutations: selalu `DB::transaction()`
- UUID di URL publik, bukan sequential ID
- Semua response via Laravel API Resource

---

## 6. Architecture Patterns — Frontend

### 6.1 Module-Based SPA

```
frontend/resources/js/
├── main.js                  ← App entry (registerPlugins → mount)
├── @core/                   ← Materio core (ThemeSwitcher, IconBtn, dll)
├── @layouts/                ← Materio layout engine
├── layouts/
│   ├── AuthLayout.vue       ← Authenticated (sidebar + navbar + VMain)
│   ├── GuestLayout.vue      ← Public (blank — class="layout-blank layout-guest")
│   └── components/          ← NavItems, NavbarThemeSwitcher, UserProfile
├── modules/                 ← Business modules
│   ├── auth/                ← views/, routes.js
│   ├── [nama-modul]/        ← views/, components/, services/, stores/, routes.js
│   └── ...
├── router/
│   └── index.js             ← App router entry (loads router instance from @/core/router)
├── core/
│   └── router/
│       └── index.js         ← createAppRouter() + navigation guard + auto-load module routes via import.meta.glob
├── api/
│   └── axios.js             ← Axios instance + Bearer interceptor + error handler
├── stores/                  ← Global stores (auth.js, toast.js)
└── styles/                  ← design-tokens.css (from .design/)
```

**Per-module structure:**
```
modules/<name>/
├── views/          ← Vue page components
├── components/     ← Module-specific components
├── services/       ← Axios API calls (TIDAK di view)
├── stores/         ← Pinia store
└── routes.js       ← export array → spread di router/routes.js
```

### 6.2 Auto-Import Rules

| Scope | Auto-imported | Must import explicitly |
|---|---|---|
| Vue APIs | `ref`, `computed`, `onMounted`, `watch`, `defineProps`, `defineEmits` | — |
| Pinia | `storeToRefs`, `defineStore` | — |
| Vue Router | **NOT auto-imported** | `useRouter`, `useRoute` from `vue-router` |
| Vuetify | All components (VCard, VBtn, dll) | — |
| Core components | ThemeSwitcher | — (IconBtn tidak ada di project — pakai `VBtn variant="text" icon`) |

### 6.3 Vite Path Aliases (vite.config.js)

```js
'@'          → resources/js/
'@core'      → resources/js/@core/
'@layouts'   → resources/js/@layouts/
'@images'    → resources/images/
'@styles'    → resources/styles/
```

---

## 7. Auth & Authorization

### 7.1 Auth Flow

```
POST /api/login
  ├─ input email? ──────────────► SQL attempt (Auth::attempt via email)
  ├─ LDAP disabled? ────────────► SQL attempt (Auth::attempt via username)
  └─ LDAP enabled + username ──► LDAP: bind DN → query cn/mail
         │                           └─ gagal? → SQL fallback
         ▼
    syncLdapUser() via User::firstOrCreate(username)
         │
         ▼
    createToken('auth-token') → { token, token_type: 'Bearer', user }
         │
         ▼
    Frontend: localStorage('auth_token') + Pinia auth store → redirect /dashboard
```

**Key details:**
- Token type: Bearer di localStorage (`auth_token`)
- Dikirim via `Authorization: Bearer <token>` di Axios interceptor
- Revocation: `POST /api/logout` → `$user->currentAccessToken()->delete()`
- Session check: `router.beforeEach` → `auth.fetchMe()` → `GET /api/me`
- 401 response: clear localStorage → redirect `/login` → show toast
- `LDAP_ENABLED` — selalu pakai `filter_var(env(...), FILTER_VALIDATE_BOOLEAN)`, bukan raw `env()`

### 7.2 Role Model (Standard 8 Roles)

| Role | Scope |
|---|---|
| Super Admin | All data, all departments |
| HR / People Development | All departments (reporting, governance) |
| Department Head | Own department |
| Section Manager | Own section |
| Matrix Owner | Assigned job family / job profile |
| Lead / Evaluator | Assigned assessments |
| Member | Own data and assessments |
| Viewer | Read-only within scope |

### 7.3 Scope-Based Access (AccessScopeService)

Semua read operation yang mengembalikan data organisasi/matrix/assessment **wajib** filter via scope:

```php
$scope = $this->accessScopeService->resolveScope(auth()->user());

// global  → Super Admin, tidak filter
// department → filter department_id
// section    → filter section_id
// job_family → filter job_family_id
// job_profile → filter job_profile_id

$query
    ->when($scope->department_id, fn($q) => $q->where('department_id', $scope->department_id))
    ->when($scope->section_id,    fn($q) => $q->where('section_id',    $scope->section_id));
```

---

## 8. API Design

### 8.1 Conventions

- Base URL: `http://localhost:8000/api`
- Resourceful routes: `Route::apiResource()` + `->shallow()` untuk nested resources
- Route naming: kebab-case (`/job-families`, `/job-profiles`)
- Semua routes `auth:sanctum` kecuali register, login, ping
- Validation errors: `422` dengan `{ message, errors }`
- Module routes di `Modules/<Name>/routes/api.php`

### 8.2 Shallow Routing Gotcha

`->shallow()` hanya mengirim child ID pada `show/update/destroy` — parameter parent tidak terisi.
**Hapus parent parameter dari method signature.** Jangan gunakan `abort_unless()` untuk cek ownership.

### 8.3 Boolean Query Params

Filter `is_active` via query string dikirim sebagai string `"true"/"false"`.
**Gunakan `$request->boolean('is_active')`** — bukan `$request->only()`.
MySQL meng-cast string `"true"` → `0` jika dilewatkan langsung ke query.

### 8.4 Breadcrumb — Vuetify 3 Gotcha

`VBreadcrumbs` dengan prop `to` merender `<a>` (bukan `<router-link>`) — bisa trigger full-page reload → theme reset ke `light`.

**Wajib gunakan `#item` slot:**
```html
<VBreadcrumbs :items="breadcrumbs">
  <template #item="{ item }">
    <li class="v-breadcrumbs-item">
      <router-link v-if="item.to" :to="item.to" class="v-breadcrumbs-item--link">
        {{ item.title }}
      </router-link>
      <span v-else>{{ item.title }}</span>
    </li>
  </template>
</VBreadcrumbs>
```

---

## 9. Frontend UI Architecture

### 9.1 Layout System

```
App.vue
├── ThemeSwitcher (global FAB — fixed bottom-right)
└── RouterView
    ├── GuestLayout.vue    ← class="layout-blank layout-guest"
    │   └── RouterView → /, /login, /register
    └── AuthLayout.vue     ← v-app + navigation-drawer + app-bar (project-owned)
            ├── VerticalNav (sidebar) — NavItems.vue
            ├── VAppBar (navbar) — NavbarThemeSwitcher + UserProfile
            └── VMain → RouterView → semua route authenticated
```

> **Catatan:** Layout menggunakan `layouts/AuthLayout.vue` dan `layouts/GuestLayout.vue` yang ditulis di project
> (bukan `@layouts/` dari Materio). Referensi Materio `@layouts/` hanya dibaca dari `.template/` — tidak di-import ke kode project.

### 9.2 Materio Template Pattern

`.template/` adalah **READ-ONLY reference** dari Materio Vuetify Admin Template.

**Alur wajib saat membangun komponen:**
1. Cari referensi di `.template/frontend/resources/js/pages/` atau `views/`
2. Baca dan pahami pola (Vuetify props, event handling)
3. Adaptasi ke module konteks — hubungkan ke store/service
4. Ikuti ESLint rules
5. **Jangan pernah modifikasi `.template/` langsung**

### 9.3 UI Conventions

| Komponen | Pattern |
|---|---|
| Auth pages | `auth-wrapper`, `auth-card`, decorative elements |
| Dashboard cards | `VRow class="match-height"` untuk uniform height |
| Stat cards | `VAvatar variant="tonal" rounded elevation-2` |
| Charts | `VueApexCharts` + `useTheme()` + `hexToRgb` — **theme-aware colors** |
| Action menu | `MoreBtn` component untuk card 3-dot menus |
| Nav items | Edit `layouts/components/NavItems.vue` |
| Module routes | Auto-loaded via import.meta.glob in `core/router/index.js` → AuthLayout children |

---

## 10. State Management

### 10.1 Pinia Stores

| Store | Location | Purpose |
|---|---|---|
| `useAuthStore` | `stores/auth.js` | Login, logout, register, fetchMe, token, user |
| `useToastStore` | `stores/toast.js` | Global toast notifications (auto-dismiss 3s) |
| Module stores | `modules/<name>/stores/<name>Store.js` | Per-module state |

**Store pattern:**
```js
// modules/<name>/stores/<name>Store.js
export const use[Name]Store = defineStore('<name>', () => {
  const items = ref([])
  const loading = ref(false)

  async function fetchAll(params = {}) {
    loading.value = true
    try {
      const res = await [name]Service.getAll(params)
      items.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  return { items, loading, fetchAll }
})
```

### 10.2 Router Guard Logic

```
router.beforeEach:
  1. if user === null AND route.meta.requiresAuth → auth.fetchMe()
  2. if requiresAuth AND !isAuthenticated → redirect('/login')
  3. if guestOnly AND isAuthenticated → redirect('/dashboard')
  4. if route.meta.role AND user.role !== meta.role → redirect('/dashboard')
```

---

## 11. Database Design Principles

### 11.1 Core Entity Convention

```php
// Semua core entity wajib memiliki:
$table->id();                           // bigint PK (internal)
$table->uuid('uuid')->unique();         // untuk public API URLs
$table->timestamps();

// Master data tambahan:
$table->boolean('is_active')->default(true);
$table->softDeletes();
```

### 11.2 Naming Conventions

| Artifact | Convention | Contoh |
|---|---|---|
| Tables | snake_case, plural | `competency_matrices`, `job_profiles` |
| Pivot tables | singular, alphabetical | `competency_standard` |
| FK columns | singular `_id` suffix | `department_id`, `job_family_id` |
| Index names | `idx_` prefix | `idx_competency_matrix_job_level` |
| JSON columns | `_payload` suffix | `scores_payload` |

### 11.3 Soft Delete + Unique Constraint

MySQL unique index menyertakan soft-deleted rows → duplicate-entry error saat reuse.

**Two-step fix:**

**Step 1 — Validation layer:**
```php
// BENAR: scope ke non-deleted rows
Rule::unique('table', 'column')->whereNull('deleted_at')

// SALAH: string syntax tidak aman dengan soft delete
'unique:table,column'
```

**Step 2 — Database layer:**
```php
// Migration: drop unique index, ganti regular index
// up()
$table->dropUnique('table_column_unique');
$table->index('column', 'table_column_index');

// down()
$table->dropIndex('table_column_index');
$table->unique('column', 'table_column_unique');
```

### 11.4 FK Constraint Patterns

```php
// Master data → restrict (proteksi historical records)
$table->foreignId('department_id')->constrained()->restrictOnDelete();

// Optional reference → null on delete
$table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

// Parent-child → cascade (child ikut terhapus)
$table->foreignId('session_id')->constrained('assessment_sessions')->cascadeOnDelete();
```

### 11.5 Soft Delete Policy

| Tipe Data | Policy |
|---|---|
| Master / referensi | Soft delete + `is_active` flag |
| Transactional | Tidak ada hard delete — gunakan cancel/void status |
| Audit trail | Immutable — tidak dihapus, tidak diedit setelah published |

---

## 12. Migration Strategy

### 12.1 Prinsip

- Semua migration di `backend/database/migrations/` dengan urutan timestamp
- Ikuti urutan blueprint project — migrations tidak dalam urutan alphabetical/modul
- Jangan edit migration lama — buat migration baru untuk perubahan schema

### 12.2 Circular FK Resolution

Tiga dependency cycle umum — diselesaikan via ALTER TABLE setelah kedua tabel ada:

```php
// Step 1: CREATE table_a dengan kolom tanpa FK constraint dulu
Schema::create('table_a', function (Blueprint $table) {
    $table->unsignedBigInteger('ref_b_id')->nullable();  // no constraint yet
});

// Step 2: CREATE table_b dengan FK ke table_a
Schema::create('table_b', function (Blueprint $table) {
    $table->foreignId('a_id')->constrained('table_a')->nullOnDelete();
});

// Step 3: ALTER table_a — tambah FK constraint setelah table_b ada
Schema::table('table_a', function (Blueprint $table) {
    $table->foreignId('ref_b_id')->nullable()->change()
          ->constrained('table_b')->nullOnDelete();
});
```

**`down()` untuk ALTER migrations:**
```php
// Gunakan dropForeign(['column_name']) — BUKAN dropForeignIdFor(ClassName::class)
$table->dropForeign(['ref_b_id']);
```

---

## 13. Code Conventions

### 13.1 Frontend (ESLint-enforced)

| Rule | Standard |
|---|---|
| Semicolons | **None** (`semi: never`) |
| Indent | 2 spaces |
| Trailing commas | Required on multiline (`comma-dangle: always-multiline`) |
| Identifier case | `camelCase` |
| Vue templates | PascalCase components (`VBtn`, not `v-btn`) |
| Directional classes | Vuetify logical (`ps-`, `pe-`, `ms-`, `me-`) — **NOT** `pl-`, `pr-` |
| `v-html` | Hanya untuk trusted SVG logos, wrap `eslint-disable`/`eslint-enable` |
| Arrow function params | Omit single param parens (`x => x`, not `(x) => x`) |
| useRouter / useRoute | **Wajib import eksplisit** dari `vue-router` — tidak auto-imported |

### 13.2 Backend

| Rule | Standard |
|---|---|
| Validation | FormRequest — tidak di Controller atau Model |
| Response | API Resource — semua response |
| Authorization | Policy + AccessScopeService |
| Multi-table | DB::transaction() wajib |
| Public URL | UUID — bukan sequential ID |
| Web routes | Empty — SPA |
| LDAP env | `filter_var(env('LDAP_ENABLED'), FILTER_VALIDATE_BOOLEAN)` |

---

## 14. Testing Strategy

### 14.1 Backend (PHPUnit)

```xml
<!-- phpunit.xml — wajib ada -->
<php>
  <env name="DB_CONNECTION" value="sqlite"/>
  <env name="DB_DATABASE" value=":memory:"/>
</php>
```

- `RefreshDatabase` trait untuk isolated tests
- Test locations: `backend/tests/Feature/` dan `backend/tests/Unit/`
- Run: `php artisan test` atau `php artisan test --filter=<Name>`
- Coverage minimum: 80% untuk kode baru

### 14.2 Frontend (Vitest)

```js
// vite.config.js — test block wajib ada sejak awal
test: {
  globals: true,
  environment: 'jsdom',
  setupFiles: ['./src/test/setup.js'],
}
```

- Setup: `test/setup.js` — localStorage mock + Vuetify global mock
- Pinia: `setActivePinia(createPinia())` — tidak perlu `@pinia/testing`
- Run: `npm run test:run` (single) atau `npm test` (watch)

```js
// src/test/setup.js — localStorage mock wajib (jsdom tidak full implement)
const localStorageMock = (() => {
  let store = {}
  return {
    getItem:    key      => store[key] ?? null,
    setItem:    (key, v) => { store[key] = String(v) },
    removeItem: key      => { delete store[key] },
    clear:      ()       => { store = {} },
  }
})()
Object.defineProperty(window, 'localStorage', { value: localStorageMock })
```

---

## 15. Concurrency & Bulk Operation Patterns

### 15.1 Atomic State Transition (WAJIB untuk semua status change)

Semua perubahan status wajib **compare-and-swap (CAS)** untuk mencegah race condition:

```php
// BENAR: atomic CAS
$affected = Model::where('id', $id)
    ->where('status', 'approved')       // expected current state
    ->update(['status' => 'completed']); // new state

if ($affected === 0) {
    throw new \DomainException('Status sudah berubah — coba lagi.');
    // atau: return response()->json(['message' => 'Conflict'], 409)
}

// SALAH: non-atomic — race condition
$model->update(['status' => 'completed']);
```

### 15.2 Pessimistic Lock untuk Bulk Operations

Operasi bulk yang concurrency-sensitive wajib pessimistic lock:

```php
$record = Model::lockForUpdate()->findOrFail($id);

// config/database.php
'options' => [PDO::ATTR_TIMEOUT => 5],

// .env
DB_LOCK_TIMEOUT=5000
```

Jika lock timeout → kembalikan **409 Conflict** ke client.

### 15.3 Queue Jobs untuk Bulk Operations

Operasi dengan >20 items wajib dispatch ke queue — jangan proses synchronous:

```php
// Dispatch ke queue
GenerateBulkJob::dispatch($record->id);
$record->update(['status' => 'generating']);

// Frontend polling: cek status = 'generating'
// Production: php artisan queue:work harus aktif
```

---

## 16. Cara Menambah Modul Baru

### 16.1 Backend (12 langkah)

1. `php artisan module:make <NamaModul>`
2. Kosongkan `Modules/<Name>/routes/web.php` (SPA)
3. Buat `Models/` — tambah `SoftDeletes`, `uuid`, `fillable`, relasi
4. Buat `Repositories/<Entity>RepositoryInterface.php`
5. Buat `Repositories/Eloquent<Entity>Repository.php`
6. Buat `Services/<Name>Service.php` — inject RepositoryInterface via constructor
7. Buat `Http/Controllers/` — inject Service, jangan panggil Model langsung
8. Buat `Http/Requests/` — semua validasi di sini
9. Buat `Http/Resources/`
10. Daftarkan binding di `Providers/<Name>ServiceProvider.php` → `register()`
11. Update `routes/api.php` dengan `auth:sanctum`
12. Tambah migration di `backend/database/migrations/` (BUKAN di dalam Modules/)

### 16.2 Frontend (8 langkah)

1. Baca referensi di `.template/` yang sesuai jenis halaman
2. Buat `modules/<nama>/services/<nama>Service.js` — semua axios call
3. Buat `modules/<nama>/stores/<nama>Store.js` — Pinia store
4. Buat `modules/<nama>/views/<Nama>IndexView.vue` — adaptasi dari `.template/pages/`
5. Buat `modules/<nama>/components/` — adaptasi dari `.template/components/`
6. Buat `modules/<nama>/routes.js` — export array route definitions
7. Import dan spread di `plugins/router/routes.js` → AuthLayout children
8. Tambah menu di `layouts/components/NavItems.vue`

---

## 17. Domain Hierarchy <!-- PROJECT-SPECIFIC -->

> Ganti section ini dengan domain hierarchy project yang spesifik.
> Contoh dari AnD Human Capital:

```
[Entity Root]
  └── [Entity Level 2]
        └── [Entity Level 3]
              └── [Core Business Entity]
                    └── [Transactional Entities]
```

**Entity Design Rules (berlaku untuk semua project):**
- Semua core entity: `id` (bigint PK) + `uuid` (unique, public URL)
- Master data: `is_active` + `softDeletes()`
- Transactional data: jangan hard delete — gunakan status/cancel
- Circular FK → selesaikan via ALTER TABLE (§12.2)

---

## 18. Sprint Roadmap <!-- PROJECT-SPECIFIC -->

> Ganti section ini dengan sprint roadmap project yang spesifik.
> Lihat `.docs/sprints/sprint-roadmap.md` untuk detail dependency.

| Sprint | Fokus | Dependency | Status |
|---|---|---|---|
| 01 | Foundation: Auth + Theme + Scaffolding | — | TODO |
| 02 | [Modul bisnis pertama] | Sprint 01 | |
| 03 | [Modul bisnis kedua] | Sprint 02 | |

**Referensi:** `.docs/sprints/sprint-roadmap.md` — dependency graph, migration map per sprint.

---

## 19. Onboarding Training Modules

> 18 modul training internal A&D untuk developer baru.
> Ini adalah kurikulum teknikal — bukan business module aplikasi.

### Phase 1: Foundation & Core Features

| # | Module | Kapabilitas |
|---|---|---|
| 01 | Setup Guide | Laravel 12 + Vue 3 decoupled, CORS & Sanctum, folder structure |
| 02 | Authentication | Register & Login API, Sanctum, Auth Store Pinia, Navigation Guard |
| 03 | Eloquent & CRUD | API Resource Controller, Vuetify Forms, Axios CRUD |
| 04 | Layout & Routing | GuestLayout/AuthLayout, Nested Routes, Navigation Guard |
| 05 | Vuetify | Installation, theme customization, responsive, dark mode |
| 06 | Pagination | Server-side pagination, Laravel Paginate, query params Vue |
| 07 | Error Handling | HTTP error codes, Axios interceptor, Toast notification |

### Phase 2: Advanced Features

| # | Module | Kapabilitas |
|---|---|---|
| 08 | Role & Permission | Spatie Laravel Permission, UI conditional rendering |
| 09 | File Upload | Laravel File Storage, validation, Vue preview, Axios multipart |
| 10 | Search & Filter | Query builder search, debounce Vue, multi-filter |
| 11 | Unit Testing | PHPUnit Feature Test, Vitest + Vue Test Utils, localStorage mock |
| 12 | Git Workflow | Branching strategy, conventional commits, pull request |

### Phase 3: Production & Architecture

| # | Module | Kapabilitas |
|---|---|---|
| 13 | Deployment | VPS Ubuntu, Nginx, HTTPS SSL Certbot, CI/CD |
| 14 | Repository & Service | Clean Code: Controller → Service → Repository |
| 15 | Modular Architecture | Large-scale structure, folder modules, lazy loading |
| 16 | Materio UI | Vuetify 3.7 + Materio Admin Template setup |
| 17 | Documentation | LaRecipe & Markdown, versioning docs |
| 18 | AI-Assisted Setup | Claude Code CLI & CLAUDE.md project automation |

---

*Living document — update saat ada perubahan arsitektur atau standar teknikal.*
*Project-specific sections (§17, §18) diisi per project.*
