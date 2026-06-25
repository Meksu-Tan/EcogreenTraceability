# Backend Modules — Convention Reference

## Module namespace (nwidart/laravel-modules v12)
Folder prefix dropped in namespace:
- `m-adjustment` → `Modules\Adjustment`
- `ts-blending` → `Modules\TsBlending`
- `trace-backward` → `Modules\TraceBackward`

## DB connection per prefix
- `m-*` → `mysql` (PostgreSQL eudr_dev)
- `ts-*` → `eudr_ts`
- `trace-*` → `eudr_ts`
- No prefix → `mysql`

## Architecture chain (WAJIB)
```
Controller → Service → Repository → Model
```

## Per-module structure
```
app/Http/Controllers/   ← inject Service, HTTP only
app/Http/Requests/      ← ALL validation
app/Providers/          ← register() binds interface→concrete
app/Repositories/       ← DB queries; Contracts/ = interface
app/Services/           ← business logic; Contracts/ = interface
routes/api.php          ← middleware(['auth:sanctum', 'plant.context'])
routes/web.php          ← EMPTY (SPA)
```

## Anti-patterns
- `new ConcreteClass()` in Service
- `env()` in Service
- Validation in Controller
- `m_tank` / `m_tank_detail` tables
- Binding in `boot()` — must be `register()`

## See also
`.claude/rules/modules-backend.md` — full module rule set
`.claude/rules/backend-php.md` — PHP conventions
