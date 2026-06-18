# 500 Error Diagnosis & Fix Guide — PostgreSQL Migration (Phase 5)
## Post-Migration Query Debugging Convention

> Status: **Active** — Applied after id_sloc JSONB→INTEGER migration
> Applies to: All transaction endpoints returning 500 (rm-entries, wip-entries, ts-stock, ts-transfer, etc)

---

## Problem Pattern

After column type migration (`id_sloc: JSONB → INTEGER`), queries fail with:
- `SQLSTATE[42883]: Undefined function` — operator `jsonb = integer` no longer valid
- `SQLSTATE[42803]: Grouping error` — columns missing from GROUP BY (PostgreSQL strict)
- Type mismatch in JOINs: `LEFT JOIN m_sloc t ON bh.id_sloc = t.id_sloc`

---

## Root Cause Checklist

Before fixing, verify:

```
□ id_sloc migration ran successfully (Migration 2026_06_18_000001)
□ Log shows no casting errors during migration
□ Column type is now INTEGER: SELECT data_type FROM information_schema.columns WHERE table_name='t_balance_header' AND column_name='id_sloc'
□ Sample data: SELECT DISTINCT id_sloc FROM t_balance_header LIMIT 3
```

---

## Step 1: Identify Failing Endpoint

From browser error → find backend endpoint:

```
GET /api/v1/transactions/rm-entries?page=1&per_page=5&id_plant=0 → 500
  ↓
backend/Modules/ts-raw/app/Repositories/...
```

**Rule:** Stack trace in laravel.log shows exact file:line

---

## Step 2: Find Query Using id_sloc

Search repository files for id_sloc usage:

```bash
# Find all id_sloc references in failing module
grep -r "id_sloc" backend/Modules/ts-raw/app/Repositories --include="*.php" | head -20
```

**Common patterns to fix:**

| Pattern | Issue | Fix |
|---------|-------|-----|
| `bh.id_sloc = t.id_sloc` | Type mismatch | Already handled by DbCompatTrait |
| `WHERE id_sloc IN (...)` | Need to verify param type | Check source of IN list |
| `GROUP BY` missing column | PostgreSQL strict | Add column to GROUP BY clause |
| `SUBSTRING()` on id_sloc | Only works on text | Use CAST first: `SUBSTRING(id_sloc::text, ...)`  |

---

## Step 3: Apply PostgreSQL-Specific Fixes

### Fix 3a: Type Casting in JOINs

**Status:** Already fixed by DbCompatTrait (do NOT duplicate)

```php
// ✅ Already handled by DbCompatTrait::dbSlocColumnClause()
$sql = "LEFT JOIN m_sloc t ON " . $this->dbSlocColumnClause('bh.id_sloc', 't.id_sloc');

// Result in PostgreSQL:
// LEFT JOIN m_sloc t ON CAST(bh.id_sloc AS TEXT) = CAST(t.id_sloc AS TEXT)
```

**Check:** Is `ForwardListQuery`, `RmEntryQueryTrait`, etc using DbCompatTrait?

```php
use Modules\Shared\Traits\DbCompatTrait;  // ← Must be present
```

### Fix 3b: GROUP BY Completeness

PostgreSQL requires ALL non-aggregated columns in GROUP BY.

```sql
-- ❌ FAIL (MySQL allows this)
SELECT bh.id, m.code FROM t_balance_header bh
LEFT JOIN m_material m ON bh.id_material = m.id_material
GROUP BY bh.id

-- ✅ FIX (PostgreSQL requires full GROUP BY)
SELECT bh.id, m.code FROM t_balance_header bh
LEFT JOIN m_material m ON bh.id_material = m.id_material
GROUP BY bh.id, m.code
```

**Search pattern:** `GROUP BY bh.id_` → ensure all SELECTed non-aggregates are in GROUP BY

### Fix 3c: SUBSTRING() on Non-Text Columns

After id_sloc becomes INTEGER, SUBSTRING() fails.

```php
// ❌ FAIL (id_sloc is now INTEGER)
WHERE SUBSTRING(id_sloc, 1, 1) = '1'

// ✅ FIX (cast first)
WHERE SUBSTRING(id_sloc::text, 1, 1) = '1'
// OR use CAST macro if available
WHERE SUBSTRING(CAST(id_sloc AS TEXT), 1, 1) = '1'
```

---

## Step 4: Affected Modules (Scan List)

Run this scan to find all 500-producing queries:

```bash
# Find repositories with direct id_sloc JOINs (no DbCompatTrait safety)
find backend/Modules -name "*.php" -path "*/Repositories/*" \
  -exec grep -l "id_sloc" {} \; | while read f; do
  if ! grep -q "DbCompatTrait" "$f"; then
    echo "⚠️ UNSAFE: $f (no DbCompatTrait)"
  fi
done
```

**Known affected (from current 500 errors):**

| Module | File | Issue |
|--------|------|-------|
| ts-raw | `RmEntryQueryTrait.php` | Check id_sloc casting in paginated queries |
| ts-wip | `WipEntryQueryTrait.php` | Check id_sloc in balance queries |
| ts-stock | `StockRepository.php` | Check id_sloc JOINs |
| trace-forward | `ForwardListQuery.php` | ✅ Already uses DbCompatTrait |
| trace-backward | `BackwardListQuery.php` | ✅ Already uses DbCompatTrait |

---

## Step 5: Fix Process

