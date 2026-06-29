# Consolidation Brief — Cross-Module Duplication Fixes
> Agent implementation guide. Baca seluruh brief sebelum mulai coding.
> Last updated: 2026-06-29 — **STATUS: SEMUA TASK SELESAI. Brief ini untuk referensi historis.**

---

## Context

EODS project sudah migration dari Laravel legacy → Laravel 12 + Vue 3 SPA.
Audit cross-module menemukan **7 issue** yang perlu dikonsolidasi agar:
- Logic bisa diubah dari 1 tempat saja
- Bug tracking lebih mudah
- Trace number + balance logic tidak tersebar

**Jangan ubah apapun yang tidak ada di list ini.** Semua generation logic (trace, FIFO, rundown) sudah terpusat — jangan refactor ulang.

---

## Mandatory Reading (wajib baca sebelum coding)

```
.claude/CLAUDE.md                    ← project conventions (WAJIB)
.claude/.docs/ARCHITECTURE.md        ← module structure, DI rules
.claude/business-process.md          ← domain: trace number format, prefixes, plant codes
backend/Modules/Shared/app/Helpers/TraceHelper.php
frontend/resources/js/composables/useTransactionList.js
```

---

## Stack Quick Reference

- Backend: Laravel 12, PHP 8.3, PostgreSQL 17, `nwidart/laravel-modules`
- Frontend: Vue 3 + `<script setup>`, Pinia, Vuetify 3, Vite
- Frontend source root: `frontend/resources/js/` (alias `@`)
- Composables dir: `frontend/resources/js/composables/`
- Utils dir: `frontend/resources/js/utils/`
- No semicolons di JS/Vue (ESLint `semi: never`)
- `declare(strict_types=1)` wajib di semua PHP file

---

## Issue List (urutan prioritas)

---

### TASK B1 — Backend: Hapus inline plant code CASE di TransferRepository

**File:** `backend/Modules/ts-transfer/app/Repositories/TransferRepository.php`

**Problem:** 2 inline SQL CASE expression yang duplikasi logika dari `TraceHelper::plantNameExpression()` dan `TraceHelper::plantCondition()`.

**Location 1 — line ~113 (di dalam `$selectSql` string):**
```php
// SEKARANG (inline — hapus ini):
(CASE WHEN CHAR_LENGTH(CAST(a.trace_no AS VARCHAR)) >= 14 THEN SUBSTRING(a.trace_no, 11, 2) ELSE SUBSTRING(a.trace_no, 8, 2) END) AS plant_code_from_trace,

// GANTI DENGAN:
" . \Modules\Shared\Helpers\TraceHelper::plantCodeExpression('a.trace_no') . " AS plant_code_from_trace,
```

**Location 2 — line ~156 (di dalam `->leftJoin()` on clause):**
```php
// SEKARANG (inline — hapus ini):
$join->on(DB::raw('(CASE WHEN CHAR_LENGTH(CAST(a.trace_no AS VARCHAR)) >= 14 THEN SUBSTRING(a.trace_no, 11, 2) ELSE SUBSTRING(a.trace_no, 8, 2) END)'), '=', DB::raw('RIGHT(p.code_3, 2)'))

// GANTI DENGAN:
$join->on(DB::raw(\Modules\Shared\Helpers\TraceHelper::plantCodeExpression('a.trace_no')), '=', DB::raw('RIGHT(p.code_3, 2)'))
```

**Tapi:** `plantCodeExpression()` BELUM ADA di `TraceHelper.php`. Harus tambah method baru ini dulu.

**Tambah di `backend/Modules/Shared/app/Helpers/TraceHelper.php`:**
```php
/**
 * Build a SQL expression returning the 2-digit plant code from a trace number.
 * Returns SUBSTRING(col,11,2) for 14-digit, SUBSTRING(col,8,2) for 11-digit.
 * Use when you need the raw plant code value (not a human-readable name).
 *
 * @param string $col Column expression, e.g. "a.trace_no"
 */
public static function plantCodeExpression(string $col): string
{
    return "(CASE WHEN CHAR_LENGTH(CAST({$col} AS TEXT)) >= 14
                  THEN SUBSTRING(CAST({$col} AS TEXT), 11, 2)
                  ELSE SUBSTRING(CAST({$col} AS TEXT), 8, 2) END)";
}
```

