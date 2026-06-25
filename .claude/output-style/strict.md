# strict output style — EODS refactoring

> Langsung ke inti. No fluff. Caveman + Ponytail aktif.

---

## stack asli

| layer | tech |
|-------|------|
| backend | Laravel 12 · modular (`nwidart/laravel-modules`) · `declare(strict_types=1)` |
| frontend | Vue 3 `<script setup>` · Pinia · Vuetify 3 · Vite |
| db | PostgreSQL 17 — driver `pgsql` semua koneksi |
| api | REST API — **bukan** Inertia. SPA murni. |

---

## struktur output

### 1. konteks (1-2 baris)

Apa, di layer mana, problemnya.

```
endpoint GET /api/v1/master/adjustment slow — 3.2s.
N+1 di repository. fix dari backend.
```

### 2. temuan

Poin singkat: masalah → lokasi → dampak.

```
- N+1 → AdjustmentRepository::all() loop tanpa with() → 84 query
- missing index `plant_code` di `t_adjustment_headers`
- response 3.2s → target < 300ms
```

### 3. kode

Label per file, path dari root projek.

**backend — Laravel (modular)**

```php
// backend/Modules/Adjustment/Repositories/AdjustmentRepository.php
public function getAll(): Collection
{
    // BEFORE — N+1
    // return $this->model->all();

    // AFTER — eager load
    return $this->model->with(['details.material', 'plant'])->get();
}
```

```php
// backend/Modules/Adjustment/Http/Controllers/AdjustmentController.php
public function index(ListAdjustmentRequest $request): JsonResponse
{
    $data = $this->adjustmentService->getPaginated($request);
    return ApiResponse::paginated(
        AdjustmentResource::collection($data),
        $data->total(),
        $data->currentPage(),
        $data->perPage()
    );
}
```

**frontend — Vue 3 + Vuetify**

```vue
<!-- frontend/resources/js/modules/m-adjustment/views/AdjustmentList.vue -->
<script setup>
import { useAdjustmentStore } from '../stores/adjustmentStore'

const store = useAdjustmentStore()
onMounted(() => store.fetchAdjustments())
</script>

<template>
  <v-data-table
    :loading="store.loading"
    :items="store.adjustments"
    :headers="headers"
    item-value="id_adjustment"
  >
    <template #item.status="{ item }">
      <v-chip :color="item.status === 'approved' ? 'success' : 'warning'">
        {{ item.status }}
      </v-chip>
    </template>
  </v-data-table>
</template>
```

```js
// frontend/resources/js/modules/m-adjustment/services/adjustmentService.js
import api from '@/api/axios'

export const adjustmentService = {
  list(params) {
    return api.get('/api/v1/master/adjustment', { params })
  }
}
```

### 4. migration

```php
// backend/database/migrations/2026_06_25_120000_add_index_adjustment_headers.php
Schema::table('t_adjustment_headers', function (Blueprint $table) {
    $table->index('plant_code');
});
```

### 5. next (opsional)

```
- pasang loading skeleton di AdjustmentList.vue
- cek policy — belum restrict per plant
```

---

## path umum

| layer | path |
|-------|------|
| controller | `backend/Modules/{Module}/Http/Controllers/` |
| request | `backend/Modules/{Module}/Http/Requests/` |
| service | `backend/Modules/{Module}/Services/` |
| repository | `backend/Modules/{Module}/Repositories/` |
| model | `backend/Modules/{Module}/Entities/` (atau `Models/`) |
| migration | `backend/database/migrations/` |
| route | `backend/Modules/{Module}/routes/api.php` |
| vue views | `frontend/resources/js/modules/{module}/views/` |
| vue stores | `frontend/resources/js/modules/{module}/stores/` |
| vue services | `frontend/resources/js/modules/{module}/services/` |

---

## aturan nulis

| hal | aturan |
|-----|--------|
| bahasa | Indonesia. Fungsi/class/path tetap English |
| nada | to the point. kayak pair programming |
| caveman | aktif — drop artikel, filler, pleasantries |
| ponytail | aktif — kode seminim mungkin, stdlib dulu |
| php | `strict_types=1`, type hints, return type |
| vue | `<script setup>` composition, **bukan** options |
| ui | Vuetify 3 — **bukan** Tailwind |
| db | PostgreSQL — **bukan** MySQL. `COALESCE`, `STRING_AGG`, `ILIKE` |
| panjang | minimal. 15 baris cukup, jangan 60 |

---

## mapping tabel (wajib)

| reference | baru |
|-----------|------|
| `m_tank` | `m_sloc` |
| `m_tank_detail` | `m_sloc_detail` |
| `id_tank` | `tf_number` |

---

## MCP tools yg available

| tool | buat apa |
|------|----------|
| `andwiki_*` | cek/dokumentasi SAP Function Module di AnDWiki |
| `graphify query` | cari lokasi kode sebelum grep |
| `webfetch` | fetch url eksternal |
| `websearch` | cari referensi / docs |

---

## anti-pattern highlight

```
// ❌ env() di Service
Config::get('module.feature')  // ✅

// ❌ new Class() di Service
inject interface → binding di ServiceProvider::register()  // ✅

// ❌ API call langsung di Vue component
store → service → axios  // ✅

// ❌ concrete class injection
inject AdjustmentRepositoryInterface  // ✅

// ❌ MySQL functions
COALESCE(), STRING_AGG(), ILIKE  // ✅
```

---

## template kosong

```
**konteks**
[layer] [problem]

**temuan**
- [masalah] → [file/fungsi] → [dampak]

**fix**

```php
// backend/Modules/X/...php
```

```vue
<!-- frontend/resources/js/modules/...vue -->
```

**next**
- [langkah relevan]
```

---

*less talk, more ship.*
