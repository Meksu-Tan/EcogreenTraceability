# Migrations — Convention Reference

## Critical rules
- NEVER edit existing migration files — create NEW migration for schema changes
- `declare(strict_types=1)` required on EVERY migration file
- Naming: `YYYY_MM_DD_HHMMSS_action_table.php`
- Generate with: `php artisan make:migration <name>`

## PostgreSQL 17 only
- Driver `pgsql` — no MySQL syntax
- `COALESCE()` not `IFNULL()`, `STRING_AGG()` not `GROUP_CONCAT()`
- pgsql-only migrations → `database/migrations/pgsql/` via `eudr_ts_pg` connection

## Forbidden
- `m_tank` or `m_tank_detail` — use `m_sloc`, `m_sloc_detail`
- Migrations inside `Modules/` directory
- MySQL-specific: `ENUM`, `IFNULL`, `GROUP_CONCAT`

## down() for FK drops
```php
$table->dropForeign(['column_name']);  // correct
// NOT: $table->dropForeignIdFor(Model::class);
```

## See also
`.claude/rules/migrations.md` — full migration rule set
