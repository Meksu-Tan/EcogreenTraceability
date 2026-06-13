# Trace Number Summary per Fitur

> Terverifikasi dari: `reference-dont-change/` code + `.claude/business-process.md`
> Last updated: 2026-06-08

## Format Universal

```
[ D ] [ YY ] [ MM ] [ DD ] [ RRR ] [ PP ] [ SS ]
  1      2     3     4      5-7     8-9   10-11   ← posisi digit (business-process.md)
  1      6 digit (yymmdd)    3 digit  2 digit  2 digit

Total: 14 digit (semua numerik)
```

| Posisi | Panjang | Nama Field | Keterangan |
|--------|---------|------------|------------|
| 1 | 1 digit | **Type of Movement** | Jenis pergerakan material (1–9) |
| 2–7 | 6 digit | **Date** | Format `yymmdd` — contoh: `260604` = 4 Juni 2026 |
| 8–10 | 3 digit | **Rundown / WH Number** | Nomor material rundown atau nomor warehouse (3 digit) |
| 11–12 | 2 digit | **Plant Code** | Dua digit terakhir dari kode plant |
| 13–14 | 2 digit | **Sequence No** | Nomor urut dokumen |

**Catatan:** Beberapa fitur menggunakan format parsial (tidak selalu 14 digit penuh), terutama Adjustment WHx (prefix 6).

---

## Ringkasan Prefix

| Prefix | Kode | Fitur | Keterangan |
|--------|------|-------|------------|
| **1** | RM-STORE | RM Entry | Penerimaan & penyimpanan bahan baku, transfer antar storage |
| **2** | RM-RUNDOWN | WIP Rundown | Material turun dari storage ke area WIP / produksi |
| **3** | MAT-FEED | WIP Feed | Material masuk ke proses produksi (feeding ke seksi) |
| **4** | WIP-OUT | Packaging | Produk WIP keluar ke warehouse melalui PPH |
| **5** | SHIPMENT | Shipment | Produk keluar untuk pengiriman ke customer |
| **6** | ADJ-WH | Adjustment WHx | Penyesuaian stok produk di warehouse |
| **7** | TRANSFER | Transfer | Transfer material antar plant |
| **8** | BLENDING | Blending | Proses blending / pencampuran material |
| **9** | ADJ-WIP | Adjustment WIP | Penyesuaian stok material di area WIP |

---

## 1. RM Entry (Raw Material Entry)

**Prefix: `1`** · **Kode: RM-STORE**

**Format:** `1 YYMMDD 000 PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `1` | RM Entry |
| Date | 2-7 | `YYMMDD` | Tanggal hari ini |
| Warehouse | 8-10 | `000` | Hardcoded = Storage Tank (`$movSeq = '000'`) |
| Plant | 11-12 | `PP` | 2 digit kode plant |
| Sequence | 13-14 | `SS` | Auto-increment dari `01` |

**Contoh:** `1260608000001` = RM Entry tanggal 08 Jun 2026, sequence ke-1

**File Generasi:**
- `backend\Modules\ts-raw\app\Repositories\Traits\RmEntryQueryTrait.php:185` — `getNewNumber()`
- `backend\Modules\ts-raw\app\Repositories\Traits\RmEntryQueryTrait.php:275` — `buildTraceNo()`
- `backend\Modules\ts-raw\app\Repositories\RmEntryRepository.php:18` — `$movSeq = '000'`
- `reference-dont-change\app\Models\RawMaterial.php:14` — `$movSeq = "000"`
- `reference-dont-change\app\Models\RawMaterial.php:319` — `get_rmNewEntryNumber()`

**Tabel Database:**
- `t_balance_header.trace_no` — menyimpan trace number
- `t_trace_header.to_trace_no` / `from_trace_no` — linking antar trace

---

## 2. WIP Entry (Work In Progress)

WIP memiliki **2 jenis** trace number: Feed dan Rundown.

### 2.1 WIP Feed (Input ke Proses)

**Prefix: `3`** · **Kode: MAT-FEED**

**Format:** `3 YYMMDD FFF PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `3` | WIP Feed |
| Date | 2-7 | `YYMMDD` | Tanggal hari ini |
| Feed Section | 8-10 | `FFF` | 3 digit Feed Section ID (mapped dari frontend) |
| Plant | 11-12 | `PP` | 2 digit kode plant |
| Sequence | 13-14 | `SS` | Auto-increment dari `01` |

