# TD-3: Raw SQL in Repositories

> **Filed:** 2026-06-13  
> **Priority:** Medium  
> **Impact:** These repositories contain raw SQL that should be migrated to QueryBuilder/Eloquent for consistency, testability, and maintainability. Many of these are complex SELECTs with FOR UPDATE locks or dynamic WHERE builders that require careful handling.

---

## BlendingRepository

**File:** `backend/Modules/ts-blending/app/Repositories/BlendingRepository.php`

| Lines | Raw SQL | Complexity | Notes |
|-------|---------|------------|-------|
| 44 | `SELECT id_balance_head, qty ... WHERE ... FOR UPDATE` | High | SELECT in transaction with FOR UPDATE lock |
| 56 | `SELECT id_balance_head, qty, out_qty ...` | Medium | Balance header lookup, could use QB |
| 82 | `SELECT id_balance_tail, id_supplier ... WHERE ... FOR UPDATE` | High | FOR UPDATE inside transaction |
| 109 | `SELECT id_balance_tail ...` | Medium | Simple balance tail lookup |
| 118 | `SELECT id_balance_tail ...` | Medium | Balance tail lookup |
| 134, 147 | `SELECT ... FROM t_formula ...` | Low-Medium | Formula read queries |
| 176, 200, 205 | Various `SELECT` with JOINs and `LATERAL` | Medium | Material/tank queries |
| 274 | `SELECT ... FROM t_blending_products ...` | Low | Simple SELECT |
| 302 | `SELECT ... FROM t_formula ...` | Low | Formula lookup |
| 312 | `SELECT description, id_plant FROM m_tank` | **Low -- EASY CONVERSION** | Simple single-table WHERE |
| 319 | `SELECT ... FROM t_trace_header ... LEFT JOIN ...` | Medium | JOIN + WHERE |
| 332 | `INSERT INTO t_blending_header (...) VALUES (...)` | Low | INSERT -- already uses QB for tracing |
| 343 | `DELETE FROM t_blending_header WHERE ...` | Low | DELETE |
| 358, 364 | `INSERT ... SELECT MAX(...)` | Medium | INSERT + MAX subquery |
| 375 | `SELECT ... FROM t_trace_header ... FOR UPDATE` | High | FOR UPDATE |
| 387, 424, 428 | `UPDATE t_blending_header ...`, `UPDATE t_trace_header ...` | **Low -- EASY CONVERSION** | Simple single-table WHERE UPDATE |
| 406 | `SELECT MAX(entry_date) FROM t_trace_header` | Low | MAX aggregation |
| 434 | `SELECT ... FROM t_trace_header` | Medium | JOIN + WHERE |
| 443 | `SELECT ... FROM t_balance_header ... FOR UPDATE` | High | FOR UPDATE |
| 452, 458 | `UPDATE t_balance_header ...`, `SELECT ... FROM t_trace_tail ... FOR UPDATE` | High | UPDATE + FOR UPDATE |
| 465, 474 | `SELECT ... FROM t_balance_detail ... FOR UPDATE`, `UPDATE ...` | High | FOR UPDATE + UPDATE |
| 480, 486 | `UPDATE t_balance_detail ...`, `UPDATE t_trace_detail ...` | **Low -- EASY CONVERSION** | Simple single-table UPDATE |
| 493, 497 | `UPDATE t_balance_header ...` | **Low -- EASY CONVERSION** | Simple UPDATE |
| 513, 522, 526, 530 | `SELECT ... FOR UPDATE`, `UPDATE ...` | High | FOR UPDATE + UPDATE in transaction |
| 544, 554, 565, 575 | Various `SELECT` | Low-Medium | Read queries |
| 583 | `INSERT INTO t_adjustment_header` | Low | INSERT |

**Easy wins (low risk, straightforward):**
- Line 312: `SELECT description, id_plant FROM m_tank WHERE id_tank = ?`
- Lines 387, 424, 428, 452, 474, 480, 486, 493, 497, 522, 526, 530: Simple UPDATE with single WHERE

**Must keep as raw SQL:**
- FOR UPDATE queries inside transactions (lines 44, 82, 375, 443, 458, 465, 513)
- Complex INSERT ... SELECT ... MAX subqueries (line 364)
- Queries with LATERAL joins (lines 176, 200, 205)

---

## TransferRepository

**File:** `backend/Modules/ts-transfer/app/Repositories/TransferRepository.php`

