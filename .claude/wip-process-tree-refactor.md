# Refaktor WIP Process Mapping Tree

## Latar Belakang

Halaman WIP Transaction (`trans_wip/index.blade.php` di legacy, `WipEntryView.vue` di new stack) menampilkan **process mapping tree** — diagram alir produksi dari Feed → Process → Rundown per section pabrik.

Saat ini, struktur tree hardcoded di **dua tempat**:
1. **Frontend Vue**: `WipEntryView.vue` (1054 baris, sections + steps + mode logic)
2. **Backend Repository**: `WipEntryQueryTrait.php` (958 baris, mapping frontend ID → DB ID)

Tujuan refaktor: **semua struktur tree dari DB**, UI auto-update kalau data diubah.

---

## Arsitektur Saat Ini

```
Frontend (Vue)                              Backend (Laravel)
┌─────────────────────────────┐           ┌──────────────────────────┐
│ WipEntryView.vue            │           │ WipEntryService          │
│                             │           │  └─ getWipTree() →       │
│ wipSectionsBase = [         │           │     SELECT m_material    │
│   section101/102 ───────────┼───harcode─┤     ┌────────────────┐   │
│   section103     ───────────┼───harcode─┤     │ WipEntryQueryTrait │
│   section104     ───────────┼───computed─┤     │  ┌────────────────┐│
│   section105     ───────────┼───computed─┤     │  │mapFrontendToDb││
│   section106     ───────────┼───computed─┤     │  │  feed_id ☠    ││
│   section110     ───────────┼───harcode─┤     │  │mapFrontendToDb││
│   section111/116 ───────────┼───harcode─┤     │  │  rundown_id ☠ ││
│   section112/114 ───────────┼───computed─┤     │  └───────────────┘│
│   section302     ───────────┼───harcode─┤     └────────────────────┘
│ ]                           │           │
│                             │           │ Config: wip_material_mapping.php
│ stepRows(step) → API call   │──────API──→  └─ feed_material_map
│                             │           │  └─ rundown_to_feed_map
│ Mode logic:                 │           │
│   selectedMode104 = ref(1)  │           │ Tables (saat ini):
│   selectedMode105 = ref(1)  │           │   m_material (id_rundown, id_feed, type)
│   selectedChain105 = 'me28' │           │   t_trace_header (to_trace_no, from_trace_no)
│   selectedMode106 = ref(1)  │           │   t_balance_header
│   selectedMode112 = ref(1)  │           │   m_sloc
└─────────────────────────────┘           └──────────────────────────┘
```

### 4 Lapis Hardcode

| # | Lapisan | Lokasi | Contoh Data |
|---|---------|--------|-------------|
| 1 | **Daftar Section** | Vue: `wipSectionsBase[]` | 101/102, 103, 104, 105, 106/114, 110, 111/116, 112/114, 302 |
| 2 | **Step per Section** | Vue: `section104Steps` dkk (computed) | feed('CRUDE-ME FEEDS','003'), rundown('BDME RUNDOWNS','023') |
| 3 | **Mode Logic** | Vue: `selectedMode*` refs | section 105: short-chain(me28) vs long-chain(me80); section 106: ecorol24 vs ecorol12/14 |
| 4 | **ID Mapping** | Backend: `mapFrontendSectionToDb*()` | 40+ mapping entry: '104'=>'ume'=>'033', 'bdme'=>'023', dll |

### Yang Udah DB-Driven

- `m_material` — nyimpen `id_rundown`, `id_feed`, `type` (WIP/RM) per material
- `getWipTree()` — query `m_material` + latest feed/rundown trace via subquery
- **TAPI** hasilnya flat array, bukan nested tree

---

## Target Arsitektur

