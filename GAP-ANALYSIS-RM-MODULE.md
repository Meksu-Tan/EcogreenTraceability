# Gap Analysis: Raw Material Module — New vs Reference

Date: 2026-06-24
Project: EODS Refactoring (Laravel 12 + Vue 3 SPA)

---

## 1. Architecture Comparison

| Aspect | Reference (Blade/MVC) | New (Laravel 12 + Vue 3) | Status |
|--------|----------------------|--------------------------|--------|
| Pattern | Fat Model + Helper | Controller → Service → Repository | OK |
| DB layer | Raw SQL in models | Raw SQL in Repository traits | OK |
| Validation | Inline in controller | FormRequest classes | OK |
| DI | None | Interface injection | OK |
| Trace engine | `Feed.php` (Model extends) | `Feed.php` (plain class) | OK |
| Rundown engine | `Rundown.php` (Model extends) | `Rundown.php` (plain class) | OK |
| Cancellation | In `RawMaterial.php` | `TransactionCancellationService` | OK |
| Period lock | Inline per method | `PeriodLockService` | Exists but **not wired** |

**Verdict:** Architecture is correct. All core tables mapped correctly (`m_tank` → `m_sloc`, `id_tank` → `id_sloc`).

---

## 2. FIFO / Trace / Transaction — Equivalent

| Mechanism | Reference | New | Match? |
|-----------|-----------|-----|--------|
| FIFO order | `ORDER BY id_balance_head ASC` | Same | ✓ |
| FIFO within head | `ORDER BY id_balance_tail ASC` | Same | ✓ |
| Trace table | `t_trace_header` (from→to) | Same | ✓ |
| Trace detail | `t_trace_detail` per supplier | Same | ✓ |
| Balance header | `t_balance_header` (qty,in,out,init) | Same | ✓ |
| Balance detail | `t_balance_detail` per supplier | Same | ✓ |
| Feed engine | `Feed::generalFeed()` | Same | ✓ |
| Rundown engine | `Rundown::generalRundown()` | Same | ✓ |
| Rundown adjust | `adjustRundownToTotal()` | Same | ✓ |
| Orchestrator | Inline in controller | `FeedRundownOrchestrator` | ✓ |
| Temp table | `t_balance_temporary` | Same | ✓ |
| Matl document | `t_material_document` | Same | ✓ |

**Verdict:** FIFO/trace/transaction algorithms fully migrated. No logic gap.

---

## 3. CONFIRMED BUGS

### Bug A — Delete action "req failed" (HTTP 500)

**Severity:** Critical

**File:** `backend/Modules/Shared/app/Services/TransactionCancellationService.php:215-254`

**Root cause:** `deactivateRmEntry()` throws uncaught `Exception` on used-entry deactivation, propagating as HTTP 500.

**Flow:**
```
View(136): @click="deactivateEntry(entry.id_balance_head)"
→ View(392): store.deactivateEntry(id)
→ Store(114): rmEntryRepo.deactivate(id)
→ Service(116-119): rmEntryRepo->deactivateRmEntry(id, user)   ← no try-catch
→ Repo(443): TransactionCancellationService::deactivateRmEntry()
→ Cancellation(224-226): throw new Exception('RM Entry has been used...')  ← 500
```

**Missing vs Reference** (`RawMaterial.php:199-213`):
- No `PeriodLockService::isLocked()` check before processing
- No try-catch in controller (`RmEntryController.php:66-71`)
- No try-catch in service (`RmEntryService.php:116-119`)
- Reference returns numeric codes (`response => 99` for locked, `response => 3` for used) — never throws

**Same bug** in `deactivateFeedLogEntry()` (`TransactionCancellationService.php:331`).

**Fix:**
```php
public function destroy($id): JsonResponse
{
    try {
        $user = Auth::user()?->name ?? 'System';
        $result = $this->rmEntryService->deactivateRmEntry((int)$id, $user);
        return ApiResponse::success($result, 'RM Entry deactivated', 200);
    } catch (\Exception $e) {
        return ApiResponse::error($e->getMessage(), 400);
    }
}
```

And add period lock check inside `deactivateRmEntry()` before the used check:
```php
$head = DB::connection($this->connection)->table('t_trace_header')
    ->where('id_balance_head', $id)->where('status', 1)->first();
if ($head && $head->entry_date && PeriodLockService::isLocked($head->entry_date)) {
    throw new Exception('Cannot deactivate: period is locked');
}
```

---

### Bug B — Period lock stub always returns false

**Severity:** High

**File:** `backend/Modules/ts-raw/app/Repositories/Traits/RmEntryQueryTrait.php:249-256`

```php
public function getLockStatus(string $entryDate): bool
{
    $lock = DB::connection('eudr_ts')->table('m_plant')
        ->where('status', 1)->whereNotNull('id_sloc')->first();
    return false;  // ← ALWAYS returns false!
}
```

The query fetches a random plant (unrelated to lock checking) and hardcodes `return false`.

