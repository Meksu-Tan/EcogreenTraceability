---
paths: backend/database/migrations/**
---

# Migration Rules

## Critical
- NEVER modify migration that already ran — buat migration BARU
- ALL migrations in `backend/database/migrations/` — NEVER inside `Modules/`
- `declare(strict_types=1)` required on EVERY migration file
- Use `php artisan make:migration <name>` to auto-generate timestamp

## Naming pattern
Format: `YYYY_MM_DD_HHMMSS_action_table.php`
Examples:
- `2026_06_15_120000_create_adjustments_table.php`
- `2026_06_15_120015_add_status_to_transfers_table.php`

## PostgreSQL 17 syntax only
- Driver: `pgsql` — no MySQL-specific syntax
- `COALESCE()` not `IFNULL()`
- Use `->nullable()` + `->change()` for ALTER columns
- pgsql-only migrations → `database/migrations/pgsql/` using `eudr_ts_pg` connection

## Table naming
- New stack: `m_sloc`, `m_sloc_detail` — NEVER `m_tank`, `m_tank_detail`
- snake_case plural: `t_adjustment_headers`, `t_balance_header`
- Pivot: singular alphabetical: `material_supplier`

## Core entity convention
```php
$table->id();                           // bigint PK
$table->uuid('uuid')->unique();         // public API URLs
$table->timestamps();

// Master data adds:
$table->boolean('is_active')->default(true);
$table->softDeletes();
```

## FK patterns
- Master → restrict: `->constrained()->restrictOnDelete()`
- Optional ref → null: `->nullable()->constrained()->nullOnDelete()`
- Parent-child → cascade: `->constrained()->cascadeOnDelete()`

## Circular FK resolution
1. CREATE table_a with column but NO constraint
2. CREATE table_b with FK to table_a
3. ALTER table_a — add FK after table_b exists

## Soft delete + unique constraint
```php
// Validation: scope to non-deleted
Rule::unique('table', 'column')->whereNull('deleted_at')

// Migration: drop unique, add regular index
$table->dropUnique('table_column_unique');
$table->index('column', 'table_column_index');
```

## down() for ALTER migrations
```php
$table->dropForeign(['column_name']);  // NOT dropForeignIdFor(ClassName::class)
```