**Letakkan setelah method `plantCondition()` (sekitar line 60).**

**Verifikasi:** `php artisan test --filter TransferRepository` harus pass. Jika tidak ada test, jalankan `php artisan serve` dan cek endpoint `/api/v1/transactions/transfers` via browser/Postman.

---

### TASK B2 — ~~Backend: Hapus SectionMappingService~~ ✅ DIBATALKAN

**Status:** DIBATALKAN — audit ulang menunjukkan `SectionMappingService` MASIH DIPAKAI.

**Caller aktif:**
- `backend/Modules/ts-wip/app/Repositories/Traits/WipEntryBatchTrait.php:16,26,134,144`

**Jangan hapus file ini.** Tidak ada aksi yang diperlukan.

---

### TASK F1 — Frontend: Buat `useLoadingState` composable

**File baru:** `frontend/resources/js/composables/useLoadingState.js`

**Problem:** Setiap store (12+ file) menduplikasi:
```js
const loading = ref(false)
const error = ref(null)
```
Dan setiap async action menduplikasi try/catch/finally pattern yang sama.

**Buat file baru:**
```js
import { ref } from 'vue'

/**
 * Reusable loading + error state with async wrapper.
 * Usage: const { loading, error, withLoading } = useLoadingState()
 */
export function useLoadingState() {
  const loading = ref(false)
  const error = ref(null)

  async function withLoading(fn) {
    loading.value = true
    error.value = null
    try {
      return await fn()
    } catch (e) {
      error.value = e?.response?.data?.message || e?.message || 'Terjadi kesalahan'
      throw e
    } finally {
      loading.value = false
    }
  }

  return { loading, error, withLoading }
}
```

**Stores yang harus diupdate (replace manual loading/error):**

| File | Current | After |
|------|---------|-------|
| `trace-forward/stores/traceForwardStore.js` | `loading = ref(false)` + try/catch | `useLoadingState()` |
| `trace-backward/stores/traceBackwardStore.js` | `loading = ref(false)` + try/catch | `useLoadingState()` |
| `m-adjustment/stores/adjustmentStore.js` | `loading = ref(false)` + try/catch | `useLoadingState()` |
| `ts-stock/stores/stockStore.js` | `loading = ref(false)` + try/catch | `useLoadingState()` |
| `ts-rmreport/stores/rmReportStore.js` | `loading = ref(false)` + try/catch | `useLoadingState()` |

**CATATAN:** Stores yang sudah pakai `useTransactionList` (ts-raw, ts-wip, ts-blending, ts-transfer, ts-package, ts-shipment) TIDAK perlu diubah — `useTransactionList` sudah punya `loading` dan `error` internal. Hanya stores yang TIDAK pakai `useTransactionList` yang perlu diupdate.

**Contoh update store:**
```js
// SEBELUM:
const loading = ref(false)
const error = ref(null)

async function fetchDetail(id) {
  loading.value = true
  error.value = null
  try {
    const res = await service.getDetail(id)
    detail.value = res.data.data
  } catch(e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

// SESUDAH:
const { loading, error, withLoading } = useLoadingState()

async function fetchDetail(id) {
  await withLoading(async () => {
    const res = await service.getDetail(id)
    detail.value = res.data.data
  })
}
```

**Verifikasi:** `npm run test:unit` harus pass. `npm run lint` harus pass.

---

### TASK F2 — Frontend: Buat `useTransactionAction` composable

**File baru:** `frontend/resources/js/composables/useTransactionAction.js`

**Problem:** 5 modul duplikasi pattern cancel/delete/submit:
- `ts-shipment/stores/useShipmentEntryStore.js` — `cancelEntry(id, traceNo)`
- `ts-package/stores/usePackageEntryStore.js` — `cancelEntry(id, traceNo)`
- `ts-transfer/stores/index.js` — `deleteTransfer(id)`
- `ts-blending/stores/index.js` — `deactivateBlending(id)`
- `ts-raw/stores/index.js` — `deleteTransfer(id)`

Pattern sama: loading → call API → refresh list → toast.

