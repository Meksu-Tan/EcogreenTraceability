# Browser Console Errors — Summary

## 1. Tracking Prevention (non-critical, abaikan)
```
Tracking Prevention blocked access to storage for https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css
```

---

## 2. HTTP 500 — Semua endpoint API

| Endpoint | Trigger |
|----------|---------|
| `GET /api/v1/trace/forward` | ForwardTraceView |
| `GET /api/v1/transactions/shipment-entries/sap-shipment` | BackwardTraceView |
| `GET /api/v1/transactions/rm-entries` | RmEntryView |
| `GET /api/v1/transactions/rm-entries/feed-log` | RmEntryView |
| `GET /api/v1/transactions/rm-entries/tanks` | RmEntryView, RmEntryModal, TransferModal |
| `GET /api/v1/transactions/rm-entries/materials` | RmEntryView, RmEntryModal, TransferModal |
| `GET /api/v1/transactions/rm-entries/suppliers/search` | RmEntryView |
| `GET /api/v1/transactions/rm-entries/new-number` | RmEntryModal |
| `GET /api/v1/transactions/rm-entries/dest-tanks` | TransferModal |
| `GET /api/v1/transactions/wip-entries/get_dtFeed` | WipEntryView (feedId: 001–009-04) |
| `GET /api/v1/transactions/wip-entries/get_dtRundown` | WipEntryView (rundownId: 011–063) |
| `GET /api/v1/transactions/wip-entries/get_cmbActiveTank_trf` | WipEntryView |
| `GET /api/v1/transactions/wip-entries/get_newFeedNumber` | WipEntryView |
| `GET /api/v1/transactions/wip-entries/get_feedLastBatch` | WipEntryView |
| `GET /api/v1/transactions/blendings` | BlendingView |
| `GET /api/v1/transactions/transfers` | TransferView |
| `GET /api/v1/transactions/stock` | StockInquiryView |
| `GET /api/v1/transactions/rm-report/summary` | RmReportView |
| `GET /api/v1/master/adjustment` | AdjustmentView |
| `GET /api/v1/master/adjustment/suppliers/search` | AdjustmentView |
| `GET /api/v1/master/adjustment/active-materials` | AdjustmentView |
| `GET /api/v1/master/adjustment/active-tanks` | AdjustmentView |
| `GET /api/v1/master/adjustment/entry-no` | AdjustmentView |
| `GET /api/v1/master/adjustment/period-headers` | AdjustmentView |

---

## 3. CORS Block — Subset dari 500 di atas

Endpoint yang juga kena CORS block (origin `localhost:5173` → `127.0.0.1:8000`):
- Semua `/api/v1/transactions/rm-entries/*`
- Semua `/api/v1/transactions/wip-entries/*`
- `/api/v1/transactions/blendings`
- `/api/v1/transactions/transfers`
- Semua `/api/v1/master/adjustment/*`

> **Catatan:** CORS block adalah efek dari 500. Laravel crash sebelum sempat kirim header `Access-Control-Allow-Origin`. Fix 500 dulu, CORS otomatis resolved.

---

## 4. Vue warn

```
[Vue warn]: Unhandled error during execution of native event handler
  at <VBtn color="error"> → <VAlert> → <BlendingView>
```

Terjadi karena retry button di error alert memanggil `fetchData` yang juga gagal 500.

---

## Root Cause

Browser console tidak menampilkan exception detail. Baca di:
```bash
grep -E "ERROR|CRITICAL|Exception|exception" storage/logs/laravel.log | tail -50
```
atau hit langsung:
```bash
curl -s "http://127.0.0.1:8000/api/v1/transactions/rm-entries?id_plant=0&page=1&per_page=5" \
  -H "Accept: application/json"
```
