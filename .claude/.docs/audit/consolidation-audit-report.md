# Audit Report: Cross-Module Duplication & Konsolidasi
> Compiled from 3 agents: Graphify (codebase mapping), Ponytail (over-engineering), Cavecrew (implementation brief)
> Date: 2026-06-29 | Project: EODS Refactoring

---

## Ringkasan

Generation logic (trace, FIFO, rundown) sudah **100% terpusat**. 7 issues ditemukan: 3 backend, 4 frontend. Total effort ~10h.

---

## Agent 1 — Graphify: Codebase Structure Mapping

**Corpus:** 725 files, ~479K words, 4721 nodes, 6838 edges.

### Findings
- **Sudah terpusat:** TraceNumberService, Feed::generalFeeds(), Rundown::generalRundown(), TraceHelper, TankQueryRepository, WipEntryQueryTrait
- **God nodes (most connected):** ApiResponse (240 edges), AuditService (51), PeriodLockService (49), AdjustmentRepository (44)
- **Surprising connections:** `debugFifoStock()` → `Feed`, `postMaterialFeed()` → `Feed`, `postMaterialRundown()` → `Rundown`
- **Potential duplication clusters:** Community 87 (Rundown + debugFifoStock), Community 88 (TraceHelper + TsReportRepository)

---

## Agent 2 — Ponytail: Over-Engineering Audit

**Corpus:** 126 files, ~84K words, 667 nodes, 889 edges. Focus: deletion over addition, YAGNI.

### Backend Issues

| ID | Issue | File | Fix |
|----|-------|------|-----|
| B1 | Inline plant code CASE duplikat | `TransferRepository.php:113,156` | Pakai `TraceHelper::plantCodeExpression()` |
| B2 | SectionMappingService orphaned (dead code) | `Shared/app/Services/SectionMappingService.php` | Hapus file + binding |
| B3 | ADJ-WH still generates 11-digit (known TODO) | `AdjustmentRepository.php:2118` | Out of scope sprint ini |

### Frontend Issues

| ID | Issue | Files | Fix |
|----|-------|-------|-----|
| F1 | loading/error boilerplate 12+ duplikasi | trace-forward/backward, m-adjustment, ts-stock, ts-rmreport | `useLoadingState()` composable |
| F2 | cancelEntry/deleteEntry identik 5 modul | ts-shipment, ts-package, ts-transfer, ts-blending, ts-raw | `useTransactionAction()` composable |
| F3 | getNewTraceNo parameter order terbalik | shipmentService vs packageService | Standardize ke object destructuring |
| F4 | formatSupplier 3 implementasi beda | ForwardTraceView, BackwardTraceView, TraceDetailModal | Ekstrak ke `utils/formatSupplier.js` |

---

## Agent 3 — Cavecrew: Implementation Brief (consolidation-brief.md)

Execution plan with 6 tasks, dependencies, verified against codebase.

### Mandatory Reading
- `.claude/CLAUDE.md`, `ARCHITECTURE.md`, `business-process.md`
- `TraceHelper.php`, `useTransactionList.js`

### Backend Tasks

#### B1: Hapus inline plant code CASE di TransferRepository
- **Tambahkan** `TraceHelper::plantCodeExpression()` method baru
- **Ganti** 2 inline CASE expressions di `TransferRepository.php`
- **Verifikasi:** `php artisan test --filter TransferRepository`

#### B2: Hapus SectionMappingService orphaned
- **Grep** dulu pastikan 0 caller
- **Hapus** `SectionMappingService.php` + binding di ServiceProvider

### Frontend Tasks

#### F1: Buat useLoadingState composable
- **File baru:** `composables/useLoadingState.js`
- **Update:** trace-forward, trace-backward, m-adjustment, ts-stock, ts-rmreport stores
- **Skip** stores yang sudah pakai `useTransactionList` (ts-raw, ts-wip, ts-blending, ts-transfer, ts-package, ts-shipment)

#### F2: Buat useTransactionAction composable
- **File baru:** `composables/useTransactionAction.js`
- **Depend on:** useLoadingState (F1 harus selesai dulu)
- **Update:** ts-shipment, ts-package, ts-transfer, ts-blending, ts-raw

#### F3: Standardize getNewTraceNo params
- **shipmentService.js:** `getNewTraceNo({ id_material, id_plant })`
- **packageService.js:** `getNewTraceNo({ id_material, id_plant })`
- **Update** semua caller di stores/views

#### F4: Ekstrak formatSupplier ke shared util
- **File baru:** `utils/formatSupplier.js` — 2 fungsi: `formatSupplierList`, `formatDetailSupplier`
- **Update:** ForwardTraceView, BackwardTraceView, TraceDetailModal

---

## Priority Matrix

| Priority | Issue | Effort | Impact | Agent |
|----------|-------|--------|--------|-------|
| HIGH | F1: useLoadingState composable | 2h | 12+ files simplified | Ponytail |
| HIGH | F2: useTransactionAction composable | 3h | 5 modules, 7 functions | Ponytail |
| MED | F3: standardize getNewTraceNo params | 1h | Silent bug prevention | Ponytail |
| MED | B1: TransferRepository pakai TraceHelper | 30m | 2 inline CASE removed | Graphify+Ponytail |
| MED | F4: shared formatSupplier util | 1h | 2 exact duplicates removed | Ponytail |
| LOW | B2: hapus SectionMappingService | 30m | Dead code cleanup | Ponytail |
| LOW | B3: ADJ-WH generate 14-digit | 2h | Known TODOs (out of scope) | Graphify |

---

## What NOT to Touch

```
TraceNumberService.php, Feed.php, Rundown.php       — sudah terpusat
WipEntryQueryTrait::mapFrontendSectionToDbFeedId()  — ini yang aktif dipakai
useTransactionList.js                                — composable sudah benar
BackwardTraceQuery / ForwardTraceQuery               — CTE logic jangan diubah
AdjustmentRepository:2118 / RmEntryTransferTrait:227 — TODO sprint lain
```

---

## Execution Order

```
1. B1 — TraceHelper::plantCodeExpression() + TransferRepository   [backend, isolated]
2. B2 — hapus SectionMappingService                                [backend, isolated]
3. F4 — formatSupplier.js + update 3 views                        [frontend, isolated]
4. F3 — standardize getNewTraceNo params + callers                [frontend, isolated]
5. F1 — useLoadingState + update 5 stores                         [frontend, wide]
6. F2 — useTransactionAction + update 5 stores                    [frontend, wide, depends F1]
```

Tasks 1-4 parallel. Task 5 before 6.

---

## Verification

- Backend: `php artisan test` pass, endpoint transfer list return benar
- Frontend: `npm run lint` 0 errors, `npm run test:unit` pass, `npm run dev` no compile error