**Section ID Mapping (Frontend → DB):**

| Frontend ID | DB Feed ID | Seksi (business-process.md) | Material |
|-------------|------------|----------------------------|----------|
| 101/102 | 001 | Degumming | RM (Source) |
| 103 | 002 | Esterifikasi | DA-OIL |
| 104 | 003 | Fractionation | CRUDE-ME |
| 105 (short chain) | 006-01 | Hidrogenasi | ME28 |
| 105 (long chain) | 006-02 | Hidrogenasi | ME80 |
| 106/114 | 008 | FA Distilasi | CFA28 |
| 110 | 004 | Glycerin Purif | TREATED GLY |
| 111/116 | 007 | Glycerin Ref | CRUDE GLY |
| 112/114 | 009 | FA Re-distilasi | Multi (ECOROL WAX, FA24, FA18lrr, FA14lrr) |
| 112/114 (variant) | 009-01 | FA Re-distilasi | FA24 |
| 112/114 (variant) | 009-02 | FA Re-distilasi | FA14lrr |
| 112/114 (variant) | 009-03 | FA Re-distilasi | FA18lrr |
| 112/114 (variant) | 009-04 | FA Re-distilasi | ECOROL WAX |
| 302 | 005 | Cracking | UME |

**File Generasi:**
- `backend\Modules\ts-wip\app\Repositories\Traits\WipEntryBatchTrait.php:162` — `generateNewFeedNumber()`
- `backend\Modules\ts-wip\app\Repositories\Traits\WipEntryQueryTrait.php:833` — `mapFrontendSectionToDbFeedId()`
- `reference-dont-change\app\Models\Wip.php:832` — `get_feedNewBatchNumber()`

### 2.2 WIP Rundown (Output dari Proses)

**Prefix: `2`** · **Kode: RM-RUNDOWN**

**Format:** `2 YYMMDD RRR PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `2` | WIP Rundown |
| Date | 2-7 | `YYMMDD` | Tanggal hari ini |
| Rundown Section | 8-10 | `RRR` | 3 digit Rundown Section ID (mapped dari frontend) |
| Plant | 11-12 | `PP` | 2 digit kode plant |
| Sequence | 13-14 | `SS` | Auto-increment dari `01` |

**Rundown Section ID Mapping (Frontend → DB):**

| Frontend ID | Produk | DB Rundown ID | business-process.md FeedId | Seksi |
|-------------|--------|---------------|---------------------------|-------|
| 102 | daoil | 011 | 11 (DA-OIL) | 101/102 |
| 102 | pkfad | 021 | 21 (PKFAD) | 101/102 |
| 103 | crudeme | 012 | 12 (CRUDE-ME) | 103 |
| 103 | treatedgly | 022 | 22 (TREATED GLY) | 103 |
| 104 | ume | 033 | 33 (UME) | 104 |
| 104 | bdme | 023 | 23 (BDME) | 104 |
| 104 | me28 | 043 | 43 (ME28) | 104 |
| 104 | econoate665 | 053 | 53 (ECONOATE 6/65) | 104 |
| 104 | me80 | 063 | 63 (ME80) | 104 |
| 105 | cfa80 | 026 | 26 (CFA80) | 105 |
| 106 | fa1299 | 078 | 78 (FA12/99) | 106/114 |
| 106 | fa1499 | 088 | 88 (FA14/99) | 106/114 |
| 110 | crudegly | 014 | 14 (CRUDE GLY) | 110 |
| 111 | glycerine | 017 | 17 (GLY) | 111/116 |
| 112 | cfa28 | 069 | 69 (CFA28) | 112/114 |
| 112 | fa12 | 039 | 39 (FA24) | 112/114 |
| 112 | fa14lrr | 079 | 79 (FA1rlrr) | 112/114 |
| 112 | fa14 | 059 | 59 (FA14/99) | 112/114 |
| 112 | fa18 | 029 | 29 (LEFA1) | 112/114 |
| 112 | fa18lrr | 049 | 49 (FA18lrr) | 112/114 |
| 112 | ecowax | 019 | 19 (ECOROL WAX) | 112/114 |
| 114 | cfa28 | 016 | 16 (CFA28) | 105 |
| 114 | ecowax | 018 | 18 (ECOROL WAX) | 106/114 |
| 114 | lefa | 028 | 28 (LEFA1) | 106/114 |
| 114 | fa24 | 038 | 38 (FA24) | 106/114 |
| 114 | fa16 | 048 | 48 (FA16/99) | 106/114 |
| 114 | fa18lrr | 058 | 58 (FA18lrr) | 106/114 |
| 114 | fa26 | 068 | 68 (FA26) | 106/114 |
| 302 | wme | 015 | 15 (WME) | 302 |
| 302 | me28 | 025 | 25 (ME28) | 302 |

**File Generasi:**
- `backend\Modules\ts-wip\app\Repositories\Traits\WipEntryBatchTrait.php:189` — `generateNewRundownNumber()`
- `backend\Modules\ts-wip\app\Repositories\Traits\WipEntryQueryTrait.php:847` — `mapFrontendSectionToDbRundownId()`
- `reference-dont-change\app\Models\Wip.php:850` — `get_rundownNewBatchNumber()`

---

## 3. Transfer

**Prefix: `7`** · **Kode: TRANSFER**

**Format:** `7 YYMMDD RRR PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `7` | Transfer |
| Date | 2-7 | `YYMMDD` | Tanggal hari ini |
| Material Rundown | 8-10 | `RRR` | 3 digit dari `m_material.id_rundown` |
| Plant | 11-12 | `PP` | 2 digit (kode plant tujuan) |
| Sequence | 13-14 | `SS` | Auto-increment dari `01` |

