# Transaction Module Consolidation Plan

> Dokumen ini merangkum hasil analisa cross-module terhadap modul transaksi EODS.
> Tujuan: identifikasi duplikasi logic, usulan konsolidasi, dan prioritas eksekusi.
> Last updated: 2026-06-11

---

## Modul yang Dianalisa

| Modul | Fungsi | Tipe |
|-------|--------|------|
| `ts-raw` | Raw Material Entry (RM Entry) | In-bound material |
| `ts-wip` | Work-in-Progress Entry (Feed & Rundown) | In-process movement |
| `ts-blending` | Blending — gabung beberapa material | In-process transformation |
| `ts-transfer` | Transfer antar tank/plant | In-process movement |
| `ts-package` | Packaging — WIP → FG | Out-bound conversion |
| `ts-shipment` | Shipment — FG → Customer | Out-bound delivery |

---

## Temuan: Pattern yang Berulang

### P1 — Feed → Rundown Workflow (4 modul IDENTIK)

`ts-raw`, `ts-wip`, `ts-blending`, `ts-transfer` semua mengeksekusi sequence **persis sama**:

```php
// Step 1: Feed (kurangi stock source)
$feedResult = Feed::generalFeed([
    'qty'            => $qty,
    'id_material'    => $materialId,
    'id_sloc'        => $sourceSloc,
    'to_trace_no'    => $traceNo,
    'entry_date'     => $date,
    'id_plant'       => $plantId,
    'user'           => $user,
    'trace_prefixes' => [1, 2, 7, 8, 9],
]);

// Step 2: Normalisasi proporsi supplier
Rundown::adjustRundownToTotal($supplierRows, $feedResult['total_out']);

// Step 3: Rundown (tambah stock destination)
Rundown::generalRundown([
    'trace_no'      => $destTraceNo,
    'from_trace_no' => $traceNo,
    'id_material'   => $materialId,
    'id_sloc'       => $destSloc,
    'in_qty'        => $feedResult['total_out'],
    'supplier_rows' => $supplierRows,
    'entry_date'    => $date,
    'id_plant'      => $plantId,
    'user'          => $user,
]);
```

`Feed.php` dan `Rundown.php` sudah di Shared — tapi **orchestration sequence** masih copy-paste di setiap modul.

---

### P2 — Material Document (4 modul, signature hampir sama)

| Modul | Method | Parameter Order |
|-------|--------|----------------|
| ts-raw | `saveMatlDoc()` | `(mode, id, number, user)` |
| ts-wip | `postMaterialDocument()` | `(mode, idTraceHead, matlDoc, user)` |
| ts-blending | `createMaterialDocument()` | `(user, idTraceHead, matlDoc, mode)` |
| ts-transfer | `createMaterialDocument()` | `(user, idTraceHead, matlDoc, mode)` |

Logic identik: insert atau update record di `t_material_document`. Hanya urutan parameter berbeda.

---

### P3 — Sub-tank Update (5 modul identik)

`updateEntrySubTank(user, idHead, tails[])` ada di: ts-raw, ts-wip, ts-blending, ts-transfer, ts-package.
Logic sama: update relasi sub-tank pada balance head.

---

### P4 — Period Lock Check (semua 6 modul)

Semua modul memanggil `PeriodLockService` sebelum save. Handling identik: kalau locked → return response code `99`.

---

### P5 — Tank Lookup Queries (4 modul, SQL hampir sama)

`getActiveTanksRundown()` dan `getActiveSpecificTanksRundown()` di ts-raw, ts-wip, ts-blending, ts-transfer.
SQL query hampir identik — hanya filter material/plant yang berbeda.

---

### P6 — Response Code Convention (semua modul, tanpa konstanta)

```
1  = Success
0  = Generic failure
2  = Duplicate entry
3  = Insufficient balance
4  = Insufficient stock
6  = No supplier traced
7  = Duplicate trace number
99 = Period locked
```

Sudah konsisten antar modul, tapi angka hardcoded di setiap service — tidak ada konstanta terpusat.

---

### P7 — Repository Structure Inconsistency

| Modul | Pola Repository |
|-------|----------------|
| ts-raw, ts-wip | **Trait composition** (QueryTrait, WriteTrait, dll) |
| ts-blending, ts-transfer, ts-package, ts-shipment | **Monolithic class** |

Makin baru modulnya, makin monolithic — class makin besar, sulit debug.

---

### P8 — Flag-based Dispatch vs REST (inkonsistensi routing)

| Modul | Pola |
|-------|------|
| ts-wip, ts-blending, ts-transfer | `POST /entries?flag=post_operationA` — 1 endpoint, 10+ operasi |
| ts-raw, ts-package, ts-shipment | Endpoint terpisah per operasi (RESTful) |

Flag-based menyulitkan debugging: satu endpoint bisa handle store, cancel, matl-doc, sub-tank sekaligus.

---

### P9 — Frontend: Pinia Store Shape Identik (semua 6 modul)

```js
// Semua 6 store punya ini persis:
const entries    = ref([])
const loading    = ref(false)
const pagination = ref({ page: 1, total: 0, perPage: 20 })
const STALE_TIME = 30000  // hardcoded di setiap store
```

Setiap store juga punya dropdown data (activeMaterials, tanks, warehouses, dll) yang di-load dengan pola sama.

---

### P10 — Frontend: Form Pattern Identik (semua 6 modul)

Field yang **selalu ada** di setiap form entry:
- `mode` — `ADD` / `EDIT`, readonly
- Entry/Trace Number — auto-generated, readonly
- Entry Date — date picker, required
- Material/Product — dropdown
- Tank/Sloc — dropdown
- Quantity — dengan balance validation
- Supplier/Batch secondary data

