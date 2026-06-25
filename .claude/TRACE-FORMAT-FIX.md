# TRACE-FORMAT-FIX.md
## Audit & Fix: Dual Trace Number Format (11-digit ↔ 14-digit)

> Last updated: 2026-06-25  
> Branch: main  
> Base commit: `f96fbce2`

---

## Latar Belakang

Sistem punya **dua format trace number** di database:

| Format | Struktur | Keterangan |
|--------|----------|------------|
| **11-digit (legacy)** | `T` + `YYMMDD(6)` + `PP(2)` + `SS(2)` | Data lama, type 1 & 9 (RM & external receipt) |
| **14-digit (current)** | `T` + `YYMMDD(6)` + `WH(3)` + `PP(2)` + `SS(2)` | Semua data baru |

Keterangan kolom: `T`=type prefix, `WH`=warehouse/section (3 digit, `000`=storage), `PP`=plant 2 digit, `SS`=sequence 2 digit.

Data 11-digit **tidak pernah digenerate lagi** tapi **masih ada di DB** dan harus bisa dibaca + di-trace.

**Core helper** yang menangani dual-format:
- `backend/Modules/Shared/app/Helpers/TraceHelper.php` — SQL fragment builder
- `backend/Modules/Shared/app/Services/TraceNumberGeneratorService.php` — PHP parser/formatter

---

## Masalah yang Ditemukan

Beberapa module menggunakan `SUBSTRING(trace_no, X, Y)` di posisi > 1 **tanpa guard** untuk format 14-digit, sehingga jika terkena data 11-digit hasilnya salah/corrupt.

### File yang Diubah

---

### 1. `backend/Modules/ts-wip/app/Repositories/Traits/WipEntryBatchTrait.php`

**Metode yang difix:** `getFeedNewBatchNumber()`, `getRundownNewBatchNumber()`

**Masalah:** `SUBSTRING(a.to_trace_no,1,10)` mengasumsikan 14-digit (posisi 8–10 = warehouse). Jika ada trace 11-digit masuk ke query, `CONCAT(3, ?, ?)` tidak akan match karena panjang berbeda.

**Fix:** Tambah `TraceHelper::only14Digit()` sebagai WHERE guard sebelum SUBSTRING positional check.

**Revert:**
```diff
- // Legacy fallback — only 14-digit feed traces (type 3) exist by definition.
- $only14 = \Modules\Shared\Helpers\TraceHelper::only14Digit('a.to_trace_no');
- $rows = $this->executeSelect('
-     SELECT a.feed_number
-       FROM (SELECT a.to_trace_no+1 AS feed_number
-               FROM t_trace_header a
-              WHERE ' . $only14 . '
-                AND SUBSTRING(a.to_trace_no,1,10) = CONCAT(3, ?, ?)
-                ...

+ $rows = $this->executeSelect('
+     SELECT a.feed_number
+       FROM (SELECT a.to_trace_no+1 AS feed_number
+               FROM t_trace_header a
+              WHERE SUBSTRING(a.to_trace_no,1,10) = CONCAT(3, ?, ?)
+                ...
```

Untuk `getRundownNewBatchNumber()` — sama, hapus baris `$only14 = ...` dan `AND ' . $only14 . '` dari kedua branch (`if ($section === '9' || $section === '8')` dan `else`).

---

### 2. `backend/Modules/ts-wip/app/Repositories/Traits/WipEntryQueryTrait.php`

**Metode yang difix:** `getSectionOverview()` (section summary query)

**Masalah:** 6 subquery menggunakan `SUBSTRING(to_trace_no,8,3) = m.id_rundown` — posisi 8 = warehouse field yang hanya ada di 14-digit. Pada 11-digit, posisi 8–9 adalah plant code, bukan warehouse.

**Fix:** Tambah `TraceHelper::only14Digit()` ke setiap subquery.

**Revert:** Di blok query section overview (sekitar baris 719–748), hapus semua baris:
```sql
AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "
```
(ada 6 baris, satu untuk setiap `SELECT ... FROM t_trace_header WHERE SUBSTRING(to_trace_no,1,1) = '2'/'3'`)