**Plant Code Mapping:**

| Kode (PP) | Plant Code | Plant |
|-----------|------------|-------|
| 01 | 1001 | EOMB |
| 02 | 1002 | EOB1 |
| 03 | 1003 | EOB2 |
| 05 | 1005 | EOB5 |
| 07 | 1007 | EOB3 |

**File Generasi:**
- `backend\Modules\ts-transfer\app\Repositories\TransferRepository.php:58` — `generateTransferEntryNo()`
- `backend\Modules\ts-raw\app\Repositories\Traits\RmEntryTransferTrait.php:227` — `generateTransferNumber()`
- `backend\Modules\ts-raw\app\Repositories\Traits\RmEntryTransferTrait.php:457` — `generateTransferEntryNo()`
- `reference-dont-change\app\Models\Transfer.php:27` — `get_newTransferEntryNo()`

**Tabel Database:**
- `t_balance_header.trace_no` — menyimpan trace transfer
- `t_trace_header.from_trace_no` / `to_trace_no` — linking source & destination

---

## 4. Blending

**Prefix: `8`** · **Kode: BLENDING**

**Format:** `8 YYMMDD RRR PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `8` | Blending |
| Date | 2-7 | `YYMMDD` | Tanggal hari ini |
| Material Rundown | 8-10 | `RRR` | 3 digit dari `m_material.id_rundown` |
| Plant | 11-12 | `PP` | 2 digit kode plant |
| Sequence | 13-14 | `SS` | Auto-increment dari `01` |

**Perilaku Khusus:**
Blending menggunakan **2 trace number** secara bersamaan — feed input dan rundown output:

- **Feed trace:** `3YYMMDDFFFPPSS` (prefix `3` = MAT-FEED, material masuk ke blending)
- **Rundown trace:** `8YYMMDDRRRPPSS` (prefix `8` = BLENDING, produk blending keluar)

Logika di `BlendingService.php:151`:
```php
$feed_entry_no = substr_replace($entryNo, '0', 8, 1);
// Mengambil trace blending (prefix 8), mengganti digit ke-9 jadi '0'
// untuk membuat companion feed trace
```

**Konsumsi Balances:**
Blending mengonsumsi balances dengan prefix: `1, 2, 7, 8, 9`

**File Generasi:**
- `backend\Modules\ts-blending\app\Repositories\BlendingRepository.php:58` — `generateBlendingEntryNo()`
- `backend\Modules\ts-blending\app\Services\BlendingService.php:123` — `executeBlending()`
- `reference-dont-change\app\Models\Blending.php:26` — `get_newBlendingEntryNo()`

---

## 5. Packaging

**Prefix: `4`** · **Kode: WIP-OUT**

**Format:** `4 YYMMDD WHH PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `4` | Packaging |
| Date | 2-7 | `YYMMDD` | Tanggal hari ini |
| Warehouse ID | 8-10 | `WHH` | 3 digit dari `m_warehouse.id_batch` (zero-padded) |
| Plant | 11-12 | `PP` | 2 digit kode plant |
| Sequence | 13-14 | `SS` | Auto-increment dari `01` |