```
Frontend (Vue)                              Backend (Laravel)
┌─────────────────────────────┐           ┌──────────────────────────┐
│ WipEntryView.vue (ramping)  │           │ WipEntryService          │
│                             │           │  └─ getWipTree() →       │
│ onMounted → fetchWipTree()  │──────API──→     nested tree dari DB  │
│   ↓                         │           │                          │
│ render dari response JSON   │           │ Tables (baru):           │
│ (v-for section → v-for step)│           │  ┌──────────────────┐    │
│                             │           │  │ m_wip_section    │    │
│ Gak ada hardcode section/   │           │  ├──────────────────┤    │
│ step di JS                  │           │  │ m_wip_process_   │    │
│                             │           │  │ step             │    │
│ Mode switch:                │           │  └──────────────────┘    │
│   tinggal render options    │           │                          │
│   dari DB column            │           │ Config (tambah):         │
│                             │           │  └─ wip_tree.php         │
│ ID mapping:                 │           │     (overrides per plant)│
│   masuk sebagai field       │           │                          │
│   di step (feed_id,         │           │ Tables (existing):       │
│   rundown_id, pipe_number)  │           │   m_material             │
└─────────────────────────────┘           │   t_trace_header         │
                                          │   m_sloc                 │
                                          └──────────────────────────┘
```

---

## 2 Tabel Baru

### `m_wip_section`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint PK auto-increment | Primary key |
| `code` | varchar(20) NOT NULL UNIQUE | Kode section, contoh: `101`, `103`, `104`, `105`, `106`, `110`, `111`, `112`, `302` |
| `name` | varchar(100) NOT NULL | Nama display, contoh: `Section 101/102`, `Section 106/114` |
| `plant_id` | int NULL | Filter per plant. NULL = all plants |
| `sort_order` | int NOT NULL DEFAULT 0 | Urutan tampil |
| `status` | smallint NOT NULL DEFAULT 1 | 1 = aktif, 0 = nonaktif |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `m_wip_process_step`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint PK auto-increment | Primary key |
| `section_id` | bigint FK → `m_wip_section.id` | Parent section |
| `parent_step_id` | bigint FK NULL → `m_wip_process_step.id` | Parent step (untuk nested/group) |
| `step_type` | varchar(20) NOT NULL | Enum: `feed`, `rundown`, `label`, `mode_switch` |
| `label` | varchar(200) NOT NULL | Teks display, contoh: `CPKO FEEDS (101 FT0113)`, `START OF SECTION 101/102` |
| `feed_id` | varchar(20) NULL | ID feed untuk backend query. Contoh: `001`, `003`, `006-01` |
| `rundown_id` | varchar(20) NULL | ID rundown untuk backend query. Contoh: `011`, `033`, `018` |
| `pipe_number` | varchar(50) NULL | Nomor pipe. Contoh: `101 FT0113`, `102 FT0109` |
| `dcs_tag` | varchar(50) NULL | DCS quantifier tag. Contoh: `101_FT0113` |
| `mode_group` | varchar(50) NULL | Mode group untuk mode_switch & conditional steps. Contoh: `mode104`, `mode105`, `mode106`, `mode112` |
| `mode_value` | varchar(50) NULL | Nilai mode yang bikin step ini aktif. Contoh: `1`, `2`, `short`, `long` |
| `sort_order` | int NOT NULL DEFAULT 0 | Urutan step dalam section |
| `status` | smallint NOT NULL DEFAULT 1 | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Contoh Data

**m_wip_section:**
| id | code | name | sort_order |
|----|------|------|-----------|
| 1 | 101 | Section 101/102 | 1 |
| 2 | 103 | Section 103 | 2 |
| 3 | 104 | Section 104 | 3 |
| 4 | 105 | Section 105 | 4 |
| 5 | 106 | Section 106/114 | 5 |
| 6 | 110 | Section 110 | 6 |
| 7 | 111 | Section 111/116 | 7 |
| 8 | 112 | Section 112/114 | 8 |
| 9 | 302 | Section 302 | 9 |