---

### P11 — Frontend: Table Column Pattern (semua 6 modul)

Kolom yang **selalu ada**: No., Trace No, Entry Date, Material/Product, Status, Actions.
Kolom yang **sering ada**: Plant, Matl Doc, Sloc, Init Qty, On-Hand, Supplier Info.

---

### P12 — Frontend: Action Flow Identik (semua 6 modul)

```js
fetchList()    → render table
storeEntry()   → POST + fetchList()
cancelEntry()  → DELETE + fetchList()
updateXxx()    → PUT + fetchList()
```

---

## Bug Aktif yang Ditemukan

> **Dua method dipanggil tapi TIDAK ADA di `Feed.php`:**

| Method | Dipanggil di | Status |
|--------|-------------|--------|
| `Feed::normalizeSupplierRundown()` | ts-wip, ts-package | **Fatal error jika dieksekusi** |
| `Feed::getDetailedFifoStock()` | ts-raw RmEntryController | **Fatal error jika dieksekusi** |

Kemungkinan method ini di-refactor keluar tapi caller belum diupdate.

---

## Rencana Konsolidasi

### Opsi A: Shared Service Layer — Low Risk, Recommended

Tidak perlu refactor arsitektur besar. Extract ke `Shared` module:

```
backend/Modules/Shared/
  app/
    Services/
      TransactionCoreService.php      ← period lock, matl doc, sub-tank update
      FeedRundownOrchestrator.php     ← Feed→Rundown sequence (ganti duplikasi di 4 modul)
    Repositories/
      TankQueryRepository.php         ← getActiveTanksRundown(), getActiveSpecificTanks()
    Constants/
      TransactionResponseCode.php     ← const SUCCESS = 1; PERIOD_LOCKED = 99; dll
```

Setiap modul inject dan call — tidak duplikasi logic. Debugging di satu tempat.

---

### Opsi B: Frontend Composables — Medium Risk

```
frontend/resources/js/composables/
  useTransactionList.js     ← fetchList, pagination, loading state
  useTransactionForm.js     ← form mode, entry number, period lock validation
  useTransactionStore.js    ← base store shape yang di-extend per modul
```

Menghapus boilerplate yang sama di 6 store/view.

---

### Opsi C: Abstract Base Service — Higher Risk (Future)

```php
abstract class BaseTransactionService {
    abstract protected function getTracePrefix(): int;

    public function createMaterialDocument(...): void { /* shared */ }
    public function updateSubTank(...): void { /* shared */ }
    public function checkPeriodLock(string $date): bool { /* shared */ }
    public function executeFeedRundown(array $source, array $dest): array { /* shared */ }
}
```

Setiap service extend dan implement hanya yang spesifik modulnya.

---

## Prioritas Eksekusi

| # | Action | Dampak | Risiko | File Target |
|---|--------|--------|--------|-------------|
| **P0** | Fix 2 missing methods di Feed.php | Bug aktif hilang | Sangat rendah | `Shared/Helpers/Feed.php` |
| **P1** | Buat `TransactionResponseCode` constants | Hapus 30+ angka hardcoded | Rendah | `Shared/Constants/TransactionResponseCode.php` |
| **P2** | Extract `createMaterialDocument()` ke Shared | Hapus 4 duplikasi | Rendah | `Shared/Services/TransactionCoreService.php` |
| **P3** | Extract `updateEntrySubTank()` ke Shared | Hapus 5 duplikasi | Rendah | `Shared/Services/TransactionCoreService.php` |
| **P4** | `FeedRundownOrchestrator` di Shared | Hapus 4 duplikasi orchestration | Sedang | `Shared/Services/FeedRundownOrchestrator.php` |
| **P5** | `TankQueryRepository` di Shared | Hapus 4 duplikasi SQL | Sedang | `Shared/Repositories/TankQueryRepository.php` |
| **P6** | Frontend `useTransactionList` composable | Hapus boilerplate 6 store | Sedang | `composables/useTransactionList.js` |
| **P7** | Standarisasi REST vs flag-based routing | Konsistensi semua modul | Sedang-Tinggi | routes/api.php per modul |

---

## Yang TIDAK Perlu Dikonsolidasi

Logic berikut memang unik per modul dan **tidak** harus digabung:

| Modul | Logic Unik |
|-------|-----------|
| ts-transfer | Approval workflow (submit → approve/reject/cancel) |
| ts-wip | DCS Quantifier auto-fill, Feed/Rundown branching |
| ts-package | FG product mapping, warehouse selection |
| ts-shipment | SAP integration, label generation, SO allocation |
| ts-raw | Supplier temp management, inter-plant transfer, FIFO sync check |
| ts-blending | Multi-source material staging, ratio calculation |

---

## Estimasi Pengurangan Code

Jika P0–P5 dieksekusi:

| Komponen | Perkiraan Baris Terhapus |
|----------|--------------------------|
| Duplikasi matl doc (4 modul × ~25 baris) | ~100 baris |
| Duplikasi sub-tank (5 modul × ~20 baris) | ~100 baris |
| Duplikasi Feed→Rundown orchestration (4 modul × ~40 baris) | ~160 baris |
| Duplikasi tank query (4 modul × ~30 baris) | ~120 baris |
| Hardcoded response codes (6 modul) | ~50 titik perubahan |
| **Total estimasi** | **~480 baris duplikasi dihapus** |

Setiap service modul berpotensi lebih ringkas 30–40%.
