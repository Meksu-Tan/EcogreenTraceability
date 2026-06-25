---
paths: backend/**
---

# Backend PHP Rules

## Mandatory per file
- `declare(strict_types=1)` on EVERY PHP file — including generated factories, seeders, migrations
- Typed properties and return types on ALL methods
- `protected $fillable` WITHOUT type hint (Eloquent parent untyped)

## Architecture chain (WAJIB)
```
Controller → Service → Repository → Model
```
- Controller: HTTP only, inject Service, NO Model direct calls
- Service: business logic, inject RepositoryInterface (never concrete class)
- Repository: DB queries only, NO business logic
- Binding Interface→concrete di `ServiceProvider::register()` — BUKAN `boot()`

## Config & Env
- `Config::get()` di Service — BUKAN `env()` directly (breaks after config:cache)
- Boolean env: `filter_var(env('VAR', false), FILTER_VALIDATE_BOOLEAN)`
- Only `config/*.php` may call `env()`

## DI Rules
- NEVER `new ClassName()` in Service — pakai constructor injection
- NEVER inject concrete class — inject interface
- ALL validation in FormRequest, NOT in Controller or Model

## Response
- ALL responses via `ApiResponse::success()` / `ApiResponse::error()` / `ApiResponse::paginated()`
- `App\Helpers\ApiResponse` — bukan module-specific

## Database
- ALL PostgreSQL 17 — driver `pgsql`
- `COALESCE()` not `IFNULL()`, `STRING_AGG()` not `GROUP_CONCAT()`, `ILIKE` not `LIKE`
- NEVER `m_tank` or `m_tank_detail` — use `m_sloc` and `m_sloc_detail`
- Multi-table mutations: WAJIB `DB::transaction()`
- UUID for public API URLs, NOT sequential ID
- `DbCompatTrait` for dual-DB helpers

## Code size limits
- Max 200 lines/class
- Max 20 lines/method
- No `dd()`, `var_dump()`, `die()`, `console.log()` in committed code
- No `TODO` comments in review-bound code
