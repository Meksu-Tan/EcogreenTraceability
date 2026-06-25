---
paths: backend/Modules/**
---

# Backend Module Rules

## Namespace pattern
`nwidart/laravel-modules` v12 — folder prefix DROPPED in namespace:
- `m-adjustment` + `"name": "Adjustment"` → `Modules\Adjustment`
- `ts-blending` + `"name": "TsBlending"` → `Modules\TsBlending`
- `trace-backward` → `Modules\TraceBackward`

Fix `composer.json` PSR-4: `"Modules\\Auth\\App\\": "app/"`

## DB connection per module prefix
- `m-*` → `mysql` connection (PostgreSQL `eudr_dev`)
- `ts-*` → `eudr_ts` connection (PostgreSQL `eudr_dev`)
- `trace-*` → `eudr_ts` connection
- No prefix (Admin, Auth, Dashboard, Shared, Inquiry) → `mysql`

## Per-module structure
```
Modules/<Name>/
  app/
    Http/Controllers/   ← inject Service, HTTP only
    Http/Requests/      ← ALL validation here
    Providers/          ← register() binds interface→concrete + loadRoutesFrom()
    Repositories/       ← DB queries; Contracts/ holds interface
    Services/           ← business logic; Contracts/ holds interface
  routes/api.php        ← middleware(['auth:sanctum', 'plant.context']) prefix('api/v1')
  routes/web.php        ← KOSONG (SPA)
```

## Route rules
- Route: kebab-case — `/api/v1/transactions/rm-entries`
- Exact named routes BEFORE `{id}` wildcard in `routes/api.php`
- All routes under `middleware(['auth:sanctum', 'plant.context'])`

## Plant context
- Frontend sends: `X-Plant-Id` header (preferred), `id_plant` query param, or body field
- Middleware sets `$request->get('plant_context')['plant_code']` (resolved `code_3`)
- `null` plant_code = all plants (no filter)
- Use `PlantFilterTrait` at repository level for scoping

## Shared module assets
- `PlantContextMiddleware` (`plant.context`) — resolves plant, null = all plants
- `PlantScopeMiddleware` (`plant.scope`) — rejects requests with no plant
- `AuditService`, `PeriodLockService` — cross-module
- `DbCompatTrait` — PostgreSQL/MySQL dual-DB helpers

## Module creation checklist (12 steps)
1. `php artisan module:make <Name> && composer dump-autoload`
2. Empty `routes/web.php`
3. Model: SoftDeletes, uuid, fillable (untyped), relations
4. `Repositories/Contracts/<Entity>RepositoryInterface.php`
5. `Repositories/Eloquent<Entity>Repository.php`
6. `Services/<Name>Service.php` — inject RepositoryInterface
7. `Http/Controllers/` — inject Service
8. `Http/Requests/` — ALL validation
9. `Http/Resources/` — API Resource responses
10. Binding in `Providers/<Name>ServiceProvider.php::register()`
11. `routes/api.php` with auth:sanctum
12. Migration in `backend/database/migrations/` (NOT inside Modules/)

## Anti-patterns (NEVER)
- `new ConcreteClass()` in Service
- `env()` in Service class
- Validation in Controller
- Model direct call from Controller
- `m_tank` or `m_tank_detail` tables