**Perilaku Khusus:**
Packaging membuat **companion transfer trace** dengan warehouse section `000`:
```php
$traceNoWhx = '4YYMMDDWHHPPSS';  // trace gudang
$traceNoTrf = substr_replace($traceNoWhx, '000', 7, 3); // → '4YYMMDD000PPSS'
```
Dua trace header dibuat:
1. `$traceNoWhx` — trace gudang (prefix `4`, warehouse asli)
2. `$traceNoTrf` — companion transfer (prefix `4`, warehouse `000`)

**File Generasi:**
- `backend\Modules\ts-package\app\Repositories\EloquentPackageRepository.php:240` — `addPckEntry()`
- `backend\Modules\ts-package\app\Repositories\EloquentPackageRepository.php:264` — companion trace logic
- `reference-dont-change\app\Models\Packaging.php:760`

---

## 6. Shipment

**Prefix: `5`** · **Kode: SHIPMENT**

**Format:** `5 YYMMDD 001 PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `5` | Shipment |
| Date | 2-7 | `YYMMDD` | Tanggal hari ini |
| Warehouse | 8-10 | `001` | Hardcoded (`$shID = '001'`) |
| Plant | 11-12 | `PP` | 2 digit kode plant |
| Sequence | 13-14 | `SS` | Auto-increment dari `01` |

**Tampilan:** `from_trace_no >>> trace_no` (contoh: `4YYMMDD0010201 >>> 5YYMMDD0010201`)

**File Generasi:**
- `backend\Modules\ts-shipment\app\Repositories\EloquentShipmentRepository.php:295` — `addShipmentEntry()`
- `reference-dont-change\app\Models\Shipment.php:381` — `$shID = '001'`
- `reference-dont-change\app\Models\Shipment.php:384`

**Tabel Database:**
- `t_shipment_header.trace_no` — menyimpan trace shipment
- `t_shipment_header.from_trace_no` — trace gudang asal
- `t_trace_header.to_trace_no` — linked trace header

---

## 7. Adjustment WHx (Warehouse Adjustment)

**Prefix: `6`** · **Kode: ADJ-WH**

**Format:** `6 YYMMDD xxxx` → **10 digit** (seed) / incremental

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `6` | Adjustment WHx |
| Date | 2-7 | `YYMMDD` | Tanggal hari ini |
| Sequence | 8-11 | `xxxx` | Seed `0001`, auto-increment |

**Contoh:** `62606080001` = ADJ-WH tanggal 08 Jun 2026, sequence ke-1

**Perbedaan dengan ADJ-WIP (prefix 9):**
- Tidak embed Plant Code (PP)
- Tidak embed Material Rundown ID (RRR)
- Format lebih pendek (10 digit vs 14 digit)
- Query table: `t_warehouse_header`

**File Generasi:**
- `reference-dont-change\app\Models\Adjustment.php:42` — `get_adjNewEntryNumberWhx()`
- `backend\Modules\m-adjustment\app\Repositories\AdjustmentRepository.php:218` — `get_dtAdjustmentWhx()`

---

## 8. Adjustment WIP (Material Adjustment)

**Prefix: `9`** · **Kode: ADJ-WIP**

**Format:** `9 YYMMDD RRR PP SS` → **14 digit**

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `9` | Adjustment WIP |
| Date | 2-7 | `YYMMDD` | Tanggal hari ini |
| Material Rundown | 8-10 | `RRR` | 3 digit dari `m_material.id_rundown` |
| Plant | 11-12 | `PP` | 2 digit kode plant |
| Sequence | 13-14 | `SS` | Auto-increment dari `01` |

**Contoh:** `92606080110103` = ADJ-WIP tanggal 08 Jun 2026, material 011, plant 01, sequence 03

**File Generasi:**
- `reference-dont-change\app\Models\Adjustment.php:13` — `get_adjNewEntryNumber()`
- `reference-dont-change\app\Models\Adjustment.php:271` — `post_storeAdjustment()` (full format build)
- `backend\Modules\m-adjustment\app\Repositories\AdjustmentRepository.php:160` — `generateNewEntryNumber()`

---

## Chain of Custody (Alur Trace Number)

```
                    ┌──────────────────────────────────────────────────────────────────┐
                    │                                                                  │