**m_wip_process_step (section 104 contoh):**
| section_id | step_type | label | feed_id | rundown_id | pipe_number | mode_group | sort_order |
|------------|-----------|-------|---------|------------|-------------|------------|-----------|
| 3 | label | START OF SECTION 104 | null | null | null | null | 1 |
| 3 | feed | CRUDE-ME FEEDS (104 F0110) | 003 | null | 104 F0110 | null | 2 |
| 3 | label | PROCESS OF SECTION 104 | null | null | null | null | 3 |
| 3 | rundown | BDME RUNDOWNS | null | 023 | null | null | 4 |
| 3 | rundown | UME RUNDOWNS (104 F0110) | null | 033 | 104 F0110 | null | 5 |
| 3 | rundown | ME28 RUNDOWNS (104 F0332) | null | 043 | 104 F0332 | mode104 | 6 |
| 3 | rundown | ECONOATE 665 RUNDOWNS | null | 053 | null | null | 7 |
| 3 | rundown | ME80 RUNDOWNS | null | 063 | null | null | 8 |
| 3 | label | END OF SECTION 104 | null | null | null | null | 9 |

**Mode switch + conditional steps (section 105 contoh):**
| section_id | step_type | label | feed_id | mode_group | mode_value | parent_step_id | sort_order |
|------------|-----------|-------|---------|------------|------------|----------------|-----------|
| 4 | mode_switch | Mode Chain | null | mode105 | null | null | 1 |
| 4 | feed | ME28 FEEDS (105 FQ104) | 006-01 | mode105 | short | 1 | 2 |
| 4 | rundown | CFA28 RUNDOWNS (105 FQ808) | null | mode105 | short | 1 | 3 |
| 4 | feed | ME80 FEEDS (105 FQ104) | 006-02 | mode105 | long | 1 | 4 |
| 4 | rundown | CFA80 RUNDOWNS (105 FQ808) | null | mode105 | long | 1 | 5 |

---

## Backend: Service `getWipTree()`

### API Response Format

```
GET /api/v1/transactions/wip-entries/tree?plant_id=1
```

```json
{
  "sections": [
    {
      "code": "101",
      "name": "Section 101/102",
      "steps": [
        { "type": "label", "label": "START OF SECTION 101/102" },
        {
          "type": "feed",
          "label": "CPKO FEEDS (101 FT0113)",
          "feedId": "001",
          "pipeNumber": "101 FT0113",
          "dcsTag": "101_FT0113",
          "latestTrace": {
            "traceNo": "32506100010011001",
            "date": "2026-06-25",
            "qty": 45.320,
            "sloc": "T-101"
          }
        },
        { "type": "label", "label": "PROCESS OF SECTION 101/102", "icon": "arrow-down" },
        {
          "type": "rundown",
          "label": "DA-OIL RUNDOWNS (102 FT0109)",
          "rundownId": "011",
          "pipeNumber": "102 FT0109",
          "dcsTag": null,
          "latestTrace": { ... }
        },
        { "type": "label", "label": "END OF SECTION 101/102", "icon": "flag" }
      ]
    }
  ],
  "modeConfigs": {
    "mode105": {
      "type": "select",
      "default": "short",
      "options": [
        { "value": "short", "label": "Short Chain (ME28)" },
        { "value": "long", "label": "Long Chain (ME80)" }
      ]
    }
  }
}
```

Fungsi `getWipTree()` di Service:
1. Ambil semua `m_wip_section` WHERE `status=1` ORDER `sort_order`
2. Per section, ambil `m_wip_process_step` WHERE `section_id=X` AND `status=1` ORDER `sort_order`
3. Untuk setiap step tipe `feed`/`rundown`, join `t_trace_header` ambil latest trace
4. Kumpulkan mode configs dari step `mode_switch`
5. Return nested tree + mode configs

### Hapus Hardcode Mapping

Setelah tree dari DB, method hardcode di `WipEntryQueryTrait` bisa dihapus:

| Method | Diganti Dengan |
|--------|----------------|
| `mapFrontendSectionToDbFeedId(section)` | Query dari tree: feed_id di step tipe feed |
| `mapFrontendSectionToDbRundownId(section,subgroup)` | Query dari tree: rundown_id di step tipe rundown |
| `mapRundownToFeedSectionId(rundownId)` | Config `rundown_to_feed_map` atau DB lookup |
| `mapSectionToMaterialId(section)` | Join m_material via id_feed / id_rundown |

---

## Frontend: Vue Refactor

### Sebelum (WipEntryView.vue ~1054 baris)