**Buat file baru:**
```js
import { useLoadingState } from '@/composables/useLoadingState'
import { useToastStore } from '@/stores/toast'

/**
 * Composable untuk action transaction (cancel, delete, submit).
 * @param {Function} apiFn - async function yang dipanggil (misal: service.cancel)
 * @param {Function} refreshFn - async function untuk refresh list setelah action
 * @param {string} successMsg - pesan toast sukses (opsional)
 */
export function useTransactionAction(apiFn, refreshFn, successMsg = 'Berhasil') {
  const { loading, error, withLoading } = useLoadingState()
  const toastStore = useToastStore()

  async function execute(...args) {
    await withLoading(async () => {
      await apiFn(...args)
      await refreshFn()
      toastStore.show(successMsg)
    })
  }

  return { loading, error, execute }
}
```

**Cara pakai di store:**
```js
// SEBELUM (ts-shipment):
async function cancelEntry(id, traceNo) {
  loading.value = true
  try {
    await shipmentService.cancel(id, traceNo)
    await fetchEntries()
    toastStore.show('Berhasil')
  } catch(e) { error.value = e }
  finally { loading.value = false }
}

// SESUDAH:
const { execute: cancelEntry } = useTransactionAction(
  (id, traceNo) => shipmentService.cancel(id, traceNo),
  fetchEntries,
  'Entry berhasil dibatalkan'
)
```

**Stores yang diupdate:** ts-shipment, ts-package, ts-transfer, ts-blending, ts-raw (untuk deleteTransfer saja).

**CATATAN:** Jika store punya cancel DAN delete yang berbeda, buat 2 instance composable dengan nama berbeda:
```js
const { execute: cancelEntry } = useTransactionAction(...)
const { execute: deleteEntry } = useTransactionAction(...)
```

---

### TASK F3 — Frontend: Standardize `getNewTraceNo` parameter order

**Problem:** `shipmentService` dan `packageService` punya parameter order terbalik — silent bug potential.

```js
// shipmentService.js:54 — (id_plant, id_material)
getNewTraceNo(id_plant, id_material)

// packageService.js:45 — (id_material, id_plant) ← TERBALIK
getNewTraceNo(id_material, id_plant)
```

**Fix — standardize ke object destructuring:**

**`frontend/resources/js/modules/ts-shipment/services/shipmentService.js` line ~54:**
```js
// SEBELUM:
getNewTraceNo(id_plant, id_material) {
  return axios.get('/api/v1/transactions/shipment-entries/new-trace-no', {
    params: { id_plant, id_material }
  })
},

// SESUDAH:
getNewTraceNo({ id_material, id_plant }) {
  return axios.get('/api/v1/transactions/shipment-entries/new-trace-no', {
    params: { id_plant, id_material }
  })
},
```

**`frontend/resources/js/modules/ts-package/services/packageService.js` line ~45:**
```js
// SEBELUM:
getNewTraceNo(id_material, id_plant) {
  return axios.get('/api/v1/transactions/package-entries/new-trace-no', {
    params: { id_material, id_plant }
  })
},

// SESUDAH:
getNewTraceNo({ id_material, id_plant }) {
  return axios.get('/api/v1/transactions/package-entries/new-trace-no', {
    params: { id_material, id_plant }
  })
},
```

**Update semua caller** di stores/views yang memanggil kedua function ini.
Cari dengan: `grep -rn "getNewTraceNo" frontend/resources/js/modules --include="*.js" --include="*.vue"`

Update setiap caller dari positional args ke object: `service.getNewTraceNo({ id_material, id_plant })`.

---

### TASK F4 — Frontend: Ekstrak `formatSupplier` ke shared util

**File baru:** `frontend/resources/js/utils/formatSupplier.js`

**Problem:** `formatSupplier()` identik di 2 komponen trace:
```
trace-backward/views/BackwardTraceView.vue:250 — identik
trace-forward/views/ForwardTraceView.vue:226    — identik (+ field `status`)
```