| Lines | Raw SQL | Complexity | Notes |
|-------|---------|------------|-------|
| 44 | `SELECT ... FROM t_transfer_header ...` | Low | Simple JOIN SELECT |
| 56 | `SELECT ... FROM t_trace_header ... LEFT JOIN ... WHERE ... IN (...)` | Medium | Dynamic IN clause |
| 87 | `SELECT ... FROM t_balance_header ... WHERE ... AND JSON_CONTAINS(...)` | Medium | JSON_CONTAINS makes QB impractical |
| 110 | `SELECT ... FROM t_trace_header ... WHERE ... FOR UPDATE` | High | FOR UPDATE |
| 259, 269 | `SELECT id_balance_detail ...` | Low | Simple SELECT |
| 288 | `SELECT description, id_plant FROM m_tank WHERE id_tank = ?` | **Low -- EASY CONVERSION** | Simple single-table |
| 295 | `SELECT ... FROM t_trace_header` | Medium | JOIN with subquery |
| 314 | `SELECT id_trace_head ... MAX(seq_no) ...` | Medium | SELECT MAX with JOIN |
| 326 | `SELECT id_plant FROM m_plant WHERE plant_code = ?` | **Low -- EASY CONVERSION** | Simple single-table |
| 330 | `SELECT ... FROM t_trace_header ... WHERE ... = ?` | Low | Simple WHERE |
| 345 | `INSERT INTO t_transfer_header` | Low | INSERT |
| 357 | `INSERT INTO t_trace_header` | Low | INSERT |
| 363 | `SELECT MAX(id_trace_head) ...` | Low | MAX aggregation |
| 375 | `SELECT ... FROM t_trace_header ... WHERE ... FOR UPDATE` | High | FOR UPDATE |
| 387 | `UPDATE t_transfer_header SET ...` | **Low -- EASY CONVERSION** | Simple UPDATE |
| 407, 409, 415, 421 | Transaction begin/rollback | Structure | Not raw SQL |
| 437, 441 | `UPDATE t_trace_detail ...` | **Low -- EASY CONVERSION** | Simple UPDATE |
| 446 | `SELECT ... FROM t_trace_header ... FOR UPDATE` | High | FOR UPDATE |
| 468 | `SELECT ... FROM t_balance_header ... FOR UPDATE` | High | FOR UPDATE |
| 477 | `UPDATE t_balance_header SET qty = ?, out_qty = ?` | **Low -- EASY CONVERSION** | Simple UPDATE |
| 482, 488, 492, 499 | Various SELECT/UPDATE | High | FOR UPDATE + UPDATE |
| 506, 515, 521, 527, 533 | Various SELECT/UPDATE | High | FOR UPDATE + UPDATE |
| 537, 543, 559, 563, 567, 571, 578, 601, 604 | Various SELECT/UPDATE | High | Transaction with FOR UPDATE |
| 617, 626, 631, 636, 641 | SELECT + UPDATE + INSERT | Medium | Stock update after transfer cancel |
| 656, 665 | SELECT + INSERT queries | Low | Read queries |

**Easy wins (low risk, straightforward):**
- Line 288: `SELECT description, id_plant FROM m_tank WHERE id_tank = ?`
- Line 326: `SELECT id_plant FROM m_plant WHERE plant_code = ?`
- Lines 387, 437, 441, 477, 521, 527: Simple single-table UPDATE

**Must keep as raw SQL:**
- FOR UPDATE queries inside transactions
- JSON_CONTAINS predicates
- Dynamic IN clauses with variable-length parameter lists

---

## Feed.php (Shared Helper)

**File:** `backend/Modules/Shared/app/Helpers/Feed.php`

The raw SQL SELECT queries in `generalFeed()`, `getAvailableQty()`, and `debugStock()` use dynamic JSON_CONTAINS and SUBSTRING WHERE clauses impractical for QueryBuilder. The UPDATE calls (lines 180-182, 267-269) have already been converted to QueryBuilder.

Remaining raw SQL:
- `generalFeed()` SELECT + FOR UPDATE (transactional, JSON_CONTAINS)
- `getAvailableQty()` SELECT (JSON_CONTAINS)
- `debugStock()` SELECT (JSON_CONTAINS)

These should stay raw until the JSON predicate builder is extracted into a shared scope class.

---

## Rundown.php (Shared Helper)

**File:** `backend/Modules/Shared/app/Helpers/Rundown.php`

The raw SQL in `generalRundown()` involves JSON_CONTAINS and FOR UPDATE queries. The UPDATE calls (lines 181-188) have already been converted to QueryBuilder.

Remaining raw SQL:
- SELECT with JSON_CONTAINS + FOR UPDATE (line 40-48, 52-61)
- SELECT with FOR UPDATE for supplier dedup (line 134-142)

These should stay raw due to JSON_CONTAINS and lock requirements.

---

## Recommended Approach for Future Sprint

1. **Start with easy wins:** Convert simple single-table SELECT and UPDATE queries (marked above)
2. **Extract JSON predicate builder:** Create a shared scope class that builds JSON_CONTAINS conditions → then all Feed/Rundown queries become convertible
3. **Wrap FOR UPDATE queries last:** These need careful testing because locking behavior can change

**Total remaining raw SQL calls (all files):** ~100+
**Already converted (TD-2):** 4 calls (Feed lines 180-182, 267-269; Rundown lines 181-188)
