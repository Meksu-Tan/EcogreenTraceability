# BRIEFING: Fix 500 Errors in Transaction Endpoints
## Immediate Action Plan (Phase 5 Completion)

---

## Situation

**3 endpoints returning 500 after id_sloc migration:**

```
1. GET /api/v1/transactions/rm-entries?page=1&per_page=5&id_plant=0
   → Module: ts-raw
   → Error: SQLSTATE[42883] operator type mismatch OR GROUP BY incomplete

2. GET /api/v1/transactions/wip-entries/balance?rundownId=001
   → Module: ts-wip
   → Error: Same as above

3. GET /api/v1/transactions/wip-entries/tanks/specific?sloc=53
   → Module: ts-wip
   → Error: Same as above
```

**Root Cause:** Queries assume id_sloc is JSONB; now it's INTEGER

---

## Priority

| Priority | Module | Endpoint | Impact |
|----------|--------|----------|--------|
| 🔴 P0 | ts-raw | rm-entries (main list) | Blocks raw material management |
| 🔴 P0 | ts-wip | wip-entries (main list) | Blocks WIP/manufacturing |
| 🟡 P1 | ts-wip | tanks/specific | Blocks tank lookup only |

---

## Action: Fix ts-raw (rm-entries)

**File:** `backend/Modules/ts-raw/app/Repositories/Traits/RmEntryQueryTrait.php`

**Step 1:** Check if DbCompatTrait is used

```bash
grep "DbCompatTrait" backend/Modules/ts-raw/app/Repositories/Traits/RmEntryQueryTrait.php
```

Expected: `use Modules\Shared\Traits\DbCompatTrait;`

**Step 2:** Find id_sloc JOIN in getList method

```bash
grep -n "id_sloc\|LEFT JOIN" backend/Modules/ts-raw/app/Repositories/Traits/RmEntryQueryTrait.php
```

**Step 3:** Replace raw JOINs with DbCompatTrait

```php
// Search for pattern:
"LEFT JOIN m_sloc ... ON bh.id_sloc = ..."

// Replace with:
"LEFT JOIN m_sloc ... ON " . $this->dbSlocColumnClause('bh.id_sloc', 't.id_sloc')
```

**Step 4:** Verify GROUP BY completeness

Look for `GROUP BY bh.id_...` and ensure all SELECTed non-aggregate columns are present.

**Step 5:** Test

```bash
php artisan tinker
$repo = app(\Modules\TsRaw\Repositories\RmEntryRepository::class);
$result = $repo->getList(['page' => 1, 'per_page' => 5]);
echo "✓ OK\n";
exit;
```

---

## Action: Fix ts-wip (wip-entries)

**File:** `backend/Modules/ts-wip/app/Repositories/Traits/WipEntryQueryTrait.php` (main) +  
         `backend/Modules/ts-wip/app/Repositories/Traits/WipEntryBatchTrait.php` (balance queries)

**Repeat same steps as ts-raw:**

1. Check DbCompatTrait present
2. Find id_sloc JOINs in getList + getBalance methods
3. Replace with dbSlocColumnClause()
4. Complete GROUP BY clauses
5. Test

**Test endpoints:**
- Main list: `/api/v1/transactions/wip-entries?page=1`
- Balance: `/api/v1/transactions/wip-entries/balance?rundownId=001&page=1`
- Tanks: `/api/v1/transactions/wip-entries/tanks/specific?sloc=53`

---

## Action: Check Other Modules (Scan)

```bash
# Find all repositories with id_sloc that might fail
find backend/Modules -name "*Repository*.php" -o -name "*QueryTrait.php" | \
while read f; do
  if grep -q "id_sloc" "$f" && ! grep -q "DbCompatTrait\|dbSlocColumnClause" "$f"; then
    echo "⚠️ UNSAFE: $f"
  fi
done
```

**Expected unsafe files to check:**
- ts-stock/StockRepository.php
- ts-transfer/TransferRepository.php
- m-adjustment/AdjustmentRepository.php (if uses id_sloc)

---

## Testing Checklist

After each module fix:

```bash
# Terminal 1: Start backend
cd backend && php artisan serve --port=8000

# Terminal 2: Get token and test
TOKEN=$(php artisan tinker <<'EOF'
echo App\Models\User::first()->createToken('test')->plainTextToken;
EOF)

# Test rm-entries
curl -s "http://localhost:8000/api/v1/transactions/rm-entries?page=1" \
  -H "Authorization: Bearer $TOKEN" | jq '.status'
# Expect: 1 (success)

# Test wip-entries
curl -s "http://localhost:8000/api/v1/transactions/wip-entries?page=1" \
  -H "Authorization: Bearer $TOKEN" | jq '.status'
# Expect: 1 (success)
```

---

## Commit Template

After fixing each module:

```
[fix] PostgreSQL: Fix id_sloc type casting in {module} list queries

Affected endpoints:
- GET /api/v1/transactions/{endpoint}

Changes:
- Add DbCompatTrait to {trait} if missing
- Replace raw id_sloc JOINs with dbSlocColumnClause()
- Complete GROUP BY clauses for PostgreSQL compatibility

Result:
- Endpoints no longer return 500
- All queries safe for INTEGER id_sloc column type

Tested:
- List query returns data without type errors
- GROUP BY clauses complete
```

---

## Timeline

| Step | Time | Owner |
|------|------|-------|
| 1. Fix ts-raw (rm-entries) | 10 min | — |
| 2. Fix ts-wip (wip-entries) | 10 min | — |
| 3. Scan other modules | 5 min | — |
| 4. Fix found unsafe repos | 15-30 min | — |
| 5. Full endpoint smoke test | 10 min | — |
| 6. Commit + push | 5 min | — |

**Total:** ~1-2 hours for all fixes

---

## Success Criteria

```
□ All transaction endpoints return HTTP 200 (not 500)
□ API responses have .status = 1 (success)
□ Data is correct: pagination, filters work
□ No type mismatch errors in logs
□ No GROUP BY incomplete errors in logs
□ Smoke test passes on all 3 modules
```

---

**Assigned to:** [next developer]  
**Blocks:** Frontend login → Dashboard (cannot load data)  
**Escalate if:** Cannot find id_sloc usage in repository, or fix breaks other queries