RM Entry (1) ──► Transfer (7) ──► WIP Feed (3) ──► WIP Rundown (2) ──► Packaging (4) ──► Shipment (5)
 Storage          Antar-plant        Feed ke          Output produk      Produk ke         Pengiriman
                                                    proses             gudang (WH)       customer
                                            │               │
                                            │               ▼
                                            │          Blending (8)
                                            │               │
                                            │               ▼
                                            │          Adjustment WIP (9)
                                            │
                                            └──► Adjustment WHx (6)  (stok gudang)
```

**Mekanisme Linking:**
- `t_trace_header.from_trace_no` = trace number sumber
- `t_trace_header.to_trace_no` = trace number tujuan
- Contoh: WIP Feed mengonsumsi stok RM → `from_trace_no = 1YYMMDD000PP01`, `to_trace_no = 3YYMMDDFFFPP01`

**Konsumsi Balances:**
- WIP Feed (prefix `3`) mengonsumsi balances dengan prefix: `1, 2, 7, 8, 9`
- Blending (prefix `8`) mengonsumsi balances dengan prefix: `1, 2, 7, 8, 9`

---

## Mapping Tipe ke Modul Backend

| Prefix | Kode | Modul Backend | Route Prefix |
|--------|------|--------------|--------------|
| 1 | RM-STORE | `ts-raw` | `/api/raw-material` |
| 2 | RM-RUNDOWN | `ts-raw` | `/api/raw-material/rundown` |
| 3 | MAT-FEED | `ts-raw` | `/api/raw-material/feed` |
| 4 | WIP-OUT | `ts-shipment` (internal) | `/api/wip-transfer` |
| 5 | SHIPMENT | `ts-shipment` | `/api/shipment` |
| 6 | ADJ-WH | `m-adjustment` (WHx) | `/api/adjustment/warehouse` |
| 7 | TRANSFER | `ts-transfer` | `/api/transfer` |
| 8 | BLENDING | `ts-blending` | `/api/blending` |
| 9 | ADJ-WIP | `m-adjustment` (WIP) | `/api/adjustment/wip` |

---

## Tabel Database yang Terlibat

| Tabel | Kolom Utama | Fungsi |
|-------|-------------|--------|
| `t_balance_header` | `trace_no`, `id_material`, `id_sloc`, `id_tank`, `id_plant` | Stok balance utama |
| `t_balance_detail` | `id_balance_head`, `id_supplier`, `batch_sap` | Detail per supplier |
| `t_trace_header` | `from_trace_no`, `to_trace_no`, `id_balance_head` | Linking antar trace |
| `t_trace_detail` | `id_trace_head`, `id_balance_tail`, `in_qty`, `out_qty` | Detail per trace flow |
| `t_material_document` | `id_trace_head`, `material_document`, `po_so` | Referensi dokumen material |
| `t_shipment_header` | `trace_no`, `from_trace_no`, `so_no` | Record shipment |
| `t_warehouse_header` | `trace_no`, `from_trace_no`, `batch_no` | Record packaging/gudang |
| `t_adjustment_header` | `adjust_no`, `before_adjust`, `after_adjust` | Record adjustment |
| `t_prod_log` | `id_trace_head`, `section`, `batch_no`, `in_qty`, `out_qty` | Log produksi WIP |
| `m_material` | `id_material`, `id_feed`, `id_rundown` | Master material |
| `m_warehouse` | `id_warehouse`, `id_batch` | Master gudang |

---

## Validasi Regex (dari business-process.md)

```javascript
// Regex validasi 14 digit dokumen EODS
const docNumberRegex = /^[1-9]\d{6}(0[0-9]{2}|[1-9][0-9]{2})(01|02|03|05|07)\d{2}$/;

// Breakdown parser
function parseDocNumber(docNo) {
  return {
    typeOfMovement : parseInt(docNo.slice(0, 1)),   // digit 1
    date           : docNo.slice(1, 7),              // digit 2-7 (yymmdd)
    rundownOrWh    : docNo.slice(7, 10),             // digit 8-10
    plantCode      : docNo.slice(10, 12),            // digit 11-12 (last 2 of plant)
    sequenceNo     : docNo.slice(12, 14),            // digit 13-14
  }
}
```

---

*Cross-referenced dengan: `reference-dont-change/` codebase + `.claude/business-process.md` (2026-06-04)*