**Reference** (`RawMaterial.php:176-202`): Queries `t_report_pspa_head` for lock status.

**Fix:** Call `PeriodLockService::isLocked()`:
```php
public function getLockStatus(string $entryDate): bool
{
    return PeriodLockService::isLocked($entryDate);
}
```

---

### Bug C — Feed log missing manufacturer join

**Severity:** Medium

**File:** `backend/Modules/ts-raw/app/Repositories/Traits/RmEntryTransferTrait.php:232-266`

`getTankLog()` query (`getFeedLog` delegate) has no `m_manufacturer` join. The view at `RmEntryView.vue:214` renders:
```vue
<td class="text-caption">{{ log.manufacturer_name || '-' }}</td>
```

Always shows `-` because `manufacturer_name` is never selected.

**Fix:** Add `LEFT JOIN m_manufacturer` to the `getTankLog` query and select `manufacturer_name` in output mapping.

---

### Bug D — `deactivateRmEntry` doesn't restore source balance

**Severity:** High

**File:** `backend/Modules/Shared/app/Services/TransactionCancellationService.php:228-248`

**Reference** (`RawMaterial.php:1084-1098`): After deactivating a transfer entry, source storage tank balance is restored — `qty` incremented, `out_qty` decremented, both header and detail level.

**New code:** Only soft-deletes by setting `status=0`. No balance restoration.

Note: `deactivateFeedLogEntry()` (line 358-371) **does** restore source balance correctly. `deactivateRmEntry()` doesn't.

---

### Bug E — `created_at` may be NULL in Feed/Rundown trace headers

**Severity:** Low-Medium

**File:** `backend/Modules/Shared/app/Helpers/Feed.php:188-200` and `Rundown.php:77-89, 106-118`

Both helpers use `DB::connection()->table('t_trace_header')->insertGetId([...])` without including `created_at` in the insert array. Though the migration sets `DEFAULT CURRENT_TIMESTAMP`, the column is also `nullable()`. When `insertGetId()` omits `created_at`, the DB default should apply — but this behavior depends on the DB driver and column definition interaction.

The `getTankLog` query aggregates `MIN(a.created_at)` — if any grouped row has NULL `created_at`, `MIN()` produces NULL → blank in view.

**Fix:** Add `'created_at' => now()` to all `t_trace_header` inserts in Feed and Rundown.

---

## 4. Key Differences Summary

| Area | Reference | New | Gap |
|------|-----------|-----|-----|
| Error handling | Numeric codes (1,3,99) | Exception → 500 | **Bug** |
| Period lock on deactivate | Always checked (`t_report_pspa_head`) | Stub returns false | **Bug** |
| Deactivate restores balance | Yes (header + detail) | No (only in feed log) | **Bug** |
| Manufacturer in feed log | Present | Missing join | **Bug** |
| created_at in insert | Uses DB::insert without timestamps | Same — DB default may not fire | **Risk** |
| Stock inquiry | `Stock.php` with drilldown & CTEs | Separate module exists | OK |
| Forward/Backward trace | Recursive CTE | Separate modules (trace-fwd, trace-bwd) | OK |
| Supplier batch code gen | Custom algorithm | Same | OK |
| Trace number format (RM Entry prefix 1) | `1YYMMDD000PPSS` — RRR='000' hardcoded (correct per spec) | Same | OK |
| Trace number format (Transfer prefix 7) | `7YYMMDDRRR_PPSS` — RRR = `id_rundown` | RRR always '000' (wrong) | **Bug F** |

---

### Bug F — Transfer trace RRR always '000' (intra-plant RM transfer)

**Severity:** High

**Status:** STILL OPEN — partially addressed but root issue remains.

**Files involved:**
- `backend/Modules/ts-raw/app/Repositories/Traits/RmEntryTransferTrait.php:315-366` — `generateTransferNumber()` (prefix 7)
- `backend/Modules/ts-raw/app/Services/RmEntryService.php:179` — `transfer()` method, caller
- `backend/Modules/ts-raw/app/Http/Controllers/RmEntryController.php:162-168` — `transferNumber()` endpoint

**Domain context — what must NOT be hardcoded:**

Per `business-process.md §4.4`:
```
Transfer (prefix 7): Format = 7 YYMMDD RRR PP SS
RRR = 3 digit dari m_material.id_rundown   ← must come from material, NOT tank code
PP  = 2 digit plant code                   ← from actual id_plant
```

CONTRAST with RM Entry (prefix 1):
```
RM Entry (prefix 1): Format = 1 YYMMDD 000 PP SS
RRR = '000'   ← INTENTIONALLY hardcoded per spec (storage tank, no warehouse)
```
RRR='000' in RM Entry is CORRECT. In Transfer it is a BUG.

---

**Root cause analysis — what user's changes fixed vs what remains:**

User changes partially addressed: `$tracePlantCode` now uses `$plantId` (not hardcoded '00') and sequence reads from `t_trace_header` (not `t_balance_header`). These two sub-bugs are RESOLVED.

**Remaining Bug F-1 — Wrong argument type passed to `generateTransferNumber()`:**