**Buat file baru:**
```js
/**
 * Parse supplier aggregate string dari backend (STRING_AGG result).
 * Format input: "SupplierName / BatchSAP / Qty : 1.234 MT"
 * Delimiter antar supplier: " | "
 *
 * @param {string} val - raw string dari kolom supplier backend
 * @returns {Array<{supplier: string, batch: string, qty: string, status: string}>}
 */
export function formatSupplierList(val, { withStatus = false } = {}) {
  if (!val) return []
  return val.split(' | ').map(item => {
    const parts = item.split(' / ')
    const result = {
      supplier: parts[0] || '',
      batch: parts[1] || '',
      qty: (parts[2] || '').replace('Qty : ', '').replace('Qty: ', ''),
    }
    if (withStatus) result.status = parts[3] || ''
    return result
  })
}

/**
 * Parse supplier string dari TraceDetailModal (delimiter ' || ').
 * Format input: "SupplierName / BatchSAP / Qty MT"
 * Delimiter antar supplier: " || "
 */
export function formatDetailSupplier(val) {
  if (!val) return []
  return val.split(' || ').map(item => {
    const parts = item.split(' / ')
    return {
      supplier: parts[0] || '',
      batch: parts[1] || '',
      qty: (parts[2] || '').replace(' MT', '').trim(),
    }
  })
}
```

**Update 3 file:**

1. `trace-backward/views/BackwardTraceView.vue`:
   - Hapus inline `formatSupplier()` function
   - Tambah import: `import { formatSupplierList } from '@/utils/formatSupplier'`
   - Rename call di template: `formatSupplier(row.supplier)` → `formatSupplierList(row.supplier)`

2. `trace-forward/views/ForwardTraceView.vue`:
   - Hapus inline `formatSupplier()` function
   - Tambah import: `import { formatSupplierList } from '@/utils/formatSupplier'`
   - Rename call: `formatSupplier(row.supplier)` → `formatSupplierList(row.supplier, { withStatus: true })`

3. `shared/components/TraceDetailModal.vue`:
   - Hapus inline `formatDetailSupplier()` function
   - Tambah import: `import { formatDetailSupplier } from '@/utils/formatSupplier'`
   - Fungsi `formatDetailSupplier` sudah tersedia langsung dari import

---

## Execution Order (recommended)

```
1. B1 — tambah TraceHelper::plantCodeExpression(), update TransferRepository  [backend, isolated]
2. B2 — grep confirm + delete SectionMappingService                           [backend, isolated]
3. F4 — buat formatSupplier.js util, update 3 views                           [frontend, isolated]
4. F3 — standardize getNewTraceNo params, update callers                      [frontend, isolated]
5. F1 — buat useLoadingState composable, update 5 stores                      [frontend, wide]
6. F2 — buat useTransactionAction composable, update 5 stores                 [frontend, wide, depends F1]
```

Tasks 1-4 bisa paralel (independent). Task 5 harus selesai sebelum task 6.

---

## Verification Checklist (per task)

Setelah tiap task selesai, verifikasi:

**Backend (B1, B2):**
- [ ] `php artisan test` — semua pass
- [ ] `php artisan serve` — tidak ada PHP error di startup
- [ ] Endpoint transfer list masih return data benar

**Frontend (F1-F4):**
- [ ] `npm run lint` — 0 errors
- [ ] `npm run test:unit` — semua pass
- [ ] `npm run dev` — tidak ada Vite compile error
- [ ] Buka browser localhost:5173 — trace backward dan forward masih tampil supplier dengan benar

---

## What NOT to Touch

```
❌ TraceNumberService.php — sudah terpusat, jangan ubah
❌ Feed.php / Rundown.php — sudah terpusat, jangan ubah
❌ WipEntryQueryTrait::mapFrontendSectionToDbFeedId() — ini yang aktif
❌ useTransactionList.js — composable ini sudah benar
❌ TraceHelper.php methods yang sudah ada — hanya tambah plantCodeExpression()
❌ BackwardTraceQuery / ForwardTraceQuery — CTE logic jangan diubah
❌ AdjustmentRepository:2118 dan RmEntryTransferTrait:227 — TODO terpisah, in-scope sprint lain
```

---

## Known TODOs (out of scope brief ini)

Dua issue ini diketahui tapi intentionally out-of-scope:

| File | Issue | Sprint |
|------|-------|--------|
| `m-adjustment/Repositories/AdjustmentRepository.php:2118` | ADJ-WH prefix 6 masih generate 11-digit | Sprint adjustment |
| `ts-raw/Repositories/Traits/RmEntryTransferTrait.php:227` | Transfer number hardcode RRR=000, PP=00 | Sprint transfer fix |

Lihat `business-process.md` §4.8 untuk spec ADJ-WH format yang benar (`6YYMMDDWHHPPSS`).
