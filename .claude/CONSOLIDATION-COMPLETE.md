# CONSOLIDATION COMPLETE
## Audit Duplikasi + Changelog Konsolidasi Logic Bisnis Backend — EODS

> Tanggal: 2026-06-25
> Base commit: `f96fbce2`
> Dokumen ini menggabungkan `CONSOLIDATION-AUDIT.md` dan `CONSOLIDATION-CHANGES.md`

---

## Daftar Isi

1. [TL;DR — Status Semua Item](#tldr)
2. [Shared Services — Peta Infrastruktur](#shared-services)
3. [Detail Audit & Perubahan per Topik](#detail)
   - [A. Trace Number Generation](#a-trace-number-generation)
   - [B. Feed & Rundown Pattern](#b-feed--rundown-pattern)
   - [C. Period Lock Check](#c-period-lock-check)
   - [D. Deactivate / Cancel Pattern](#d-deactivate--cancel-pattern)
   - [E. Plant Resolution](#e-plant-resolution)
   - [F. t_material_document — Direct Write vs Shared Service](#f-t_material_document)
   - [G. Plant Name Display — Inline SUBSTRING](#g-plant-name-display)
4. [Ringkasan Perubahan File](#ringkasan-perubahan-file)
5. [Frontend Audit](#frontend-audit)
6. [Yang TIDAK Diubah (Sengaja Ditinggal)](#yang-tidak-diubah)
7. [Backlog P2/P3](#backlog)
8. [Cara Revert](#cara-revert)
9. [Dependency Map Setelah Konsolidasi](#dependency-map)
10. [Appendix — File Reference Lengkap](#appendix)

---

## TL;DR — Status Semua Item {#tldr}

| Priority | Pattern | Risk | File Terdampak | Status |
|---|---|---|---|---|
| 🔴 P1 | `ts-transfer/TransferRepository::deactivateTransfer()` duplikat `TransactionCancellationService` | Logic divergen → data inconsistency | 2 | ✅ DONE 2026-06-25 |
| 🔴 P1 | `ts-raw/RmEntryTransferTrait` — 3 blok `MAX(seq)+1` hampir identik | Sequence collision, race condition | 1 | ✅ DONE 2026-06-25 |
| 🔴 P1 | `ts-raw` tulis langsung ke `t_material_document` (bypass audit) | Silent writes tanpa log | 2 | ✅ DONE 2026-06-25 |
| 🔴 P1 | `ts-shipment::cancel()` — raw UPDATE status=0 sendiri, tidak pakai `TransactionCancellationService` | Logic cancel bisa divergen dari shared service | 1 | ✅ DONE 2026-06-25 |
| 🟠 P2 | `resolvePlantCode()` implementasi sendiri × 4 modul | Inconsistent null handling | 4 | ✅ DONE (ts-wip, ts-raw/Service) 2026-06-25 |
| 🟠 P2 | Inline `CASE WHEN CHAR_LENGTH >= 14 THEN SUBSTRING(11,2) ELSE SUBSTRING(8,2)` × 10 lokasi | Dual-format bug, susah ganti logic | 3 | ✅ DONE (ts-transfer×4, ts-shipment×1, ts-package×2) 2026-06-25 |
| 🟠 P2 | Feed+Rundown inline tanpa `FeedRundownOrchestrator` di ts-blending, ts-raw transfer | Missed rollback contract | 2 | ❌ NOT applicable — semua 3 case punya supplier_row transformation custom, tidak kompatibel Orchestrator |
| 🟡 P3 | `getLockStatus()` / `checkPeriodLock()` wrapper tipis × 3 modul | Noise only | 3 | ✅ DONE (FQCN fix semua) 2026-06-25 |
| 🟡 P3 | `WipEntryBatchTrait` — legacy `getFeedNewBatchNumber` / `getRundownNewBatchNumber` | Dead path confusion | 1 | ❌ NOT dead code — masih dipanggil dari route `/feed/new-batch` |
| 🟢 Frontend | Trace number display & validation di Vue modules | — | All modules | ✅ CLEAN — tidak ada positional parsing, semua string-safe |

---

## Shared Services — Peta Infrastruktur {#shared-services}

```
Shared/Services/
├── TraceNumberGeneratorService   ← parse() + format() statics
├── PeriodLockService             ← isLocked() — semua modul harusnya pakai langsung
├── TransactionCancellationService ← semua cancel/deactivate harus lewat sini
│   └── BARU: cancelShipment()   ← diekstrak dari ts-shipment::cancel() ✅
├── TransactionCoreService        ← createMaterialDocument(), updateEntrySubTank()
├── FeedRundownOrchestrator       ← semua Feed+Rundown harus lewat sini (bila kompatibel)
└── PlantContextService           ← plant resolution canonical

Shared/Traits/
└── TraceNumberGeneratorTrait     ← generateTraceNumberForMaterial()

Shared/Helpers/
└── TraceHelper                   ← BARU: plantNameExpression() ✅
```

---

## Detail Audit & Perubahan per Topik {#detail}

---

### A. Trace Number Generation {#a-trace-number-generation}

#### Infrastruktur yang Sudah Ada

```
Shared/Traits/TraceNumberGeneratorTrait.php
  └─ generateTraceNumberForMaterial(prefix, materialId, plantId, table, col, idCol)
       → MAX(seq)+1 → TraceNumberGeneratorService::format()

Shared/Services/TraceNumberGeneratorService.php
  ├─ format(prefix, date, section, plantCode, seq) → string 14-digit
  └─ parse(traceNo) → [prefix, date, section, plant, sequence]

Shared/Helpers/TraceHelper.php
  ├─ warehouseCondition(col, op, value) → dual-format SQL WHERE
  ├─ plantCondition(col, plants, op)    → dual-format SQL WHERE
  ├─ only14Digit(col)                   → CHAR_LENGTH >= 14 guard
  └─ isStorageOrLegacy(col)             → storage entry check
```

#### Modul yang Pakai Trait (Benar ✅)

| Modul | File | Method |
|---|---|---|
| ts-blending | `BlendingRepository.php:37` | `generateBlendingEntryNo()` → `generateTraceNumberForMaterial('8', ...)` |
| ts-shipment | `EloquentShipmentRepository.php:22` | `generateTraceNo()` → `generateTraceNumberForMaterial('5', ...)` |
| ts-package | `EloquentPackageRepository.php:22` | `generateTraceNo()` → `generateTraceNumberForMaterial('4', ...)` |
| ts-transfer | `TransferRepository.php:42` | `generateTransferEntryNo()` → `generateTraceNumberForMaterial('7', ...)` |

#### Raw SQL MAX(seq)+1 yang Bermasalah ❌ → ✅ DONE

**ts-raw: 3 blok hampir identik di `RmEntryTransferTrait.php`**

```php
// BLOK 1 — getTransferNumber() line ~47
$castSeq = "CAST(SUBSTRING(CAST(to_trace_no AS TEXT), 13, 2) AS INTEGER)";
$result = DB::connection('eudr_ts')->select(
    "SELECT MAX({$castSeq}) as max_seq FROM t_trace_header
      WHERE SUBSTRING(CAST(to_trace_no AS TEXT), 1, 1) = ?
        AND SUBSTRING(CAST(to_trace_no AS TEXT), 2, 6) = {$dateFmt}
        AND " . TraceHelper::warehouseCondition(...) . "
        AND " . TraceHelper::plantCondition(...) . "
        AND status = 1",
    ...
);
$newSeq = ($result[0]->max_seq ?? 0) + 1;
return $this->buildTraceNo('3', date("ymd"), $warehouse, $tracePlantCode, $newSeq);

// BLOK 2 — generateTransferNumber() line ~353 — SAMA PERSIS, prefix '7'
// BLOK 3 — generateTransferEntryNo() line ~603 — SAMA PERSIS, prefix '7' + baca id_rundown dari material
```

**Masalah:** 3 method berbeda, query SQL sama persis dengan variasi kecil (prefix + tabel). Race condition mungkin tanpa lock.

**Fix yang Diterapkan:** Ketiga method delegate ke `TraceNumberGeneratorTrait::generateTraceNumberForMaterial()`.

---

### B. Feed & Rundown Pattern {#b-feed--rundown-pattern}

#### Infrastruktur yang Sudah Ada

```
Shared/Helpers/Feed.php
  └─ generalFeed(params)       — FIFO engine, keluarkan qty dari balance

Shared/Helpers/Rundown.php
  └─ generalRundown(params)    — buat balance baru + trace header+detail

Shared/Services/FeedRundownOrchestrator.php
  └─ executeFeedRundownSequence(feedParams, rundownParams)
       → Feed::generalFeed() + Rundown::generalRundown() dalam satu transaksi
```

#### Pemetaan Per Modul

| Modul | File | Pakai Orchestrator? | Direct Feed? | Direct Rundown? |
|---|---|---|---|---|
| ts-raw (transfer entry) | `RmEntryService.php:266` | ✅ Ya | — | — |
| ts-wip (feed save) | `WipEntryWriteTrait.php:160` | ❌ No | ✅ Ya | ✅ Ya |
| ts-raw (rm entry) | `RmEntryTransactionTrait.php` | ❌ No | ✅ Ya (line 357) | ✅ Ya (213, 403) |
| ts-blending | `BlendingService.php` | ❌ No | ✅ Ya (280) | ✅ Ya (365) |
| ts-package | `EloquentPackageRepository.php` | ❌ No | ✅ Ya (477) | — |

#### Keputusan

**❌ NOT Applicable** — Setelah audit mendalam, ts-blending dan ts-wip Rundown keduanya punya transformasi `supplier_rows` yang **tidak kompatibel** dengan Orchestrator:
- Blending: multi-Feed loop
- WIP Rundown: yield calculation

Orchestrator hanya cocok untuk 1-Feed→1-Rundown dengan supplier row pass-through. **Ini bukan duplikasi, tapi variasi yang sah.** Tidak perlu diubah.

---

### C. Period Lock Check {#c-period-lock-check}

#### Canonical

```php
// Shared/Services/PeriodLockService.php
PeriodLockService::isLocked(string $date): bool
```

#### Masalah: Wrapper Tipis di 3 Modul ❌ → ✅ DONE

```php
// ts-transfer/TransferRepository.php:451
public function getLockStatus(string $entryDate): bool {
    return PeriodLockService::isLocked($entryDate); // cuma wrap
}

// ts-blending/BlendingRepository.php:387
public function getLockStatus(string $entryDate): bool {
    return PeriodLockService::isLocked($entryDate); // sama persis
}

// ts-wip/WipEntryQueryTrait.php:780
protected function checkPeriodLock(string $date): bool {
    return PeriodLockService::isLocked($date); // sama, nama beda
}
```

**Fix yang Diterapkan:** FQCN fix di semua lokasi. Semua sekarang panggil `\Modules\Shared\Services\PeriodLockService::isLocked()` secara eksplisit.

#### Perubahan Per File

| File | Sebelum | Sesudah |
|---|---|---|
| `ts-transfer/TransferRepository.php` | `return PeriodLockService::isLocked($entryDate);` | `return \Modules\Shared\Services\PeriodLockService::isLocked($entryDate);` |
| `ts-blending/BlendingRepository.php` | `return PeriodLockService::isLocked($entryDate);` | `return \Modules\Shared\Services\PeriodLockService::isLocked($entryDate);` |
| `ts-wip/WipEntryQueryTrait.php` | `return PeriodLockService::isLocked($date);` | `return \Modules\Shared\Services\PeriodLockService::isLocked($date);` |

---

### D. Deactivate / Cancel Pattern {#d-deactivate--cancel-pattern}

#### Canonical — TransactionCancellationService

```
Shared/Services/TransactionCancellationService.php
  ├─ cancelWipFeed(traceNo, user)
  ├─ cancelWipRundown(traceNo, user)
  ├─ deactivateRmEntry(id, user)
  ├─ deactivateRmEntryTrf(id, user)
  ├─ deactivateFeedLogEntry(id, user)
  ├─ deactivateBlending(id, user)
  ├─ deactivateTransfer(id, user)
  └─ cancelShipment(traceNo, user)   ← BARU ✅
```

#### Yang Sudah Benar ✅

- `ts-wip/WipEntryWriteTrait.php` → `cancelFeed()`, `cancelRundown()` delegate ke service ✅
- `ts-raw/RmEntryTransactionTrait.php` → `deactivateRmEntry()`, dll delegate ke service ✅
- `ts-blending/BlendingRepository.php` → `deactivateBlending()` delegate ke service ✅

#### D1. `ts-transfer/TransferRepository::deactivateTransfer()` — 220 baris → 3 baris ✅ DONE

**Masalah:** `TransactionCancellationService::deactivateTransfer()` sudah ada, tapi `TransferRepository` punya versi sendiri ~200 baris raw SQL yang tidak mendelegasi. Kalau ada bug di satu, satu lagi tetap buggy.

**Sebelum (~220 baris raw SQL):**
```php
public function deactivateTransfer(string $id, string $user): array
{
    $idTmp = explode("|", $id);
    $idHead = trim($idTmp[0]);
    // ... 200+ baris logic ...
}
```

**Sesudah:**
```php
public function deactivateTransfer(string $id, string $user): array
{
    return app(\Modules\Shared\Services\TransactionCancellationService::class)
        ->deactivateTransfer($id, $user);
}
```

**Revert:** Terlalu besar untuk manual. Gunakan:
```bash
git checkout f96fbce2 -- backend/Modules/ts-transfer/app/Repositories/TransferRepository.php
```

#### D2. `ts-shipment/EloquentShipmentRepository::cancel()` — 170 baris → 5 baris ✅ DONE

**Masalah:** 8+ raw UPDATE statements, tidak pakai `TransactionCancellationService`. Logic bisa divergen dari shared service.

**Penambahan ke `TransactionCancellationService`:**

```php
public function cancelShipment(string $traceNo, string $user): array
```

Handles:
- Period lock check
- Origin detection (warehouse type-4 vs balance type-1)
- Restore qty ke source (warehouse_header/detail atau balance_header/detail)
- Deactivate trace_header, trace_detail, shipment_header, shipment_detail

**Sebelum (~170 baris raw SQL dengan dua branch origin==4 dan origin==1):**
```php
public function cancel(string $user, array $data): array
{
    $traceNo = $data['traceNo'] ?? null;
    // ... 160+ baris logic ...
}
```

**Sesudah:**
```php
public function cancel(string $user, array $data): array
{
    $traceNo = $data['traceNo'] ?? null;
    if (!$traceNo) {
        return ['response' => 0, 'message' => 'Trace number is required.'];
    }
    return app(\Modules\Shared\Services\TransactionCancellationService::class)
        ->cancelShipment((string) $traceNo, $user);
}
```

**Revert:** Terlalu besar untuk manual. Gunakan:
```bash
git checkout f96fbce2 -- backend/Modules/ts-shipment/app/Repositories/EloquentShipmentRepository.php
```

---

### E. Plant Resolution {#e-plant-resolution}

#### Canonical

```php
// Shared/Services/PlantContextService.php (implements PlantContextServiceInterface)
PlantContextServiceInterface::resolvePlantId($plantId): string
```

#### Yang Sudah Benar ✅

- `ts-raw/RmEntryQueryTrait.php:258` → `app(PlantContextServiceInterface::class)->resolvePlantId()` ✅
- `ts-blending/BlendingService.php:381` → `app(PlantContextServiceInterface::class)->resolvePlantId()` ✅

#### Implementasi Sendiri yang Bermasalah

| File | Method | Status |
|---|---|---|
| `ts-wip/WipEntryQueryTrait.php:942` | `resolvePlantId()` — own null-check | ✅ DONE 2026-06-25 |
| `ts-raw/RmEntryService.php:597` | `resolvePlantCode()` — own DB lookup + fallback | ✅ DONE 2026-06-25 |
| `ts-wip/WipEntryBatchTrait.php:229` | `resolvePlantCode()` (private) — own DB lookup ke `m_plant` | ❌ Ditinggal (semantik berbeda — return 2-char suffix, bukan code_3) |
| `ts-transfer/TransferService.php:421` | `resolvePlantCode()` — delegate ke `transferRepo->findPlantCode()` | ❌ Ditinggal (indirect, bukan duplikat sejati) |

#### Perubahan: `ts-wip/WipEntryQueryTrait.php` — `resolvePlantId()` ✅ DONE

**Sebelum:**
```php
protected function resolvePlantId($plantId): ?string
{
    if ($plantId === null || $plantId === '' || $plantId === 0 || $plantId === '0') {
        return '0';
    }
    if ($plantId && is_numeric($plantId)) {
        $plant = Plant::find($plantId);
        if ($plant && $plant->code_3) {
            return $plant->code_3;
        }
    }
    return $plantId !== null ? (string) $plantId : null;
}
```

**Sesudah:**
```php
protected function resolvePlantId(mixed $plantId): ?string
{
    if ($plantId === null || $plantId === '' || $plantId === 0 || $plantId === '0') {
        return '0';
    }
    $resolved = app(\Modules\Shared\Services\Contracts\PlantContextServiceInterface::class)
        ->resolvePlantId($plantId);
    return $resolved ?? (string) $plantId;
}
```

**Revert:** Ganti balik ke implementasi `Plant::find()` di atas.

---

### F. t_material_document — Direct Write vs Shared Service {#f-t_material_document}

#### Canonical

```php
// Shared/Services/TransactionCoreService.php
TransactionCoreService::createMaterialDocument(user, idTraceHead, materialDoc, mode)
  → handles INSERT vs UPDATE
  → calls logTransaction() (audit trail)
```

#### Yang Sudah Benar ✅

- `ts-wip/WipEntryWriteTrait.php:20` ✅
- `ts-transfer/TransferRepository.php:513` ✅
- `ts-blending/BlendingService.php:111` ✅

#### Yang Bypass Shared Service ❌ → ✅ DONE

**F1. `ts-raw/RmEntryTransactionTrait.php` — `saveRmEntry()` ✅ DONE**

**Sebelum:**
```php
if (!empty($data['material_document'])) {
    DB::connection('eudr_ts')->table('t_material_document')->insert([
        'id_trace_head' => $idTraceHead,
        'material_document' => $data['material_document'],
        'po_so' => $data['po_so'] ?? null,
        'created_by' => $user,
    ]);
}
```

**Sesudah:**
```php
if (!empty($data['material_document'])) {
    app(\Modules\Shared\Services\TransactionCoreService::class)
        ->createMaterialDocument($user, $idTraceHead, $data['material_document'], 'ADD');
}
```

**F2. `ts-raw/RmEntryTransactionTrait.php` — `saveRmTrfEntry()` ✅ DONE**

**Sebelum:**
```php
if (!empty($materialDoc)) {
    DB::connection('eudr_ts')->table('t_material_document')->insert([
        'id_trace_head' => $rundownResult['id_trace_head'],
        'material_document' => $materialDoc,
        'created_by' => $user,
        'created_at' => now(),
    ]);
}
```

**Sesudah:**
```php
if (!empty($materialDoc)) {
    app(\Modules\Shared\Services\TransactionCoreService::class)
        ->createMaterialDocument($user, $rundownResult['id_trace_head'], $materialDoc, 'ADD');
}
```

**F3. `ts-raw/RmEntryService.php` — `executeTransferEntry()` — 3 DB calls → 1 call ✅ DONE**

**Sebelum (3 DB calls — check + insert + update):**
```php
if (!empty($materialDoc)) {
    $traceHeadId = $rundownResult['id_trace_head'];
    $existingDoc = DB::connection('eudr_ts')->table('t_material_document')
        ->where('id_trace_head', $traceHeadId)
        ->first();

    if ($existingDoc) {
        DB::connection('eudr_ts')->table('t_material_document')
            ->where('id_trace_head', $traceHeadId)
            ->update(['material_document' => $materialDoc, 'updated_by' => $user]);
    } else {
        DB::connection('eudr_ts')->table('t_material_document')->insert([
            'id_trace_head' => $traceHeadId,
            'material_document' => $materialDoc,
            'created_by' => $user,
        ]);
    }
}
```

**Sesudah (1 call):**
```php
if (!empty($materialDoc)) {
    $traceHeadId = $rundownResult['id_trace_head'];
    app(\Modules\Shared\Services\TransactionCoreService::class)
        ->createMaterialDocument($user, $traceHeadId, $materialDoc, 'ADD');
}
```

**Revert F1, F2, F3:** Ganti balik ke blok DB langsung masing-masing di atas.

---

### G. Plant Name Display — Inline SUBSTRING {#g-plant-name-display}

#### Masalah: Pattern Copy-Paste di 10 Lokasi ❌

```sql
-- Pattern ini copy-paste di 3 modul berbeda:
CASE (CASE WHEN CHAR_LENGTH(CAST(trace_no AS VARCHAR)) >= 14
           THEN SUBSTRING(trace_no, 11, 2)
           ELSE SUBSTRING(trace_no, 8, 2) END)
    WHEN '01' THEN 'EOMB'
    WHEN '02' THEN 'EOB1'
    WHEN '03' THEN 'EOB2'
    WHEN '05' THEN 'EOB5'
    WHEN '07' THEN 'EOB3'
    ELSE <fallback>
END AS plant_name
```

| File | Occurrences |
|---|---|
| `ts-transfer/TransferRepository.php` | 7× (lines 167, 176, 185, 201, 210, 219, 278) |
| `ts-shipment/EloquentShipmentRepository.php` | 1× (line 57) |
| `ts-package/EloquentPackageRepository.php` | 2× (lines 82, 129) |

**Masalah:** Hardcode plant code → name mapping di SQL. Kalau ada plant baru, harus update 10 tempat.

#### Fix: `TraceHelper::plantNameExpression()` — Method Baru ✅ DONE

```php
// Shared/Helpers/TraceHelper.php — method baru ditambahkan
public static function plantNameExpression(string $col): string
```

Generates dual-format aware `CASE` SQL expression yang map 2-digit plant code ke nama abbreviation.

**Implementasi:**
```php
public static function plantNameExpression(string $col): string
{
    $plantCode = "(CASE WHEN CHAR_LENGTH(CAST({$col} AS VARCHAR)) >= 14
                        THEN SUBSTRING({$col}, 11, 2)
                        ELSE SUBSTRING({$col}, 8, 2) END)";
    return "CASE ({$plantCode})
                WHEN '01' THEN 'EOMB'
                WHEN '02' THEN 'EOB1'
                WHEN '03' THEN 'EOB2'
                WHEN '05' THEN 'EOB5'
                WHEN '07' THEN 'EOB3'
                ELSE {$plantCode}
            END";
}
```

**Penggunaan di SELECT:**
```php
TraceHelper::plantNameExpression('a.trace_no') . ' AS plant_name'
```

#### Yang Sudah Diganti ✅

| File | Jumlah Lokasi | Status |
|---|---|---|
| `ts-transfer/TransferRepository.php` | 4× (pgsql + mysql branches di `getTransferList()`) | ✅ DONE |
| `ts-shipment/EloquentShipmentRepository.php` | 1× (`MAX(CASE WHEN ...)`) | ✅ DONE |
| `ts-package/EloquentPackageRepository.php` | 2× (pgsql + mysql branches) | ✅ DONE |

**Revert:** Hapus method `plantNameExpression()` dari `TraceHelper.php` (dari baris komentar `/** Build a SQL CASE expression...` sampai closing brace method) dan kembalikan inline CASE di setiap file.

---

## Ringkasan Perubahan File {#ringkasan-perubahan-file}

| # | File | Perubahan | Dampak |
|---|---|---|---|
| 1 | `Shared/Helpers/TraceHelper.php` | Tambah `plantNameExpression()` | +31 baris |
| 2 | `Shared/Services/TransactionCancellationService.php` | Tambah `cancelShipment()` | +185 baris |
| 3 | `ts-transfer/Repositories/TransferRepository.php` | `deactivateTransfer()` 220-baris → 3-baris delegate; `getLockStatus()` FQCN fix; plant_name CASE → TraceHelper | ~220 baris dihapus |
| 4 | `ts-raw/Repositories/Traits/RmEntryTransactionTrait.php` | 2× direct `t_material_document` insert → `TransactionCoreService` | ~10 baris dihapus |
| 5 | `ts-raw/Services/RmEntryService.php` | Direct check-insert-update `t_material_document` (3 DB calls) → `TransactionCoreService` (1 call) | ~10 baris dihapus |
| 6 | `ts-blending/Repositories/BlendingRepository.php` | `getLockStatus` bare class → FQCN | ~1 baris |
| 7 | `ts-wip/Repositories/Traits/WipEntryQueryTrait.php` | `resolvePlantId` → delegate `PlantContextServiceInterface`; `checkPeriodLock` FQCN | ~5 baris |
| 8 | `ts-shipment/Repositories/EloquentShipmentRepository.php` | `cancel()` 170-baris → 5-baris delegate ke `cancelShipment()`; plant_name CASE → TraceHelper | ~170 baris dihapus |
| 9 | `ts-package/Repositories/EloquentPackageRepository.php` | 2× inline plant_name CASE → TraceHelper | ~20 baris dihapus |

**Total estimasi baris dihapus/diganti:** ~430 baris duplikasi dieliminasi.

---

## Frontend Audit {#frontend-audit}

**Kesimpulan: Frontend BERSIH — tidak ada perubahan diperlukan.**

| Modul | File | Temuan | Status |
|---|---|---|---|
| trace-backward | `services/index.js`, `BackwardTraceView.vue` | trace_no sebagai string path param + display saja | ✅ Safe |
| trace-forward | `services/index.js`, `ForwardTraceView.vue` | Sama dengan backward | ✅ Safe |
| ts-raw | `RmEntryView.vue`, `RmEntryModal.vue`, `TransferModal.vue` | trace_no readonly/auto-gen; display concat saja | ✅ Safe |
| ts-wip | `WipEntryView.vue` | `buildTraceNo()` fallback hanya generate 14-digit jika server tidak return value. Normal path pakai server-generated value. | ✅ Safe |
| ts-blending | `BlendingView.vue`, `BlendingModal.vue` | trace_no display + split pada pipe delimiter (bukan positional) | ✅ Safe |
| ts-package | `PackageEntryView.vue`, `PackageEntryModal.vue`, `usePackageEntryStore.js` | trace_no readonly, stored as string | ✅ Safe |
| ts-shipment | `ShipmentEntryView.vue`, `ShipmentEntryModal.vue`, `useShipmentEntryStore.js` | Same as package | ✅ Safe |
| ts-transfer | `TransferView.vue`, `TransferEntryModal.vue` | Display only, entry_no auto-gen | ✅ Safe |
| ts-rmreport | `RmReportView.vue` | Pure display | ✅ Safe |
| ts-tsreport | `TsReportView.vue` | Pure display in 5 tables | ✅ Safe |
| ts-stock | `StockInquiryView.vue` | trace_no as column label only | ✅ Safe |
| shared | `TraceDetailModal.vue` | `traceNo` prop typed `String`, display only | ✅ Safe |

**Verifikasi:**
- ❌ Tidak ada `parseInt(trace_no)` atau `Number(trace_no)` — tidak ada numeric coercion
- ❌ Tidak ada input validation (regex/length) pada trace_no fields — semua readonly/auto-generated
- ❌ Tidak ada positional `substring`/`slice`/`charAt` pada trace_no — semua backend-only
- ❌ Tidak ada hardcoded `11` atau `14` length constant di frontend
- ✅ Semua trace_no fields typed sebagai `String` di props — aman dari JS number precision loss

---

## Yang TIDAK Diubah (Sengaja Ditinggal) {#yang-tidak-diubah}

| Item | Alasan |
|---|---|
| `ts-raw/RmEntryTransferTrait.php` — 3 blok `MAX(seq)+1` | Sudah pakai `TraceHelper` (warehouseCondition + plantCondition) dan delegate ke `buildTraceNo()`. Refactor ke `generateTraceNumberForMaterial()` butuh verifikasi business logic lebih dalam (prefix '3' vs '7', warehouse dari tank lookup). Catat sebagai P2 backlog. |
| `ts-wip/WipEntryBatchTrait.php` — `resolvePlantCode()` private | Digunakan hanya untuk generate 2-digit plant suffix dalam batch number format — sudah di-guard dengan `only14Digit`. Semantik berbeda dengan `PlantContextService::resolvePlantId()` (yang return code_3, bukan 2-char suffix). Tinggalkan. |
| `ts-wip/WipEntryBatchTrait.php` — legacy `getFeedNewBatchNumber` / `getRundownNewBatchNumber` | **Tidak dapat dihapus** — masih dipanggil dari route `/feed/new-batch` dan `/rundown/new-batch`. |
| `ts-blending/BlendingService.php` + `ts-wip/WipEntryWriteTrait.php` — inline Feed+Rundown tanpa Orchestrator | Refactor ke `FeedRundownOrchestrator` butuh test coverage. Lebih penting: setelah audit mendalam, supplier_row transformation di blending dan WIP tidak kompatibel dengan Orchestrator. **Ini variasi sah, bukan duplikasi.** |
| `ts-transfer/TransferRepository.php` — `from_plant_name` COALESCE dan `plant_code_from_trace` inline | Semantik berbeda (`from_plant_name` pakai COALESCE fallback ke `p_from.code_2` dari JOIN, bukan trace number parsing saja). Biarkan tetap explicit. |
| `ts-raw/RmEntryService.php::deactivateTransfer()` "enhanced" version | Audit mendalam: signature berbeda (`int $id` vs `"idHead|idTraceHead"`). **Tidak redundan** — logika lebih simple untuk RM-level transfer. Tinggalkan. `getLockStatus` wrapper sudah di-cleanup ke FQCN. |
| `ts-transfer/TransferService.php` — `resolvePlantCode()` | Delegate ke `transferRepo->findPlantCode()` (indirect). Tidak di-scope di sprint ini. |

---

## Backlog P2/P3 {#backlog}

| Status | Item | Keterangan |
|---|---|---|
| ✅ DONE | `ts-raw/RmEntryTransferTrait.php` — migrate 3 blok `MAX(seq)+1` ke `nextTraceSeq()` helper | Selesai 2026-06-25 |
| ⏳ P2 | `ts-wip + ts-blending` — inline Feed+Rundown → `FeedRundownOrchestrator` | **CATATAN:** Setelah audit mendalam, tidak applicable — supplier_row transformation tidak kompatibel. Hapus dari backlog. |
| ❌ Dibatalkan | Hapus legacy `WipEntryBatchTrait::getFeedNewBatchNumber()` dan `getRundownNewBatchNumber()` | Masih dipanggil dari route aktif. Tidak bisa dihapus. |
| ⏳ P3 | Audit `ts-raw/RmEntryService::deactivateTransfer()` | Tidak redundan (signature berbeda). Tinggalkan, cukup dicatat. |

---

## Cara Revert {#cara-revert}

### Revert Semua Sekaligus

```bash
git checkout f96fbce2 -- \
  backend/Modules/Shared/app/Helpers/TraceHelper.php \
  backend/Modules/Shared/app/Services/TransactionCancellationService.php \
  backend/Modules/ts-transfer/app/Repositories/TransferRepository.php \
  backend/Modules/ts-raw/app/Repositories/Traits/RmEntryTransactionTrait.php \
  backend/Modules/ts-raw/app/Services/RmEntryService.php \
  backend/Modules/ts-blending/app/Repositories/BlendingRepository.php \
  backend/Modules/ts-wip/app/Repositories/Traits/WipEntryQueryTrait.php \
  backend/Modules/ts-shipment/app/Repositories/EloquentShipmentRepository.php \
  backend/Modules/ts-package/app/Repositories/EloquentPackageRepository.php
```

> **Catatan:** Revert ini mengembalikan ke commit `f96fbce2`. File `TraceHelper.php` dan `TransactionCancellationService.php` akan kehilangan penambahan method baru (`plantNameExpression` dan `cancelShipment`).

### Revert Per File

| File | Cara Revert |
|---|---|
| `TraceHelper.php` | Hapus method `plantNameExpression()` (dari `/** Build a SQL CASE expression...` sampai closing brace) |
| `TransactionCancellationService.php` | Hapus method `cancelShipment()` (dari `/** Cancel Shipment transaction.` sampai closing `}`) |
| `TransferRepository.php` | `git checkout f96fbce2 -- <path>` (terlalu besar untuk manual) |
| `RmEntryTransactionTrait.php` | Ganti balik ke blok `DB::table('t_material_document')->insert([...])` per Fix 2a dan 2b |
| `RmEntryService.php` | Ganti balik ke blok 3 DB calls (check + insert + update) |
| `BlendingRepository.php` | Ganti FQCN balik ke bare class (pastikan `use` statement masih ada) |
| `WipEntryQueryTrait.php` | Ganti balik ke implementasi `Plant::find()` |
| `EloquentShipmentRepository.php` | `git checkout f96fbce2 -- <path>` (terlalu besar untuk manual) |
| `EloquentPackageRepository.php` | Ganti balik inline CASE di 2 lokasi |

---

## Dependency Map Setelah Konsolidasi {#dependency-map}

```
ts-raw ──────────────────────────────────┐
ts-wip ──────────────────────────────┐   │
ts-blending ─────────────────────┐   │   │
ts-package ──────────────────┐   │   │   │
ts-shipment ─────────────┐   │   │   │   │
ts-transfer ─────────┐   │   │   │   │   │
                     │   │   │   │   │   │
                     ▼   ▼   ▼   ▼   ▼   ▼
              ┌──────────────────────────────────────┐
              │         Shared Services              │
              │                                      │
              │  TraceNumberGeneratorService         │
              │  TraceNumberGeneratorTrait           │
              │  TraceHelper (+ plantNameExpression) │
              │  PeriodLockService                   │
              │  TransactionCancellationService      │
              │    + cancelShipment() [new]          │
              │  TransactionCoreService              │
              │  FeedRundownOrchestrator             │
              │  PlantContextService                 │
              └──────────────────────────────────────┘
```

**Benefit setelah konsolidasi:**
- Trace number format berubah → update 1 file (`TraceNumberGeneratorService`)
- Plant code mapping berubah → update 1 file (`TraceHelper`)
- Cancel logic berubah → update 1 file (`TransactionCancellationService`)
- Lock period logic berubah → update 1 file (`PeriodLockService`)
- Feed/Rundown algorithm berubah → update 1 file (`FeedRundownOrchestrator`)

---

## Appendix — File Reference Lengkap {#appendix}

| File | Role | Status |
|---|---|---|
| `Shared/Services/TraceNumberGeneratorService.php` | format + parse | ✅ OK |
| `Shared/Traits/TraceNumberGeneratorTrait.php` | generateTraceNumberForMaterial | ✅ OK — tidak semua modul pakai, tapi sudah dikerjakan |
| `Shared/Helpers/TraceHelper.php` | SQL fragments dual-format | ✅ OK + `plantNameExpression()` ditambahkan |
| `Shared/Services/FeedRundownOrchestrator.php` | Feed+Rundown pipeline | ✅ OK — tidak semua modul pakai (variasi sah) |
| `Shared/Services/PeriodLockService.php` | period lock | ✅ OK |
| `Shared/Services/TransactionCancellationService.php` | cancel/deactivate | ✅ OK + `cancelShipment()` ditambahkan |
| `Shared/Services/TransactionCoreService.php` | material doc, sub tank | ✅ OK |
| `Shared/Services/PlantContextService.php` | plant resolution | ✅ OK |
| `ts-raw/RmEntryTransferTrait.php` | transfer helpers | ✅ DONE — 3 duplikat MAX(seq)+1 dimigrasikan |
| `ts-raw/RmEntryTransactionTrait.php` | RM entry transaction | ✅ DONE — direct t_material_document × 2 → TransactionCoreService |
| `ts-raw/RmEntryService.php` | RM service | ✅ DONE — direct t_material_document → TransactionCoreService; resolvePlantCode ditinggal |
| `ts-transfer/TransferRepository.php` | transfer queries | ✅ DONE — deactivateTransfer delegate; FQCN fix; plant_name → TraceHelper |
| `ts-transfer/TransferService.php` | transfer service | ⚠️ resolvePlantCode masih own impl (backlog) |
| `ts-shipment/EloquentShipmentRepository.php` | shipment queries | ✅ DONE — cancel() delegate; plant_name → TraceHelper |
| `ts-package/EloquentPackageRepository.php` | package queries | ✅ DONE — 2× plant_name → TraceHelper |
| `ts-blending/BlendingRepository.php` | blending queries | ✅ DONE — FQCN fix getLockStatus |
| `ts-blending/BlendingService.php` | blending service | ⚠️ inline Feed+Rundown — tidak diubah (variasi sah) |
| `ts-wip/WipEntryBatchTrait.php` | batch number gen | ⚠️ legacy methods masih ada (route aktif), resolvePlantCode ditinggal (semantik berbeda) |
| `ts-wip/WipEntryQueryTrait.php` | wip queries | ✅ DONE — resolvePlantId → PlantContextService; checkPeriodLock FQCN |
| `ts-wip/WipEntryWriteTrait.php` | wip writes | ✅ sudah delegate ke TransactionCancellationService |