`RmEntryService::transfer()` at line 179:
```php
$transferNo = $this->rmEntryRepo->generateTransferNumber($data['id_plant'], $sourceBalance->id_sloc);
//                                                                          ^^^^^^^^^^^^^^^^^^^^^^^^
//                                                        passes id_sloc int (e.g. 42) — wrong type
```

`generateTransferNumber()` in trait expects a tank **description string** to do:
```php
$targetDesc = str_ireplace('Storage', 'Feed', $tankDesc);  // expects "Storage Tank FT0113"
$tank = DB::table('m_sloc')->where('description', $targetDesc)->first();
```

Passing `id_sloc=42` (int) → `str_ireplace('Storage', 'Feed', 42)` → `42` (no match) → `where('description', '42')` → null → `$movSeq` stays `'000'` fallback.

**Result:** Every intra-plant RM transfer generates `7YYMMDD000PPss` — RRR is always '000'.

**Remaining Bug F-2 — Wrong RRR source even if description lookup succeeded:**

Even if fixed to pass description string, the approach is wrong per spec. `generateTransferNumber()` derives RRR from tank feed code via Storage→Feed name substitution — not from `m_material.id_rundown`. The correct approach (already implemented in `generateTransferEntryNo()` at line 588, used by ts-transfer module) is:

```php
$material = DB::table('m_material')->where('id_material', $materialId)->first();
$movSeq = str_pad($material->id_rundown, 3, '0', STR_PAD_LEFT);
```

---

**Cross-feature impact:**

| Feature | Impact |
|---------|--------|
| Intra-plant RM transfer (all) | `trace_no` RRR='000' — incorrect, non-standard trace |
| Backward trace (trace-backward module) | Reads `trace_no` from `t_trace_header` — RRR='000' in transfer rows breaks trace-chain RRR lookups |
| Forward trace (trace-forward module) | Same — chain tracing by RRR-match will misidentify transfer entries |
| ts-transfer module | Unaffected — uses `generateTransferEntryNo()` which correctly uses `id_rundown` |
| ts-wip feed consumption | Feed queries filter on `prefix IN (1,2,7,8,9)` — '000' RRR still consumed correctly since filter is on prefix not RRR |

The ts-wip feed consumption is NOT broken (RRR isn't part of the consumption filter). The trace visibility and audit trail IS broken.

---

**Fix:**

In `RmEntryTransferTrait::generateTransferNumber()`, replace tank description lookup for RRR with `m_material.id_rundown` lookup. Caller `RmEntryService::transfer()` must pass `$sourceBalance->id_material` (not `id_sloc`):

```php
// RmEntryService::transfer() — change line 179:
$transferNo = $this->rmEntryRepo->generateTransferNumber($data['id_plant'], $sourceBalance->id_material);
//                                                                           ^^^^^^^^^^^^^^^^^^^^^^^^^^

// RmEntryTransferTrait::generateTransferNumber() — replace RRR resolution:
$material = DB::connection('eudr_ts')->table('m_material')
    ->where('id_material', $materialId)
    ->first();
$movSeq = $material
    ? str_pad((string) $material->id_rundown, 3, '0', STR_PAD_LEFT)
    : '000';  // only fallback if material not found — log this case
```

This aligns with `generateTransferEntryNo()` (line 588) which is already correct.

---

## 5. Priority Fix Order

| Priority | Bug | File | Effort |
|----------|-----|------|--------|
| P0 | **A** — Delete 500 / uncaught Exception | `TransactionCancellationService.php`, `RmEntryController.php` | Small |
| P1 | **B** — Period lock stub | `RmEntryQueryTrait.php` | Trivial |
| P1 | **D** — Source balance not restored on deactivate | `TransactionCancellationService.php` | Medium |
| P1 | **F** — Transfer RRR always '000' (wrong type passed + wrong RRR source) | `RmEntryTransferTrait.php:315`, `RmEntryService.php:179` | Small |
| P2 | **C** — Manufacturer missing in feed log | `RmEntryTransferTrait.php` | Small |
| P3 | **E** — created_at NULL risk | `Feed.php`, `Rundown.php` | Trivial |

---

## 6. Reference Architecture Notes (for future migration)

The reference `RawMaterial.php` (1295 lines) handles all RM business in one file:
- `post_rmEntry()` — new RM entry (Rundown + adjust)
- `post_rmTrfEntry()` — transfer to feed tank (Feed + Rundown)
- `deactivateRmEntry()` — with lock check + used check
- `deactivateRmEntryTrf()` — with source balance restoration
- `getRmList()` — storage tank log query
- `getRmTrfList()` — feed tank log query (subquery with `tank_type`)
- `generateNumber()` — trace number generation

All table aliases and column names verified — the new implementation correctly uses:
- `id_sloc` for `id_tank`
- `m_sloc` for `m_tank`
- String `id_sloc` stored as JSON array
- `SUBSTRING(CAST(trace_no AS TEXT), ...)` for prefix extraction
