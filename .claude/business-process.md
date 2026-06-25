# EODS Domain Reference
### PT Ecogreen Oleochemicals — Business Process, Trace Numbers & Data Dictionary

> Dokumen referensi domain bisnis untuk sistem EODS.
> Gabungan dari: `docs/TRACE_NUMBER_SUMMARY.md` + `.claude/business-process.md` (merged 2026-06-11)
> Dibaca on-demand saat coding fitur yang berhubungan dengan domain ini.
> Last updated: 2026-06-11

---

## Daftar Isi

1. [Format Nomor Dokumen](#1-format-nomor-dokumen)
2. [Tipe Pergerakan Material](#2-tipe-pergerakan-material)
3. [Kode Plant](#3-kode-plant)
4. [Trace Number per Fitur](#4-trace-number-per-fitur)
5. [Chain of Custody](#5-chain-of-custody)
6. [Alur Proses WIP](#6-alur-proses-wip)
7. [Referensi Cepat: FeedId per Seksi](#7-referensi-cepat-feedid-per-seksi)
8. [Diagram Alur Material End-to-End](#8-diagram-alur-material-end-to-end)
9. [Tabel Database yang Terlibat](#9-tabel-database-yang-terlibat)
10. [Referensi Developer](#10-referensi-developer)

---

## 1. Format Nomor Dokumen
Format lama (FIX hanya untuk plant EOB 1 /1002)
Total: **11 digit** (semua tipe, termasuk ADJ-WH prefix 6)

```
[ D ] [ YY ] [ MM ] [ DD ] [ RRR ] [ SS ]
  1      2     3     4      5-7      8-9   ← posisi digit
  1      6 digit (yymmdd)    2 digit    2 digit
```

| Posisi | Panjang | Nama Field | Keterangan |
|--------|---------|------------|------------|
| 1 | 1 digit | **Type of Movement** | Jenis pergerakan material (1–9) |
| 2–7 | 6 digit | **Date** | Format `yymmdd` — contoh: `260604` = 4 Juni 2026 |
| 8–9 | 2 digit | **Rundown / WH Number** | Nomor material rundown atau nomor warehouse |
| 10–11 | 2 digit | **Sequence No** | Nomor urut dokumen |

### Contoh Nomor Dokumen

```
1 260604 01 03
│ ────── ─── ──
│   │     │  └─ Sequence: 03
│   │     │   
│   │     └───────── Rundown/WH: 01
│   └─────────────── Date: 4 Juni 2026
└─────────────────── Type: 1 (Raw Material storage & transfer)

→ Nomor dokumen: 12606040103

Format Baru

Total: **14 digit** (semua tipe, termasuk ADJ-WH prefix 6)

```
[ D ] [ YY ] [ MM ] [ DD ] [ RRR ] [ PP ] [ SS ]
  1      2     3     4      5-7     8-9   10-11   ← posisi digit
  1      6 digit (yymmdd)    3 digit  2 digit  2 digit
```

| Posisi | Panjang | Nama Field | Keterangan |
|--------|---------|------------|------------|
| 1 | 1 digit | **Type of Movement** | Jenis pergerakan material (1–9) |
| 2–7 | 6 digit | **Date** | Format `yymmdd` — contoh: `260604` = 4 Juni 2026 |
| 8–10 | 3 digit | **Rundown / WH Number** | Nomor material rundown atau nomor warehouse |
| 11–12 | 2 digit | **Plant Code** | Dua digit terakhir dari kode plant |
| 13–14 | 2 digit | **Sequence No** | Nomor urut dokumen |

### Contoh Nomor Dokumen

```
1 260604 001 01 03
│ ──────  ─── ── ──
│   │      │   │  └─ Sequence: 03
│   │      │   └──── Plant: 01 (EOMB, kode 1001)
│   │      └──────── Rundown/WH: 001
│   └─────────────── Date: 4 Juni 2026
└─────────────────── Type: 1 (Raw Material storage & transfer)

→ Nomor dokumen: 12606040010103
```

---

## 2. Tipe Pergerakan Material

| Digit | Kode | Nama Pergerakan | Deskripsi |
|-------|------|-----------------|-----------|
| **1** | RM-STORE | Raw Material Storage & Transfer | Penerimaan dan penyimpanan bahan baku, transfer antar storage |
| **2** | RM-RUNDOWN | Material Rundown to WIP/PRD | Material turun dari storage ke area WIP atau produksi |
| **3** | MAT-FEED | Material Feed to Process | Material masuk ke proses produksi (feeding ke seksi) |
| **4** | WIP-OUT | Product WIP to Warehouse (through PPH) | Produk WIP keluar ke warehouse melalui PPH |
| **5** | SHIPMENT | Product Out to Shipment | Produk keluar untuk pengiriman ke customer |
| **6** | ADJ-WH | Product Adjustment WHx | Penyesuaian stok produk di warehouse |
| **7** | TRANSFER | Material Transfer Inter-Plant | Transfer material antar plant |
| **8** | BLENDING | Material Blending | Proses blending/pencampuran material |
| **9** | ADJ-WIP | Material Adjustment WIP | Penyesuaian stok material di area WIP |

### Pemetaan Tipe ke Modul & Route

| Tipe | Kode | Modul Backend | Route |
|------|------|--------------|-------|
| 1 | RM-STORE | `ts-raw` | `/api/v1/transactions/rm-entries` |
| 2 | RM-RUNDOWN | `ts-wip` | `/api/v1/transactions/wip-entries` |
| 3 | MAT-FEED | `ts-wip` | `/api/v1/transactions/wip-entries` |
| 4 | WIP-OUT | `ts-package` | `/api/v1/transactions/package-entries` |
| 5 | SHIPMENT | `ts-shipment` | `/api/v1/transactions/shipment-entries` |
| 6 | ADJ-WH | `m-adjustment` | `/api/v1/master/adjustments` |
| 7 | TRANSFER | `ts-transfer` | `/api/v1/transactions/transfers` |
| 8 | BLENDING | `ts-blending` | `/api/v1/transactions/blendings` |
| 9 | ADJ-WIP | `m-adjustment` | `/api/v1/master/adjustments` |

---

## 3. Kode Plant

| Kode Plant | Singkatan | Dua Digit (PP) |
|------------|-----------|----------------|
| **1001** | EOMB | `01` |
| **1002** | EOB1 | `02` |
| **1003** | EOB2 | `03` |
| **1005** | EOB5 | `05` |
| **1007** | EOB3 | `07` |

> Field PP di nomor dokumen menggunakan **dua digit terakhir** kode plant.
> Contoh: Plant EOMB (1001) → PP = `01`

---

## 4. Trace Number per Fitur

### 4.1 RM Entry (Raw Material Entry)

**Prefix: `1`** · **Kode: RM-STORE** · **Modul: `ts-raw`**

**Format:** `1 YYMMDD 000 PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `1` | RM Entry |
| Date | 2–7 | `YYMMDD` | Tanggal entry |
| Warehouse | 8–10 | `000` | Hardcoded = Storage Tank (`$movSeq = '000'`) |
| Plant | 11–12 | `PP` | 2 digit kode plant |
| Sequence | 13–14 | `SS` | Auto-increment dari `01` |

**Contoh:** `1260608000001` = RM Entry 08 Jun 2026, plant 00, sequence 01

**File Generasi:**
- `backend/Modules/ts-raw/app/Repositories/Traits/RmEntryQueryTrait.php:185` — `getNewNumber()`
- `backend/Modules/ts-raw/app/Repositories/Traits/RmEntryQueryTrait.php:275` — `buildTraceNo()`
- `reference-dont-change/app/Models/RawMaterial.php:319` — `get_rmNewEntryNumber()`

---

### 4.2 WIP Feed (Material Feed to Process)

**Prefix: `3`** · **Kode: MAT-FEED** · **Modul: `ts-wip`**

**Format:** `3 YYMMDD FFF PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `3` | WIP Feed |
| Date | 2–7 | `YYMMDD` | Tanggal entry |
| Feed Section | 8–10 | `FFF` | 3 digit Feed Section ID (mapped dari frontend) |
| Plant | 11–12 | `PP` | 2 digit kode plant |
| Sequence | 13–14 | `SS` | Auto-increment dari `01` |

**Feed Section ID Mapping (Frontend → DB):**

| Frontend ID | DB Feed ID | Seksi | Material Input |
|-------------|------------|-------|----------------|
| 101 / 102 | 001 | Degumming | RM |
| 103 | 002 | Esterifikasi | DA-OIL |
| 104 | 003 | Fractionation | CRUDE-ME |
| 105 (Mode 1) | 006-01 dan 006-02 | Hidrogenasi | ME28 (short chain) dan ME80 (long chain) |
| 105 (Mode 2) | 006-02 | Hidrogenasi | ME80 |
| 106 / 114 (Mode 1) | 008-01 | FA Distilasi | CFA28 |
| 106 / 114 (Mode 2) | 008-02 | FA Distilasi | CFA80 |
| 110 | 004 | Glycerin Purif | TREATED GLY |
| 111 / 116 | 007 | Glycerin Ref | CRUDE GLY |
| 112 / 114 | 009 | FA Re-distilasi | ECOROL WAX / FA24 / FA18lrr / FA14lrr |
| 302 | 005 | Cracking | UME |

**File Generasi:**
- `backend/Modules/ts-wip/app/Repositories/Traits/WipEntryBatchTrait.php:162` — `generateNewFeedNumber()`
- `backend/Modules/ts-wip/app/Repositories/Traits/WipEntryQueryTrait.php:833` — `mapFrontendSectionToDbFeedId()`
- `reference-dont-change/app/Models/Wip.php:832` — `get_feedNewBatchNumber()`

---

### 4.3 WIP Rundown (Material Rundown to WIP/PRD)

**Prefix: `2`** · **Kode: RM-RUNDOWN** · **Modul: `ts-wip`**

**Format:** `2 YYMMDD RRR PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `2` | WIP Rundown |
| Date | 2–7 | `YYMMDD` | Tanggal entry |
| Rundown Section | 8–10 | `RRR` | 3 digit Rundown Section ID |
| Plant | 11–12 | `PP` | 2 digit kode plant |
| Sequence | 13–14 | `SS` | Auto-increment dari `01` |

**Rundown Section ID Mapping (Frontend → DB):**

| Frontend ID | Produk | DB Rundown ID | FeedId | Seksi |
|-------------|--------|---------------|--------|-------|
| 102 | daoil | 011 | 11 | 101/102 |
| 102 | pkfad | 021 | 21 | 101/102 |
| 103 | crudeme | 012 | 12 | 103 |
| 103 | treatedgly | 022 | 22 | 103 |

#### Mode 1

| Frontend ID | Produk | DB Rundown ID | FeedId | Seksi |
|-------------|--------|---------------|--------|-------|
| 104 | ume | 033 | 33 | 104 |
| 104 | bdme | 023 | 23 | 104 |
| 104 | me28 | 043 | 43 | 104 |
| 104 | econoate665 | 053 | 53 | 104 |
| 104 | me80 | 063 | 63 | 104 |
| 104 | me60 | 013 | 13 | 104 |
| 105 | cfa80 | 026 | 26 | 105 |
| 105 | cfa28 | 016 | 16 | 105 |
| 106 | fa1299 | 078 | 78 | 106/114 |
| 106 | fa1499 | 088 | 88 | 106/114 |
| 110 | crudegly | 014 | 14 | 110 |
| 111 | glycerine | 017 | 17 | 111/116 |
| 112 | cfa28 | 069 | 69 | 112/114 |
| 112 | fa12 | 039 | 39 | 112/114 |
| 112 | fa14lrr | 079 | 79 | 112/114 |
| 112 | fa14 | 059 | 59 | 112/114 |
| 112 | fa18 | 029 | 29 | 112/114 |
| 112 | fa18lrr | 049 | 49 | 112/114 |
| 112 | ecowax | 019 | 19 | 112/114 |
| 114 | ecowax | 018 | 18 | 106/114 |
| 114 | lefa | 028 | 28 | 106/114 |
| 114 | fa24 | 038 | 38 | 106/114 |
| 114 | fa16 | 048 | 48 | 106/114 |
| 114 | fa18lrr | 058 | 58 | 106/114 |
| 114 | fa26 | 068 | 68 | 106/114 |
| 302 | wme | 015 | 15 | 302 |
| 302 | me28 | 025 | 25 | 302 |

#### Mode 2

| Frontend ID | Produk | DB Rundown ID | FeedId | Seksi |
|-------------|--------|---------------|--------|-------|
| 104 | ume | 033 | 33 | 104 |
| 104 | bdme | 023 | 23 | 104 |
| 104 | me28 | 043 | 43 | 104 |
| 104 | econoate665 | 053 | 53 | 104 |
| 104 | me80 | 063 | 63 | 104 |
| 105 | cfa80 | 026 | 26 | 105 |
| 106 | cfa28 | 098 | 98 | 106 |
| 106 | lefa | 028 | 28 | 106 |
| 106 | fa8 | 108 | 108 | 106 |
| 106 | fa10 | 118 | 118 | 106 |

**File Generasi:**
- `backend/Modules/ts-wip/app/Repositories/Traits/WipEntryBatchTrait.php:189` — `generateNewRundownNumber()`
- `backend/Modules/ts-wip/app/Repositories/Traits/WipEntryQueryTrait.php:847` — `mapFrontendSectionToDbRundownId()`
- `reference-dont-change/app/Models/Wip.php:850` — `get_rundownNewBatchNumber()`

---

### 4.4 Transfer (Material Transfer Inter-Plant)

**Prefix: `7`** · **Kode: TRANSFER** · **Modul: `ts-transfer`**

**Format:** `7 YYMMDD RRR PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `7` | Transfer |
| Date | 2–7 | `YYMMDD` | Tanggal entry |
| Material Rundown | 8–10 | `RRR` | 3 digit dari `m_material.id_rundown` |
| Plant | 11–12 | `PP` | 2 digit kode plant tujuan |
| Sequence | 13–14 | `SS` | Auto-increment dari `01` |

> ⚠️ **Catatan:** `generateTransferNumber()` di `RmEntryTransferTrait` saat ini menggunakan `$movSeq = '000'` dan `PP = '00'` (hardcode sementara). Perlu diupdate untuk mengambil `m_material.id_rundown` dan plant code aktual.

**File Generasi:**
- `backend/Modules/ts-transfer/app/Repositories/TransferRepository.php:58` — `generateTransferEntryNo()`
- `backend/Modules/ts-raw/app/Repositories/Traits/RmEntryTransferTrait.php:227` — `generateTransferNumber()` ⚠️ hardcode sementara
- `reference-dont-change/app/Models/Transfer.php:27` — `get_newTransferEntryNo()`

---

### 4.5 Blending

**Prefix: `8`** · **Kode: BLENDING** · **Modul: `ts-blending`**

**Format:** `8 YYMMDD RRR PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `8` | Blending |
| Date | 2–7 | `YYMMDD` | Tanggal entry |
| Material Rundown | 8–10 | `RRR` | 3 digit dari `m_material.id_rundown` material output |
| Plant | 11–12 | `PP` | 2 digit kode plant |
| Sequence | 13–14 | `SS` | Auto-increment dari `01` |

**Perilaku Khusus — Companion Feed Trace:**

Blending membuat **2 trace number sekaligus**, keduanya prefix `8`:

- **Rundown trace:** `8 YYMMDD RRR PP SS` — trace produk blending keluar (RRR = id_rundown material output)
- **Feed trace:** `8 YYMMDD RR0 PP SS` — companion konsumsi material input; digit tengah RRR diganti `0`

```php
// BlendingService.php:151
$feed_entry_no = substr_replace($entryNo, '0', 8, 1);
// $entryNo       = "8260608016020"  → digit index 8 (0-based) = '1'
// $feed_entry_no = "8260608006020"  → digit ke-9 diganti '0'
```

**Balances yang dikonsumsi:** prefix `1, 2, 7, 8, 9`

**File Generasi:**
- `backend/Modules/ts-blending/app/Repositories/BlendingRepository.php:58` — `generateBlendingEntryNo()`
- `backend/Modules/ts-blending/app/Services/BlendingService.php:123` — `executeBlending()`
- `reference-dont-change/app/Models/Blending.php:26` — `get_newBlendingEntryNo()`

---

### 4.6 Packaging (WIP to Warehouse)

**Prefix: `4`** · **Kode: WIP-OUT** · **Modul: `ts-package`**

**Format:** `4 YYMMDD WHH PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `4` | Packaging |
| Date | 2–7 | `YYMMDD` | Tanggal entry |
| Warehouse ID | 8–10 | `WHH` | 3 digit dari `m_warehouse.id_batch` (zero-padded) |
| Plant | 11–12 | `PP` | 2 digit kode plant |
| Sequence | 13–14 | `SS` | Auto-increment dari `01` |

**Perilaku Khusus — Companion Transfer Trace:**

Packaging membuat **2 trace header** sekaligus:

```php
$traceNoWhx = '4YYMMDDWHHPPSS';                        // trace gudang
$traceNoTrf = substr_replace($traceNoWhx, '000', 7, 3); // → '4YYMMDD000PPSS'
```

1. `$traceNoWhx` — trace gudang (WHH = warehouse asli)
2. `$traceNoTrf` — companion transfer (WHH = `000`)

**File Generasi:**
- `backend/Modules/ts-package/app/Repositories/EloquentPackageRepository.php:240` — `addPckEntry()`
- `backend/Modules/ts-package/app/Repositories/EloquentPackageRepository.php:264` — companion trace logic
- `reference-dont-change/app/Models/Packaging.php:760`

---

### 4.7 Shipment

**Prefix: `5`** · **Kode: SHIPMENT** · **Modul: `ts-shipment`**

**Format:** `5 YYMMDD 001 PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `5` | Shipment |
| Date | 2–7 | `YYMMDD` | Tanggal entry |
| Warehouse | 8–10 | `001` | Hardcoded |
| Plant | 11–12 | `PP` | 2 digit kode plant |
| Sequence | 13–14 | `SS` | Auto-increment dari `01` |

**Tampilan di UI:** `from_trace_no >>> trace_no` (contoh: `4260608001 0201 >>> 5260608001 0201`)

**File Generasi:**
- `backend/Modules/ts-shipment/app/Repositories/EloquentShipmentRepository.php:295` — `addShipmentEntry()`
- `reference-dont-change/app/Models/Shipment.php:381` — `$shID = '001'`

---

### 4.8 Adjustment WHx (Warehouse Adjustment)

**Prefix: `6`** · **Kode: ADJ-WH** · **Modul: `m-adjustment`**

**Format:** `6 YYMMDD WHH PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `6` | Adjustment WHx |
| Date | 2–7 | `YYMMDD` | Tanggal entry |
| Warehouse ID | 8–10 | `WHH` | 3 digit dari `m_warehouse.id_batch` (zero-padded) |
| Plant | 11–12 | `PP` | 2 digit kode plant |
| Sequence | 13–14 | `SS` | Auto-increment dari `01` |

**Perbedaan dengan ADJ-WIP (prefix 9):**
- ADJ-WH → `t_warehouse_header` (stok gudang/FG)
- ADJ-WIP → `t_adjustment_header` (stok WIP/material proses)

**File Generasi:**
- `backend/Modules/m-adjustment/app/Repositories/AdjustmentRepository.php:2118` — `storeAdjustmentWhx()` ⚠️ format lama (11 digit)
- `reference-dont-change/app/Models/Adjustment.php:42` — `get_adjNewEntryNumberWhx()` ⚠️ referensi saja, jangan ubah

---

### 4.9 Adjustment WIP (Material Adjustment)

**Prefix: `9`** · **Kode: ADJ-WIP** · **Modul: `m-adjustment`**

**Format:** `9 YYMMDD RRR PP SS` → 14 digit

| Komponen | Posisi | Nilai | Keterangan |
|----------|--------|-------|------------|
| Prefix | 1 | `9` | Adjustment WIP |
| Date | 2–7 | `YYMMDD` | Tanggal entry |
| Material Rundown | 8–10 | `RRR` | 3 digit dari `m_material.id_rundown` |
| Plant | 11–12 | `PP` | 2 digit kode plant |
| Sequence | 13–14 | `SS` | Auto-increment dari `01` |

**Contoh:** `92606080110103` = ADJ-WIP 08 Jun 2026, material 011, plant 01, sequence 03

**File Generasi:**
- `backend/Modules/m-adjustment/app/Repositories/AdjustmentRepository.php:160` — `generateNewEntryNumber()`
- `reference-dont-change/app/Models/Adjustment.php:13` — `get_adjNewEntryNumber()`
- `reference-dont-change/app/Models/Adjustment.php:271` — `post_storeAdjustment()`

---

## 5. Chain of Custody

```
RM Entry (1)
    │
    ├──► Transfer (7) ──► WIP Feed (3) ──► WIP Rundown (2) ──► Packaging (4) ──► Shipment (5)
    │     Antar-plant      Feed ke           Output produk       Produk ke         Ke customer
    │                      proses            ke storage          gudang (WH)
    │                          │
    │                          ▼
    │                      Blending (8)
    │                          │
    │                          ▼
    │                      Adjustment WIP (9)
    │
    └──► Adjustment WHx (6)  (stok gudang)
```

**Balances yang dikonsumsi per operasi:**

| Operasi | Mengonsumsi prefix |
|---------|--------------------|
| WIP Feed (3) | `1, 2, 7, 8, 9` |
| Blending (8) | `1, 2, 7, 8, 9` |
| Transfer (7) | `1, 2, 7, 8, 9` |

**Mekanisme Linking:**
- `t_trace_header.from_trace_no` = trace sumber
- `t_trace_header.to_trace_no` = trace tujuan
- Contoh: WIP Feed mengonsumsi RM → `from_trace_no = 1YYMMDD000PP01`, `to_trace_no = 3YYMMDDFFFPP01`

---

## 6. Alur Proses WIP

Setiap seksi memiliki **Source** (input), **Product** (output), **FeedId**, dan **Tank** referensi.

---

### Seksi 101 / 102 — Degumming / Neutralisasi

**Proses:** Pemisahan awal dari Raw Material

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Source | RM | 01 | `101 FT0113` |
| Product | DA-OIL | 11 | `101 FT0109` |
| Product | PKFAD | 21 | `101 FT0113` |

```
RM (FeedId 01)
    ├── DA-OIL  (FeedId 11) → masuk ke Seksi 103
    └── PKFAD   (FeedId 21) → produk akhir / warehouse
```

---

### Seksi 103 — Esterifikasi

**Proses:** Esterifikasi / transesterifikasi DA-OIL

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Source | DA-OIL | 02 | `103 FT0101` |
| Product | CRUDE-ME | 12 | `103 FT0329` |
| Product | TREATED GLY | 22 | `103 FT0266` |

```
DA-OIL (FeedId 02)
    ├── CRUDE-ME    (FeedId 12) → masuk ke Seksi 104
    └── TREATED GLY (FeedId 22) → masuk ke Seksi 110
```

---

### Seksi 110 — Glycerin Purification

**Proses:** Pemurnian glycerin (treated → crude)

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Source | TREATED GLY | 04 | `110 FT0107` |
| Product | CRUDE GLY | 14 | `110 FT0108` |

```
TREATED GLY (FeedId 04)
    └── CRUDE GLY (FeedId 14) → masuk ke Seksi 111/116
```

---

### Seksi 111 / 116 — Glycerin Refinery

**Proses:** Refinery glycerin

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Source | CRUDE GLY | 07 | `111 FT0118` + `116 FC01` |
| Product | GLY | 17 | `111 FT0314` |

```
CRUDE GLY (FeedId 07)
    └── GLY (FeedId 17) → produk akhir / warehouse
```

---

### Seksi 104 — Fractionation

**Proses:** Distilasi CRUDE-ME — menghasilkan berbagai grade ME. Terbagi menjadi 2 mode operasi.

#### Mode 1

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Source | CRUDE-ME | 03 | `104 FT0118` |
| Product | ME-60 | 13 | `104 FT0157` |
| Product | BDME | 23 | `104 FT0215` |
| Product | UME | 33 | `104 FT0110` |
| Product | ME28 | 43 | `104 FT0332` |
| Product | ECONOATE 6/65 | 53 | `104 FT0170` |
| Product | ME80 | 63 | `104 FT0157` |

```
CRUDE-ME (FeedId 03)
    ├── ME-60         (FeedId 13) → produk akhir
    ├── BDME          (FeedId 23) → produk akhir
    ├── UME           (FeedId 33) → masuk ke Seksi 302
    ├── ME28          (FeedId 43) → masuk ke Seksi 105 (short chain)
    ├── ECONOATE 6/65 (FeedId 53) → produk akhir
    └── ME80          (FeedId 63) → masuk ke Seksi 105 (long chain)
```

#### Mode 2

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Source | CRUDE-ME | 03 | `104 FT0118` |
| Product | BDME | 23 | `104 FT0215` |
| Product | UME | 33 | `104 FT0110` |
| Product | ME28 | 43 | `104 FT0332` |
| Product | ECONOATE 6/65 | 53 | `104 FT0170` |
| Product | ME80 | 63 | `104 FT0157` |

```
CRUDE-ME (FeedId 03)
    ├── BDME          (FeedId 23) → produk akhir
    ├── UME           (FeedId 33) → masuk ke Seksi 302
    ├── ME28          (FeedId 43) → masuk ke Seksi 105 (short chain)
    ├── ECONOATE 6/65 (FeedId 53) → produk akhir
    └── ME80          (FeedId 63) → masuk ke Seksi 105 (long chain)
```

> **Catatan Tank:** ME-60 (FeedId 13) dan ME80 (FeedId 63) menggunakan tank yang sama `FT0157`. Bedakan via FeedId.

---

### Seksi 105 — Hidrogenasi

**Proses:** Dua mode operasi.

#### Mode 1 (Short Chain & Long Chain)

| Mode | Source | Feed DB ID | Tank Source |
|------|--------|------------|-------------|
| Short Chain | ME28 | 006-01 | `105 FQ104` |
| Long Chain | ME80 | 006-02 | `105 FQ104` |

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Product (short chain) | CFA28 | 16 | `3105FQ808` |
| Product (long chain) | CFA80 | 26 | `302 105FQ808` |

```
ME28 (006-01, short chain) → CFA28 (FeedId 16) → masuk ke Seksi 106/114
ME80 (006-02, long chain)  → CFA80 (FeedId 26) → produk akhir
```

#### Mode 2 (Long Chain Only)

| Mode | Source | Feed DB ID | Tank Source |
|------|--------|------------|-------------|
| Long Chain | ME80 | 006-02 | `105 FQ104` |

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Product (long chain) | CFA80 | 26 | `302 105FQ808` |

```
ME80 (006-02, long chain) → CFA80 (FeedId 26) → masuk ke seksi 106
```

---

### Seksi 302 — Cracking

**Proses:** Pemecahan UME

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Source | UME | 05 | `321 FT0102` |
| Product | ME28 | 25 | `302V04` |
| Product | WME | 15 | `302 FT101` |

```
UME (FeedId 05)
    ├── ME28 (FeedId 25) → masuk ke Seksi 106/114 atau Seksi 105
    └── WME  (FeedId 15) → produk akhir
```

---

### Seksi 106 / 114 — FA Distilasi

**Proses:** Distilasi fatty alcohol — berbagai grade FA

#### Mode 1

**Dua sumber input:**

| Source | Material | FeedId | Tank | Asal |
|--------|----------|--------|------|------|
| Source A | CFA28 | 08 | `106F0115` | Dari Seksi 105 |
| Source B | CFA28 | 08 | `106F0115` | Dari Seksi 112/114 (recycle) |

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Product | ECOROL WAX | 18 | `106 F0245/167` |
| Product | LEFA1 | 28 | `106 F0167` |
| Product | FA24 | 38 | `106 F0134` |
| Product | FA16/99 | 48 | `106 F0231` |
| Product | FA18lrr | 58 | `106 F0112` |
| Product | FA26 | 68 | `106 F0134` |
| Product | FA12/99 | 78 | `106 F0134` |
| Product | FA14/99 | 88 | `106 F0231` |

> **Tank Shared di Seksi 106:**
> - FA24 (38), FA26 (68), FA12/99 (78) → `F0134` — bedakan via FeedId
> - FA16/99 (48) dan FA14/99 (88) → `F0231` — bedakan via FeedId
> - ECOROL WAX (18) → `F0245/167` (dua tank)

```
CFA28 (FeedId 08)
    ├── LEFA1    (28), FA16/99 (48), FA26 (68), FA12/99 (78), FA14/99 (88) → produk akhir
    ├── ECOROL WAX (18) ──┐
    ├── FA24       (38) ──┤→ masuk ke Seksi 112/114 atau produk akhir
    └── FA18lrr    (58) ──┘
```

#### Mode 2

**Satu sumber input:**

| Material | FeedId | Tank | Asal |
|----------|--------|------|------|
| CFA80 | 08 | `106F0115` | Dari Seksi 105 |

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Product | CFA28 | 98 | `106 F0245` |
| Product | LEFA1 | 28 | `106 F0167` |
| Product | FA8 | 108 | `106 F0134` |
| Product | FA10 | 118 | `106 F0231` |

> **Tank Shared di Seksi 106:**
> - FA24 (38), FA26 (68), FA12/99 (78) → `F0134` — bedakan via FeedId
> - FA16/99 (48) dan FA14/99 (88) → `F0231` — bedakan via FeedId
> - ECOROL WAX (18) → `F0245/167` (dua tank)

```
CFA28 (FeedId 08)
    └── LEFA1 (28), FA8 (108), FA10 (118), CFA28 (108) → produk akhir
```

---

### Seksi 112 / 114 — FA Re-distilasi

**Proses:** Pemurnian lanjutan fatty alcohol dan recycle CFA28

**Lima kemungkinan sumber input (semua FeedId 09, tank `112 F0109`):**

| Source | Material | FeedId | Asal |
|--------|----------|--------|------|
| A | ECOROL WAX | 09 | Dari Seksi 106/114 |
| B | FA24 | 09 | Dari Seksi 106/114 |
| C | FA18lrr | 09 | Dari Seksi 106/114 |
| D | FA14lrr | 09 | Internal recycle |
| E | FA18lrr | 09 | Internal recycle |

> Semua source FeedId 09, tank sama `F0109`. Pembedaan berdasarkan material yang masuk.

| Role | Material | FeedId | Tank |
|------|----------|--------|------|
| Product | ECOROL WAX | 19 | `112 F0224` |
| Product | fa18 | 29 | `112 F0235` |
| Product | fa12 | 39 | `112 F0235` |
| Product | FA18lrr | 49 | `112 F0235` |
| Product | FA14/99 | 59 | `112 F0235` |
| Product | CFA28 | 69 | `112 F0139` |
| Product | fa14lrr | 79 | `112 F0224` |

> **Tank Shared di Seksi 112:**
> - ECOROL WAX (19) dan fa14lrr (79) → `F0224`
> - fa18 (29), fa12 (39), FA18lrr (49), FA14/99 (59) → `F0235`

```
Input (berbagai FA dari Seksi 106/114 atau internal recycle)
    ├── ECOROL WAX (19), fa18 (29), fa12 (39), FA18lrr (49), FA14/99 (59), fa14lrr (79) → produk akhir
    └── CFA28 (69) → kembali ke Seksi 106/114 (recycle loop)
```

**Recycle Loop:**
```
Loop 1: Seksi 106/114 → ECOROL WAX/FA24/FA18lrr → Seksi 112/114 → CFA28 (69) → kembali ke 106/114
Loop 2: Seksi 105 → CFA28 (16) → Seksi 106/114 → Seksi 112/114 → CFA28 (69) → kembali ke 106/114
```

---

## 7. Referensi Cepat: FeedId per Seksi

| FeedId | Material | Seksi | Role | Tank |
|--------|----------|-------|------|------|
| 01 | RM | 101/102 | Source | `101 FT0113` |
| 02 | DA-OIL | 103 | Source | `103 FT0101` |
| 03 | CRUDE-ME | 104 | Source | `104 FT0118` |
| 04 | TREATED GLY | 110 | Source | `110 FT0107` |
| 05 | UME | 302 | Source | `321 FT0102` |
| 006-01 | ME28 (short chain) | 105 | Source | `105 FQ104` |
| 006-02 | ME80 (long chain) | 105 | Source | `105 FQ104` |
| 07 | CRUDE GLY | 111/116 | Source | `111 FT0118` + `116 FC01` |
| 08 | CFA28 | 106/114 | Source | `321 FT0102` |
| 09 | Multi (ECOROL WAX / FA24 / FA18lrr / FA14lrr) | 112/114 | Source | `112 F0109` |
| 11 | DA-OIL | 101/102 | Product | `101 FT0109` |
| 12 | CRUDE-ME | 103 | Product | `103 FT0329` |
| 13 | ME-60 | 104 | Product | `104 FT0157` |
| 14 | CRUDE GLY | 110 | Product | `110 FT0108` |
| 15 | WME | 302 | Product | `302 FT101` |
| 16 | CFA28 | 105 | Product | `3105FQ808` |
| 17 | GLY | 111/116 | Product | `111 FT0314` |
| 18 | ECOROL WAX | 106/114 | Product | `106 F0245/167` |
| 19 | ECOROL WAX | 112/114 | Product | `112 F0224` |
| 21 | PKFAD | 101/102 | Product | `101 FT0113` |
| 22 | TREATED GLY | 103 | Product | `103 FT0266` |
| 23 | BDME | 104 | Product | `104 FT0215` |
| 25 | ME28 | 302 | Product | `302V04` |
| 26 | CFA80 | 105 | Product | `302 105FQ808` |
| 28 | LEFA1 | 106/114 | Product | `106 F0167` |
| 29 | fa18 | 112/114 | Product | `112 F0235` |
| 33 | UME | 104 | Product | `104 FT0110` |
| 38 | FA24 | 106/114 | Product | `106 F0134` |
| 39 | fa12 | 112/114 | Product | `112 F0235` |
| 43 | ME28 | 104 | Product | `104 FT0332` |
| 48 | FA16/99 | 106/114 | Product | `106 F0231` |
| 49 | FA18lrr | 112/114 | Product | `112 F0235` |
| 53 | ECONOATE 6/65 | 104 | Product | `104 FT0170` |
| 58 | FA18lrr | 106/114 | Product | `106 F0112` |
| 59 | FA14/99 | 112/114 | Product | `112 F0235` |
| 63 | ME80 | 104 | Product | `104 FT0157` |
| 68 | FA26 | 106/114 | Product | `106 F0134` |
| 69 | CFA28 | 112/114 | Product | `112 F0139` |
| 78 | FA12/99 | 106/114 | Product | `106 F0134` |
| 79 | fa14lrr | 112/114 | Product | `112 F0224` |
| 88 | FA14/99 | 106/114 | Product | `106 F0231` |

---

## 8. Diagram Alur Material End-to-End

```
RAW MATERIAL (RM)
    │
    ▼
┌─────────────────────┐
│  Seksi 101 / 102    │  FeedId Source: 01
│  (Degumming)        │
└─────────────────────┘
    │               │
    ▼               ▼
DA-OIL (11)      PKFAD (21) ──────────────────────────► [WAREHOUSE]
    │
    ▼
┌─────────────────────┐
│  Seksi 103          │  FeedId Source: 02
│  (Esterifikasi)     │
└─────────────────────┘
    │                       │
    ▼                       ▼
CRUDE-ME (12)         TREATED GLY (22)
    │                       │
    ▼                       ▼
┌───────────┐         ┌─────────────────────┐
│ Seksi 104 │         │  Seksi 110          │  FeedId Source: 04
│ (Fraksi)  │         │  (Glycerin Purif)   │
└───────────┘         └─────────────────────┘
  Source: 03                 │
    │                  CRUDE GLY (14)
    │                        │
    │                  ┌─────────────────┐
    │                  │ Seksi 111/116   │  FeedId Source: 07
    │                  │ (Glycerin Ref.) │
    │                  └─────────────────┘
    │                        │
    │                    GLY (17) ──────────────────────► [WAREHOUSE]
    │
    ├── ME-60 (13) ─────────────────────────────────────► [WAREHOUSE]
    ├── BDME  (23) ─────────────────────────────────────► [WAREHOUSE]
    ├── ECONOATE 6/65 (53) ─────────────────────────────► [WAREHOUSE]
    │
    ├── UME (33)
    │       │
    │       ▼
    │  ┌───────────┐
    │  │ Seksi 302 │  FeedId Source: 05
    │  │ (Cracking)│
    │  └───────────┘
    │       │            │
    │   ME28 (25)     WME (15) ──────────────────────────► [WAREHOUSE]
    │       │
    │       ▼ (bergabung dengan ME28 dari Seksi 104)
    │
    ├── ME28 (43) ──┐
    │               │
    ├── ME80 (63) ──┼──► ┌──────────────────────┐
                   │     │  Seksi 105            │  FeedId: 006-01 (ME28), 006-02 (ME80)
                   │     │  (Hidrogenasi)        │
                   │     │  Short: ME28 → CFA28  │
                   │     │  Long:  ME80 → CFA80  │
                   │     └──────────────────────┘
                   │              │               │
                   │         CFA28 (16)       CFA80 (26) ──────────────► [WAREHOUSE]
                   │              │
                   │              ▼
                   │   ┌──────────────────────┐
                   │   │  Seksi 106 / 114     │  FeedId Source: 08
                   │   │  (FA Distilasi)      │◄─────────────────────────┐
                   │   └──────────────────────┘                           │
                   │        │                                              │
                   │        ├── LEFA1    (28) ───────────────────────► [WH]│
                   │        ├── FA16/99  (48) ───────────────────────► [WH]│
                   │        ├── FA26     (68) ───────────────────────► [WH]│
                   │        ├── FA12/99  (78) ───────────────────────► [WH]│
                   │        ├── FA14/99  (88) ───────────────────────► [WH]│
                   │        │                                              │
                   │        ├── ECOROL WAX (18) ──┐                       │
                   │        ├── FA24       (38) ──┤                       │
                   │        └── FA18lrr    (58) ──┘                       │
                   │                              │                       │
                   │                              ▼                       │
                   │                   ┌──────────────────────────┐       │
                   │                   │  Seksi 112 / 114         │       │
                   │                   │  (FA Re-distilasi)       │       │
                   │                   │  FeedId Source: 09       │       │
                   │                   └──────────────────────────┘       │
                   │                         │              │              │
                   │              CFA28 (69) ┘              │              │
                   │                   │           ┌────────┴──────┐       │
                   └───────────────────┘           │  Produk Akhir │       │
                                                   ├── ECOROL WAX (19) ───┤► [WH]
                                                   ├── fa18      (29) ────┤► [WH]
                                                   ├── fa12      (39) ────┤► [WH]
                                                   ├── FA18lrr   (49) ────┤► [WH]
                                                   ├── FA14/99   (59) ────┤► [WH]
                                                   └── fa14lrr   (79) ────► [WH]
```

---

## 9. Tabel Database yang Terlibat

| Tabel | Kolom Utama | Fungsi |
|-------|-------------|--------|
| `t_balance_header` | `trace_no`, `id_material`, `id_sloc`, `id_tank`, `id_plant` | Stok balance utama |
| `t_balance_detail` | `id_balance_head`, `id_supplier`, `batch_sap` | Detail per supplier |
| `t_trace_header` | `from_trace_no`, `to_trace_no`, `id_balance_head` | Linking antar trace |
| `t_trace_detail` | `id_trace_head`, `id_balance_tail`, `in_qty`, `out_qty` | Detail per trace flow |
| `t_material_document` | `id_trace_head`, `material_document`, `po_so` | Referensi dokumen material |
| `t_shipment_header` | `trace_no`, `from_trace_no`, `so_no` | Record shipment |
| `t_warehouse_header` | `trace_no`, `from_trace_no`, `batch_no` | Record packaging / adjustment WHx |
| `t_adjustment_header` | `adjust_no`, `before_adjust`, `after_adjust` | Record adjustment WIP |
| `t_prod_log` | `id_trace_head`, `section`, `batch_no`, `in_qty`, `out_qty` | Log produksi WIP |
| `m_material` | `id_material`, `id_feed`, `id_rundown` | Master material |
| `m_warehouse` | `id_warehouse`, `id_batch` | Master gudang |

---

## 10. Referensi Developer

### Validasi Nomor Dokumen

```javascript
// Regex validasi 14 digit dokumen EODS
const docNumberRegex = /^[1-9]\d{6}(0[0-9]{2}|[1-9][0-9]{2})(01|02|03|05|07)\d{2}$/

// Parser
function parseDocNumber(docNo) {
  return {
    typeOfMovement : parseInt(docNo.slice(0, 1)),   // digit 1
    date           : docNo.slice(1, 7),              // digit 2-7 (yymmdd)
    rundownOrWh    : docNo.slice(7, 10),             // digit 8-10
    plantCode      : docNo.slice(10, 12),            // digit 11-12
    sequenceNo     : docNo.slice(12, 14),            // digit 13-14
  }
}
```

### Plant Code Constants

```javascript
const VALID_PLANT_CODES = ['01', '02', '03', '05', '07']

const PLANT_MAP = {
  '01': { code: '1001', name: 'EOMB' },
  '02': { code: '1002', name: 'EOB1' },
  '03': { code: '1003', name: 'EOB2' },
  '05': { code: '1005', name: 'EOB5' },
  '07': { code: '1007', name: 'EOB3' },
}
```

### Movement Type Constants

```javascript
const MOVEMENT_TYPES = {
  1: 'Raw Material Storage & Transfer',
  2: 'Material Rundown to WIP/PRD',
  3: 'Material Feed to Process',
  4: 'Product WIP to Warehouse (PPH)',
  5: 'Product Out to Shipment',
  6: 'Product Adjustment WHx',
  7: 'Material Transfer Inter-Plant',
  8: 'Material Blending',
  9: 'Material Adjustment WIP',
}
```

### Shared Tank — Selalu Gunakan FeedId sebagai Primary Key

| Situasi | Cara Membedakan |
|---------|-----------------|
| ME-60 (13) dan ME80 (63) di `104 FT0157` | Gunakan FeedId |
| FA24 (38), FA26 (68), FA12/99 (78) di `106 F0134` | Gunakan FeedId |
| FA16/99 (48) dan FA14/99 (88) di `106 F0231` | Gunakan FeedId |
| ECOROL WAX (19) dan fa14lrr (79) di `112 F0224` | Gunakan FeedId |
| fa18 (29), fa12 (39), FA18lrr (49), FA14/99 (59) di `112 F0235` | Gunakan FeedId |
| Semua source Seksi 105 di `105 FQ104` | Bedakan via DB Feed ID: 006-01 (ME28) vs 006-02 (ME80) |
| Semua source Seksi 112/114 di `112 F0109` | Bedakan via material yang diumpankan |

### ⚠️ Item yang Perlu Diupdate di Code

| File | Issue | Action |
|------|-------|--------|
| `backend/Modules/m-adjustment/app/Repositories/AdjustmentRepository.php:2118` | ADJ-WH menghasilkan 11 digit (`6YYMMDD0001`) | Update ke 14 digit: `6YYMMDDWHHPPSS` |
| `reference-dont-change/app/Models/Adjustment.php:42` | ADJ-WH format lama | Referensi saja — jangan ubah |
| `backend/Modules/ts-raw/app/Repositories/Traits/RmEntryTransferTrait.php:227` | Transfer number hardcode `RRR=000`, `PP=00` | Update ambil `m_material.id_rundown` dan plant code aktual |

---

*Dokumen ini adalah referensi domain bisnis yang stabil.*
*Update jika ada perubahan seksi, material, FeedId, kode plant, atau format trace number.*
*Last updated: 2026-06-11*