For each affected file:

### 5a. Verify DbCompatTrait Usage

```php
// ✅ Top of file should have:
use Modules\Shared\Traits\DbCompatTrait;

// ✅ Class should use trait:
class MyRepository {
    use DbCompatTrait;
    // ...
}
```

### 5b. Find id_sloc Usage

```bash
# In target file, find all occurrences
grep -n "id_sloc" RmEntryQueryTrait.php
```

### 5c: Fix JOIN Clauses

Replace raw joins with DbCompatTrait method:

```php
// ❌ BEFORE
$sql .= "LEFT JOIN m_sloc t ON bh.id_sloc = t.id_sloc";

// ✅ AFTER
$sql .= "LEFT JOIN m_sloc t ON " . $this->dbSlocColumnClause('bh.id_sloc', 't.id_sloc');
```

### 5d: Fix GROUP BY

Count all non-aggregate columns in SELECT, add to GROUP BY:

```php
// ❌ Incomplete GROUP BY
GROUP BY bh.id_balance_head, bh.trace_no

// ✅ Complete GROUP BY (if m.code is in SELECT)
GROUP BY bh.id_balance_head, bh.trace_no, m.code, m.description
```

### 5e: Fix SUBSTRING()

```php
// ❌ BEFORE
"WHERE SUBSTRING(bh.trace_no, 1, 1) = '1'"

// ✅ AFTER (if trace_no is integer)
"WHERE SUBSTRING(bh.trace_no::text, 1, 1) = '1'"
// OR use text cast macro
"WHERE SUBSTRING(CAST(bh.trace_no AS TEXT), 1, 1) = '1'"
```

---

## Step 6: Testing After Fix

For each fixed repository:

```php
// In tinker
$repo = app(\Modules\TsRaw\Repositories\RmEntryRepository::class);
$result = $repo->getList(['page' => 1, 'per_page' => 5]);
echo "✓ Query OK: " . count($result) . " rows\n";
```

Or via curl:

```bash
TOKEN=$(php artisan tinker <<'EOF'
echo App\Models\User::first()->createToken('test')->plainTextToken;
EOF)

curl -s "http://localhost:8000/api/v1/transactions/rm-entries?page=1" \
  -H "Authorization: Bearer $TOKEN" | jq '.status'
# Expected: 1 (success)
```

---

## Step 7: Commit Convention

After fixing each module, commit with:

```
[fix] PostgreSQL: Fix id_sloc type casting in {MODULE} queries

Module: {name}
Files: {list}
Changes:
- Add DbCompatTrait to {Repo} if missing
- Fix JOINs using dbSlocColumnClause()
- Complete GROUP BY clauses (PostgreSQL strict)
- Cast id_sloc to TEXT where needed (SUBSTRING, operators)

Result:
- {endpoint} no longer returns 500
- All queries type-safe for INTEGER id_sloc
```

---

## Checklist: All 500 Errors Fixed

Run endpoint audit:

```bash
#!/bin/bash
TOKEN=$(php artisan tinker <<'EOF'
echo App\Models\User::first()->createToken('test')->plainTextToken;
EOF)

ENDPOINTS=(
  "api/v1/transactions/rm-entries?page=1"
  "api/v1/transactions/wip-entries?page=1"
  "api/v1/transactions/ts-stock?page=1"
  "api/v1/transactions/ts-transfer?page=1"
)

for ep in "${ENDPOINTS[@]}"; do
  STATUS=$(curl -s "http://localhost:8000/$ep" \
    -H "Authorization: Bearer $TOKEN" | jq '.status' 2>/dev/null)
  
  if [ "$STATUS" = "1" ]; then
    echo "✅ $ep"
  else
    echo "❌ $ep (status=$STATUS)"
  fi
done
```

---

## Prevention: Post-Migration Code Review

Every repository file with database queries must:

1. **Use DbCompatTrait** if handling tank/storage/material JOINs
2. **Complete GROUP BY** (audit all GROUP BY clauses for completeness)
3. **Type-safe operations** (cast non-text columns before string operations)
4. **Test in PostgreSQL** (not just MySQL — syntax requirements differ)

---

## Reference: Available Helpers

From `DbCompatTrait`:

```php
// Type-safe column comparison
$this->dbSlocColumnClause($col1, $col2)
→ PostgreSQL: CAST($col1 AS TEXT) = CAST($col2 AS TEXT)
→ MySQL: ($col1 = $col2) OR ...

// Number formatting (handles GROUP_CONCAT vs STRING_AGG)
$this->dbNumberFormat('SUM(qty)', 3)
→ PostgreSQL: TO_CHAR(ROUND(CAST(...), 3), 'FM999999999999990.000')
→ MySQL: FORMAT(..., 3)

// String concatenation aggregation
$this->dbGroupConcat('col', ', ')
→ PostgreSQL: STRING_AGG(col, ', ')
→ MySQL: GROUP_CONCAT(col SEPARATOR ', ')
```

---

## Document: Issue Tracking

Log each fix with:

```markdown
### {Module} — {Endpoint}

**Error:** SQLSTATE[{code}]: {description}
**File:** {path}:{line}
**Root:** {cause — type mismatch | incomplete GROUP BY | etc}
**Fix:** {specific change applied}
**Status:** ✅ Fixed {date}
```

---

**Last Updated:** 2026-06-18  
**Next Review:** After all 500 errors resolved