```js
// ☠ HARDCODED
const wipSectionsBase = [
  section('section101', 'Section 101/102', [
    label('START'), feed('CPKO FEEDS','001'), label('PROCESS'),
    rundown('DA-OIL','011'), rundown('PKFAD','021'), label('END'),
  ]),
  // ... 8 sections hardcoded
]

// ☠ HARDCODED MODE LOGIC
const section104Steps = computed(() => {
  if (selectedMode104.value === 1) { /* ... */ }
  // 20+ lines conditional
})
```

### Sesudah (ramping)

```vue
<template>
  <VCard v-for="section in tree.sections" :key="section.code">
    <VCardTitle>{{ section.name }}</VCardTitle>
    <VCardText>
      <div v-for="step in section.steps" :key="step.id">
        <div v-if="step.type === 'label'" class="label-row">{{ step.label }}</div>
        <VCard v-else-if="visible(step)" variant="outlined">
          <WipMiniTable ... />
        </VCard>
      </div>
    </VCardText>
  </VCard>
</template>

<script setup>
const tree = ref({ sections: [], modeConfigs: {} })
const modes = reactive({})  // dinamis dari modeConfigs

onMounted(async () => {
  tree.value = await wipApi.getWipTree()
  // Init mode defaults
  for (const [key, cfg] of Object.entries(tree.value.modeConfigs)) {
    modes[key] = cfg.default
  }
})

function visible(step) {
  if (!step.modeGroup) return true
  return modes[step.modeGroup] === step.modeValue
}
</script>
```

---

## Migration Plan

### Phase 1: Tabel + Seed
1. Migration `create_m_wip_section_table`
2. Migration `create_m_wip_process_step_table`
3. Seeder: insert semua existing section + step dari hardcode

### Phase 2: Backend
1. `WipTreeService` — baca tree dari DB
2. `WipTreeController` — endpoint `GET /api/v1/master/wip-tree`
3. Update `WipEntryService::getWipTree()` — panggil service baru
4. Tambah cache biar gak query tiap render

### Phase 3: Frontend
1. Hapus `wipSectionsBase`, `section*Steps`, semua computed
2. Ganti `fetchWipTree()` call di `onMounted`
3. Render nested dari response
4. Mode switch render dari `modeConfigs`

### Phase 4: Hapus Hardcode Lama
1. Hapus `mapFrontendSectionToDb*` dari `WipEntryQueryTrait`
2. Hapus config `wip_material_mapping.php` (opsional, bisa dipindah ke DB)
3. Hapus `mapSectionToMaterialId()`

---

## Keuntungan

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| **Ubah tree** | Edit Vue + backend mapping | INSERT/UPDATE DB, UI auto |
| **Tambah section** | Edit Vue + 2 mapping | INSERT `m_wip_section` + steps |
| **Tambah mode** | Edit Vue computed + ref | INSERT step `mode_switch` + conditional steps |
| **Perbedaan plant** | Gak bisa | `m_wip_section.plant_id` filter |
| **Baris code** | ~1500 baris (Vue + trait) | ~200 baris render logic |
| **Bug** | Mapping mismatch rentan | Single source of truth |

---

## Resiko & Mitigasi

| Resiko | Mitigasi |
|--------|----------|
| **Performance** — query tree + latest trace tiap load | Cache tree (cache key per plant), invalidate on section/step CRUD |
| **Mode logic kompleks** — beberapa mode nested (mode104 × chain mode) | `parent_step_id` buat hierarchical conditional |
| **Seed data besar** — 9 section × ~5-10 step = ~60-80 baris | Split per section di seeder, test satu-satu |
| **Config mapping hilang** — `wip_material_mapping.php` dipake juga di repo | Config fallback: kalau DB kosong, pakai config |

---

## Catatan Ponytail

- ✅ **Skip drag-drop UI** — cukup sort_order column
- ✅ **Skip WebSocket realtime** — polling `reloadAll()` cukup
- ✅ **Skip soft-delete cascade** — cukup `status = 0`
- ✅ **1 tabel step, bukan 3** — gak perlu `m_wip_feed` + `m_wip_rundown` terpisah, cukup `step_type` ENUM
- ✅ **mode_group + mode_value** — minimal, gak perlu table `m_wip_mode` terpisah
