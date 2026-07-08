# TRACE-DEV — Bug Analysis & Resolution Plan

**Tanggal:** 2026-07-08
**Status:** PLAN ONLY — no code executed. Awaiting approval before any fix.
**Sumber:** code-analysis terarah (graphify tidak bisa build graph — lihat catatan bawah).

---

## Catatan Metode (graphify)

`graphify` dijalankan scoped (`graphify-scope/` junction, 26 module dir) tapi **gagal menghasilkan `graph.json`**.
Penyebab: corpus PHP/Vue butuh Gemini key untuk semantic extraction; AST graphify tidak cover PHP → graph kosong.
Analisis root-cause dilakukan via grep/read terarah sebagai pengganti (lebih reliable untuk bug spesifik).
Folder `graphify-scope/` masih ada di root — bisa dihapus bila tidak diperlukan.

---

## Daftar Issue (11 total)

| # | Modul | Symptom |
|---|-------|---------|
| 1 | trace-forward / trace-backward | `created_at` di detail trace masih kosong |
| 2 | trace-forward (list) | Kolom tank / tf-no tidak lengkap (sloc belum clickable → specific tank) |
| 3 | ts-blending | Add qty blending material → stock material malah **nambah** (tdk berkurang) |
| 4 | ts-blending (list) | Kolom sloc belum sesuai (belum clickable → specific tank) |
| 5 | ts-transfer | Error "stock not enough" |
| 6 | ts-shipment / ts-package (entry) | `created_at` belum muncul |
| 7 | ts-package (entry) | Kolom sloc belum clickable → specific tank |
| 8 | ts-tsreport | Tabel blending transaction tidak ada |
| 9 | ts-tsreport | Transaksi tercatat 2x (dari save gagal stock-not-enough) |
| 10 | ts-rmreport (rm-to-prd) | Kolom `on prd`, `on adj`, `traced` masih kosong |
| 11 | m-adjustment (list) | `created_at` & `updated_at` di tabel adjustment list kosong |

---

## Pengelompokan (5 Cluster)

### Cluster A — `created_at` / `updated_at` tidak muncul (#1, #6, #11)

**#1 — trace-forward / trace-backward detail:**
- DB punya `created_at` (`timestamp default CURRENT_TIMESTAMP`); query sudah pilih `created_at`:
  - `trace-forward/.../Concerns/ForwardDetailQuery.php:95,102`
  - `trace-backward/.../Concerns/BackwardDetailQuery.php:93,104`
- `trace-forward` & `trace-backward` **TIDAK punya `Http/Resources`** (return raw array) → frontend detail view harus render `created_at`.
- **Root cause:** `created_at` ada di data tapi tdk di-render di frontend detail view.

**#6 — ts-shipment / ts-package entry:**
- `ts-shipment/app/Http/Resources/ShipmentEntryResource.php:23` sudah map `created_at`.
- `ts-package/app/Http/Resources/PackageEntryResource.php` ada — kemungkinan **belum** map `created_at`.
- **Root cause:** PackageEntryResource belum expose `created_at` (+ frontend entry view belum render).

**#11 — m-adjustment list:**
- Frontend SUDAH render keduanya: `m-adjustment/views/AdjustmentView.vue:94-96,115,117` (`{{ row.created_at || '-' }}`, `{{ row.updated_at || '-' }}`) → gap ada di backend.
- `AdjustmentRepository::getAdjustmentList` (`:33`) — SELECT di `:79` & `:108` memuat `created_at` tapi **TIDAK `updated_at`**.
- `updated_at` hanya muncul di write (`:1884, :1906, :2001`), tidak pernah di-SELECT list.
- **Root cause:** `updated_at` tdk di-SELECT (selalu `'-'`); `created_at` di-SELECT tapi mungkin payload key salah / kolom null di table; perlu cek table punya kolom `updated_at` & `created_at` + model `$timestamps`.
- **Fix:** tambah `updated_at` ke SELECT list query; pastikan kolom ada di table + model; map ke payload. Cross-check legacy adjustment list.

---

### Cluster B — SLOC clickable → specific tank (#2, #4, #7)

**Pattern SUDAH ada di `ts-transfer` (reference):**
- `ts-transfer/views/TransferView.vue:118` → `<td @click="openSubTankModal(trf)">{{ trf.sloc }}</td>`
- `ts-transfer/components/SubTankEditModal.vue:103` → fetch `getSpecificTanksRundown({sloc})` → tampil specific tank.
- Backend siap: `BlendingRepository::getActiveSpecificTanksRundown`, `getAllTanks`, `getTanks`; `TransferRepository` punya `getSpecificTanksRundown`.

**Belum di-replika ke:**
- #2 `trace-forward` list — kolom tank / tf-no tidak lengkap, sloc belum clickable.
- #4 `ts-blending` list — sloc belum clickable (komponen `SubTankEditModal.vue` ada di `ts-blending/components` tapi list belum wired).
- #7 `ts-package` entry — sloc statis (ada `SelectSubTankModal.vue` di `ts-package/views` tapi entry list/column belum wired).

**Fix:** extract `SubTankEditModal` jadi shared component; wire ke ForwardList (tank/tf-no), BlendingView list, PackageEntryView.

---

### Cluster C — Blending qty (jadikan legacy acuan logic) (#3)

**Symptom:** add qty blending material (acid 24) → stock acid 24 **nambah** (kelihatan di transfer qty) tapi tdk nambah di stock on hand beginning  balance tapi buat baris baru.

