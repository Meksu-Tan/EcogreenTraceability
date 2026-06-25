---
paths: reference-dont-change/**
---

# Reference System — READ ONLY

> `reference-dont-change/` is the legacy Laravel MVC+Blade system.
> Source of truth for ALL business logic in EODS.
> Runs at http://localhost:8001

## NEVER modify any file in this directory

## Purpose — READ to understand, then implement in new stack
- Business logic, validation rules, field mappings, calculation flows
- Trace number generation algorithms
- FeedId/RundownId mappings
- WIP section flows and material chains

## Table name mapping when migrating logic
| Reference | New Stack |
|-----------|-----------|
| `m_tank` | `m_sloc` |
| `m_tank_detail` | `m_sloc_detail` |
| `id_tank` | `id_sloc` |

## DO NOT copy code verbatim
- Understand the logic
- Implement in new Laravel 12 + PostgreSQL architecture
- Follow conventions in CLAUDE.md and modules-backend rules

## Key reference files
- `app/Models/RawMaterial.php` — RM entry number generation
- `app/Models/Wip.php` — WIP feed/rundown number generation
- `app/Models/Blending.php` — blending trace number + companion feed trace
- `app/Models/Packaging.php` — packaging + companion transfer trace
- `app/Models/Shipment.php` — shipment number generation
- `app/Models/Transfer.php` — transfer entry number
- `app/Models/Adjustment.php` — adjustment number generation (WIP + WHx)
