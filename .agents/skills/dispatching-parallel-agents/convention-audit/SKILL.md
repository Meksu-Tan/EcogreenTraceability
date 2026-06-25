---
name: convention-audit
description: >
  Audit codebase against project conventions: PostgreSQL compatibility, module structure,
  naming conventions, design system compliance, anti-patterns. Produces a findings report
  with file:line references and fix suggestions. Use when user says "audit conventions",
  "cek konsistensi", "audit kode", "check conventions", or invokes /convention-audit.
---

# Convention Audit Skill

Scan codebase for convention violations. Output structured findings with file:line and fixes.

## Trigger

User says: "audit conventions", "cek konsistensi kode", "audit kode", "check conventions", "konvensi audit", or invokes `/convention-audit [scope]`.

Scope options: `all` (default), `backend`, `frontend`, `sql`, `naming`, `architecture`.

## Convention Checklist

### 1. PostgreSQL Compatibility (CRITICAL)

Scan all PHP files in `backend/Modules/` for MySQL-specific patterns:

| Pattern | Problem | Fix |
|---------|---------|-----|
| `IFNULL(` | MySQL-only | `COALESCE(` |
| `GROUP_CONCAT(` | MySQL-only | `STRING_AGG(` via `dbGroupConcat()` |
| `LIKE` (case-insensitive) | Case-sensitive in PG | `ILIKE` |
| `YEAR(` | MySQL-only | `EXTRACT(YEAR FROM col)` |
| `CURDATE()` | MySQL-only | `CURRENT_DATE` |
| `IF(cond, a, b)` | MySQL-only | `CASE WHEN cond THEN a ELSE b END` |
| Backtick identifiers | MySQL-only | Remove or use double quotes |
| `COLLATE utf8mb4` | MySQL-only | Remove |
| `DATE_FORMAT(` | MySQL-only | `TO_CHAR(` |
| `ROUND(x AS type, N)` | MySQL syntax | `ROUND(CAST(x AS type), N)` |
| `LEFT(str, N)` with int | MySQL implicit cast | `SUBSTRING(str FROM 1 FOR N)` |

### 2. Module Structure

Check every module in `backend/Modules/<module>/`:

| Check | Expected |
|-------|----------|
| `Http/Controllers/` | Exists, injects Service |
| `Services/` | Exists, injects Repository interface |
| `Repositories/` | Interface + Implementation exist |
| `Providers/` | Binds interface → implementation in `register()` (not `boot()`) |
| `Http/Requests/` | FormRequest for each endpoint |
| `routes/api.php` | Under `auth:sanctum` + `plant.context`, prefix `api/v1` |
| Route order | Named routes before `{id}` wildcards |

### 3. PHP Conventions

| Check | Expected |
|-------|----------|
| `declare(strict_types=1)` | On every PHP file |
| `env()` usage | Only in `config/*.php`, never in Services |
| Constructor injection | No `new Class()` in Services |
| Interface injection | Inject interfaces, not concrete classes |
| `$fillable` | Untyped (not `protected array $fillable`) |
| Exception handling | Catch `\Throwable` not just `\Exception` |
| `dd()` / `var_dump()` / `die()` | Not in committed code |
| `number_format()` in API | Return raw numbers, format on frontend |

### 4. Table Naming

Scan all PHP and migration files:

| Check | Expected |
|-------|----------|
| `m_tank` | ❌ Never in new code — use `m_sloc` |
| `m_tank_detail` | ❌ Never in new code — use `m_sloc_detail` |
| `id_tank` | ❌ Never in new code — use `id_sloc` |
| `m_sloc` | ✅ Correct |
| `m_sloc_detail` | ✅ Correct |
| `id_sloc` | ✅ Correct |

### 5. Frontend Conventions

Scan all `.vue` and `.js` files in `frontend/src/`:

| Check | Expected |
|-------|----------|
| `<script setup>` | Composition API, not Options API |
| API calls | Through store → service → Axios, not from template |
| `console.log()` | Not in committed code |
| jQuery | Not used |
| `window.location.href` | Not in Axios interceptor |
| `document.documentElement.*` | Use `useTheme()`, `useLayout()` |
| Design tokens | Primary `#42B240`, Montserrat headings, Source Sans 3 body |
| Hardcoded URLs/credentials | Use `import.meta.env.*` |

### 6. API Response Format

| Check | Expected |
|-------|----------|
| Response helper | `ApiResponse::success()` / `ApiResponse::error()` |
| Double-nested data | `ApiResponse::success($data)` not `ApiResponse::success([$data])` |
| `number_format()` in response | Return raw, format on frontend |
| ResourceCollection | Use `$collection->toArray()` not `Resource::collection()->toArray()` |

### 7. SQL Alias Case Folding (PostgreSQL)

| Check | Expected |
|-------|----------|
| `AS camelCase` | Quote it: `AS "camelCase"` |
| Access `$result->camelCase` | Use `$result->{"camelCase"}` or lowercase alias |
| Raw DB queries | Check PDO fetch mode — lowercase keys with FETCH_ASSOC |

## Workflow

### Step 1 — Determine Scope

If scope not specified, audit all. If specified, focus on that area.

### Step 2 — Run Grep Searches

For each convention check, run grep/ripgrep across the codebase:

```bash
# PostgreSQL: find MySQL-specific functions
rg -n "IFNULL|GROUP_CONCAT|CURDATE\(\)|DATE_FORMAT|YEAR\(" backend/Modules/ --include "*.php"

# Table naming: find forbidden names
rg -n "m_tank|m_tank_detail|id_tank" backend/ frontend/ --include "*.php" --include "*.js" --include "*.vue"

# Anti-patterns
rg -n "dd\(|var_dump\(|die\(|console\.log\(" backend/ frontend/ --include "*.php" --include "*.js" --include "*.vue"

# env() outside config
rg -n "env\(" backend/Modules/ --include "*.php" | grep -v "config/"

# declare(strict_types=1)
rg -L "declare(strict_types=1)" backend/Modules/ --include "*.php"
```

### Step 3 — Read flagged files for context

Don't just report line numbers — read the surrounding code to confirm it's a real violation (not a false positive from test files, comments, or config).

### Step 4 — Produce Report

```markdown
## Convention Audit Report

**Date**: YYYY-MM-DD
**Scope**: all | backend | frontend | sql | naming | architecture
**Files scanned**: N

### Summary
- ❌ Critical: N (PostgreSQL incompatibility, forbidden table names)
- ⚠️ Warning: N (missing strict_types, env() outside config)
- 🔵 Info: N (style issues, naming inconsistencies)

### Findings

| # | Severity | File:Line | Convention | Issue | Fix |
|---|----------|-----------|------------|-------|-----|
| 1 | ❌ Critical | `Repository.php:42` | PostgreSQL | Uses `IFNULL()` | Change to `COALESCE()` |
| 2 | ⚠️ Warning | `Service.php:15` | PHP | Missing `declare(strict_types=1)` | Add at top of file |
```

### Step 5 — Prioritize

**Fix immediately** (Critical):
- MySQL-specific SQL (will break on PostgreSQL)
- `m_tank` / `m_tank_detail` references
- `env()` in Services (breaks after `config:cache`)
- Missing interface injection

**Fix soon** (Warning):
- Missing `declare(strict_types=1)`
- `dd()` / `console.log()` in code
- Double-nested ApiResponse
- `number_format()` in API responses

**Fix when touching** (Info):
- Naming inconsistencies
- Missing tests
- Style issues

## Stopping Condition

Audit complete when:
- All convention checks have been run against specified scope
- Every finding has file:line reference
- Critical issues have concrete fix suggestions
- Report delivered to user

## Output

Deliver as:
1. Markdown audit report (saved to `convention-audit-YYYY-MM-DD.md`)
2. Summary in chat with top critical findings