**Bukti:**
- `ts-blending/Services/BlendingService.php:executeBlendingFeed:313` → `Feed::generalFeed` dgn `qty=$qtySource`, `id_sloc=$row->tf_number` (source). Feed seharusnya **decrement** source.
- `:runBlendingRundown:403` → `Rundown::generalRundown` tambah produk blend ke target tank.
- Stock-on-hand (`ts-stock/Repositories/StockRepository.php:114,195`) baca `in_qty - out_qty`; "transfer qty" baca field berbeda → discrepancy.

**Root cause (hipotesis):** sign feed-path blending terbalik vs legacy — input material di-increment bukan decrement.
**Wajib verifikasi:** `Feed::generalFeed` + `BlendingRepository::addBlendingEntryMaterial` vs legacy `BlendingController` feed logic (`reference-dont-change/`).
**Fix:** samakan sign dgn legacy; tambah PHPUnit feature test (stock berkurang setelah blend).

---

### Cluster D — Transfer flow: not-enough, pending, tsreport dup (#5, #8, #9)

**#8 — Blending transaction hilang di tsreport:**
- `ts-tsreport/Repositories/TsReportRepository.php:69` & `:354` → `SUBSTRING(to_trace_no,1,1) NOT IN ('1','6','7','8','9')` → **prefix 8 (blending) sengaja di-exclude**.
- Service tdk punya `getTsReportBlending` (cuma rm/pck/shipment/transfer/wip) padahal `TsReportRequest.php:22` terima `type:blending`.
- **Fix:** add `getTsReportBlending` (query prefix 8, status=1) + service method + controller + frontend tab.

**#9 — Duplicate / tercatat padahal gagal (stock not enough):**
- tsreport baca `t_trace_header WHERE status=1` **TANPA filter `approval_status`** (semua section cuma `a.status=1`).
- `ts-transfer/Services/TransferService.php:processTransferTransaction:289` set `approval_status='DRAFT'` lalu `submit()` → `PENDING`. Flow input→pending→approve→APPROVED **sudah ada** (`TransferApprovalService`).
- `executeTransferWithAutoAdjustment:205` kalau response 4 → bikin ADJUSTMENT (prefix 9, di-exclude) + re-run transfer (prefix 7 persist). Maka "gagal"/not-enough tetap nulis trace header → muncul 2x di tsreport.
- **Fix:** tsreport transfer section filter `approval_status='APPROVED'` (atau exclude DRAFT/PENDING); pastikan transfer gagal tdk persist trace header; cek `getTransferList` jg filter status.

**#5 — Stock not enough:**
- `validateTransferPreConditions:388` return response 4 SEBELUM write → seharusnya tdk persist.
- Tapi frontend kemungkinan panggil `executeTransferWithAutoAdjustment` yg auto-adjust + re-run → "gagal" jd tercatat.
- **Fix:** frontend pakai endpoint benar + tsreport filter approval (lihat #9).

**Alur yang diharapkan (sudah sebagian ada):** user input form transfer → masuk pending → acc supervisor → tercatat di transfer list / tsreport / adjustment.

---

### Cluster E — RM to PRD kolom kosong (#10)

**Symptom:** `on prd`, `on adj`, `traced` kosong di rm-to-prd report.

**Bukti:**
- `ts-rmreport/Repositories/RmReportRepository.php:149` → `traced` = `CASE WHEN MAX(out_qty)=0 THEN 'N/A' ELSE '' END` (logic ada).
- `:162` subquery `i` → `qty_adjustment` (on adj) pake `adjSlocFilter`.
- `on prd` (production) → **tdk ada JOIN ke `t_prod_log`** di query rmreport (grep tdk temukan).
- **Root cause:** join `t_prod_log` utk on_prd kemungkinan hilang; kolom on_adj/traced mungkin tdk di-select/di-render frontend.
- **Fix:** tambah JOIN prod_log utk on_prd; verifikasi SELECT + frontend render. Banding legacy RM-to-PRD report.

---

## Rencana Eksekusi (berurutan, tiap selesai → test)

| Phase | Cluster | Aksi | Risk |
|-------|---------|------|------|
| 0 | — | Konfirmasi plan + cek legacy utk #3 & #10 | — |
| 1 | A | Surface `created_at`/`updated_at`: PackageEntryResource, forward/backward detail view, adjustment list SELECT (`updated_at` + pastikan kolom table) | Low–Med |
| 2 | B | Shared `SubTankEditModal`; wire ForwardList (tank/tf-no), BlendingView list, PackageEntryView | Med |
| 3 | D | tsreport: filter `approval_status`; add blending section; fix not-enough persist | High |
| 4 | C | Blending qty sign vs legacy + test | High |
| 5 | E | RM-to-PRD: JOIN prod_log (on_prd) + render on_adj/traced | Med |
| 6 | — | Regression test (PHPUnit + Vitest) + legacy parity | — |

**Cross-cutting:** `reference-dont-change/` jadi acuan wajib utk #3 (blending feed sign) & #10 (RM-to-PRD join) & #11 (adjustment list).

---

## Open Questions / Verifikasi

1. **#3:** `Feed::generalFeed` (Shared/Helpers) — apakah decrement benar untuk blending source? Banding legacy.
2. **#10:** kolom `on prd` di legacy diisi dari table mana (prod_log?)?
3. **#11:** table adjustment punya kolom `updated_at`? (folder `m-adjustment/database/migrations` tdk ditemukan — cek shared/legacy migration).
4. **#9:** apakah `getTransferList` (transfer list) sudah filter `approval_status` atau ikut menampilkan DRAFT/PENDING?