---

### 3. `backend/Modules/ts-blending/app/Repositories/BlendingRepository.php`

**Metode yang difix:** `getBlendingList()`

**Masalah:** `SUBSTRING(to_trace_no FROM 9 FOR 1) <> '0'` — posisi 9 = karakter pertama warehouse field (14-digit). Blending type `8` selalu 14-digit, tapi guard ini bisa gagal jika data 11-digit masuk filter.

**Fix:** Tambah `TraceHelper::only14Digit()` pada dua tempat di `$traceSubquery`:
1. `is_last_row` subquery inner WHERE
2. JOIN subquery inner WHERE  

**Revert:** Cari dan hapus dua baris:
```php
" AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "
" AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('c.to_trace_no') . "
```

---

### 4. `backend/Modules/ts-blending/app/Services/BlendingService.php`

**Metode yang difix:** `getBlendingTraceData()`

**Masalah:** `SUBSTRING(to_trace_no,13,2) = ?` — posisi 13–14 = sequence field, hanya ada di 14-digit.

**Fix:** Tambah `AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "` sebelum baris warehouse condition.

**Revert:** Hapus baris:
```php
   AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "
```
dari `$sql` string di method `getBlendingTraceData()`.

---

### 5. `backend/Modules/ts-package/app/Repositories/EloquentPackageRepository.php`

**Metode yang difix:** `store()`

**Masalah:** `SUBSTRING(a.to_trace_no,1,12)` dan `SUBSTRING(a.to_trace_no,13,2)` di query generate batch number. Posisi 13 hanya valid untuk 14-digit.

**Fix:** Tambah `TraceHelper::only14Digit()` guard di subquery inner WHERE pada `$datPckBatch`.

**Revert:** Hapus baris:
```php
                         WHERE " . \Modules\Shared\Helpers\TraceHelper::only14Digit('a.to_trace_no') . "
```
dari query `$datPckBatch` di method `store()`.

---

### 6. `backend/Modules/ts-shipment/app/Repositories/EloquentShipmentRepository.php`

**Dua perubahan:**

#### 6a. `getDtShipEntry()` — `next_process` subquery
**Masalah:** `SUBSTRING(from_trace_no, 8, 3) = '001'` — mencari "shipment origin dari packaging" berdasarkan warehouse field posisi 8. Posisi ini berbeda di 11-digit.

**Fix:** Ganti dengan `TraceHelper::warehouseCondition('from_trace_no', '<>', '000')` — dual-format safe.

**Revert:**
```diff
- WHERE SUBSTRING(from_trace_no, 1, 1) = '4'
-   AND " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('from_trace_no', '<>', '000') . "

+ WHERE SUBSTRING(from_trace_no, 8, 3) = '001'
+   AND SUBSTRING(from_trace_no, 1, 1) = '4'
```

#### 6b. `store()` — generate trace number shipment
**Masalah:** `SUBSTRING(a.to_trace_no,1,12)` dan `SUBSTRING(a.to_trace_no,13,2)` di `$datPckBatch`. Sama seperti ts-package.

**Fix:** Tambah `TraceHelper::only14Digit()` guard.

**Revert:** Hapus baris:
```php
                         WHERE " . \Modules\Shared\Helpers\TraceHelper::only14Digit('a.to_trace_no') . "
```
dari `$datPckBatch` query di `store()`.

---

### 7. `backend/Modules/ts-transfer/app/Repositories/TransferRepository.php`

**Metode yang difix:** `getTransferList()` — `$traceSubquery` untuk `is_last_row`

**Masalah:** `CAST(SUBSTRING(c.to_trace_no,9,1) AS INTEGER) <> 0` — posisi 9 = karakter pertama warehouse field di 14-digit. Transfer type `7` selalu 14-digit tapi bisa JOIN ke RM 11-digit.

**Fix:** Tambah `TraceHelper::only14Digit()` sebelum SUBSTRING posisi 9.

**Revert:** Hapus:
```php
" AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('c.to_trace_no') . "
```
dari `$traceSubquery` string, pada baris `is_last_row` MAX(CASE WHEN ...).

---

## Cara Revert Semua Sekaligus

Jika ingin revert semua 7 file ke kondisi sebelum fix:

```bash
git checkout f96fbce2 -- \
  backend/Modules/ts-wip/app/Repositories/Traits/WipEntryBatchTrait.php \
  backend/Modules/ts-wip/app/Repositories/Traits/WipEntryQueryTrait.php \
  backend/Modules/ts-blending/app/Repositories/BlendingRepository.php \
  backend/Modules/ts-blending/app/Services/BlendingService.php \
  backend/Modules/ts-package/app/Repositories/EloquentPackageRepository.php \
  backend/Modules/ts-shipment/app/Repositories/EloquentShipmentRepository.php \
  backend/Modules/ts-transfer/app/Repositories/TransferRepository.php
```

> **Catatan:** `f96fbce2` adalah commit sebelum fix ini. Perintah ini hanya checkout 7 file tersebut, tidak mengubah file lain.

---

## Yang TIDAK Perlu Difix (Aman)

| Module | Kenapa Aman |
|--------|-------------|
| `trace-backward` | Traversal via FK (`from_trace_no` → `to_trace_no`), tidak parse posisi |
| `trace-forward` (detail/traversal) | FK traversal, aman |
| `ts-raw` | Sudah pakai `TraceHelper` di semua query positional |
| `ts-stock` | `SUBSTRING(trace_no,8,2)` hanya pada type 4/5/6 — semua 14-digit by definition |
| `ts-tsreport` | Hanya pakai `SUBSTRING(trace_no,1,1)` type prefix; SUBSTRING lain pada `qtf_feed`/`qtf_rundown` |
| `m-adjustment`, `m-quantifier` | Tidak query trace_no secara struktural |

---

## Fix Tambahan (Audit Post-User, 2026-06-25)

### 8. `backend/Modules/trace-forward/app/Repositories/Concerns/ForwardListQuery.php`

**Metode:** `execute()` — list query RM starting points untuk Forward Trace

**Masalah:** `SUBSTRING(bh.trace_no,8,3) = '000'` — filter "storage entry" dengan positional parse. Pada 11-digit, pos 8-9 = plant code sehingga **tidak pernah match '000'** → semua 11-digit RM tidak muncul di Forward Trace list.

Note: TRACE-FORMAT-FIX.md versi sebelumnya **salah** mengklaim `trace-forward` aman sepenuhnya. List query ini berbeda dari traversal query.

**Fix:** Ganti dengan `TraceHelper::isStorageOrLegacy('bh.trace_no')`.

---

### 9. `backend/Modules/ts-rmreport/app/Repositories/RmReportRepository.php`

**Metode:** Query utama RM annual summary (baris ~227)

**Masalah:** `SUBSTRING(CAST(a.trace_no AS TEXT),8,2) = '00'` — pada 11-digit, pos 8-9 = plant code. Hanya record all-plant (plant code = '00') yang lolos; per-plant 11-digit RM hilang dari report tahunan.

**Fix:** Ganti dengan `TraceHelper::isStorageOrLegacy('a.trace_no')`.

---

### 10. `backend/Modules/ts-transfer/app/Repositories/TransferRepository.php`

**Metode:** `getNextSequence()` — lookup max sequence untuk generate transfer trace number

**Masalah:** Parse pos 8, 11, 13 tanpa `only14Digit` guard. Transfer type 7 kemungkinan tidak ada di era 11-digit, tapi tanpa guard jika ada data corrupt bisa return wrong max_seq.

**Fix:** Tambah `TraceHelper::only14Digit('to_trace_no')` sebagai guard pertama di WHERE clause.

---

## Prinsip Fix

Semua fix pakai satu pola:

```php
// Sebelum query yang pakai posisi > 7, tambahkan guard:
\Modules\Shared\Helpers\TraceHelper::only14Digit('kolom_trace_no')

// Fungsi ini menghasilkan SQL:
// CHAR_LENGTH(CAST(kolom AS CHAR)) >= 14
```

Guard ini memastikan row 11-digit **dilewati** (bukan di-process dengan logic 14-digit yang hasilnya salah).
