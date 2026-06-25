# .template/ — UI Template Reference
## Materio Vuetify 3 | READ-ONLY — Jangan Dimodifikasi

Folder ini berisi referensi pola UI dari Materio Vuetify 3.
AI agent **WAJIB** membaca bagian yang relevan sebelum membangun Vue component apapun.

---

## Aturan Keras

| ✅ Boleh | ❌ Tidak Boleh |
|---------|---------------|
| Baca dan pelajari pola UI | Modifikasi file apapun di folder ini |
| Adaptasi pola ke module konteks | Copy-paste mentah tanpa adaptasi |
| Referensi Vuetify props & events | Import langsung dari .template/ ke source |
| Tiru struktur layout | Gunakan sebagai dependency project |

---

## Peta Folder & Kapan Digunakan

### `@core/components/` — Input Fields Standar
Berisi wrapper components untuk Vuetify input:
- `AppTextField.vue` — text input standar project
- `AppSelect.vue` — select/dropdown standar
- `AppCombobox.vue` — combobox dengan autocomplete
- `AppDateTimePicker.vue` — date/time picker

**Gunakan untuk:** referensi saat membangun form input fields. Ikuti prop pattern yang dipakai.

---

### `@core/utils/` — Helper Functions
Berisi utility yang sudah ada di project:
- Formatter: currency, tanggal, truncate text
- Validators: custom validation rules
- Helpers: debounce, deepClone, dll

**Gunakan untuk:** sebelum membuat utility baru, cek di sini dulu apakah sudah ada.

---

### `components/dialogs/` — Dialog Patterns
Pattern untuk semua jenis dialog:
- `ConfirmDialog.vue` — dialog konfirmasi (delete, approve, dll)
- `FormDialog.vue` — dialog dengan form input

**Pola yang perlu dipahami:**
```vue
<!-- Pattern: dialog dengan v-model -->
<template>
  <VDialog v-model="isOpen" max-width="500">
    <VCard>
      <VCardTitle>{{ title }}</VCardTitle>
      <VCardText>...</VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn @click="isOpen = false">Batal</VBtn>
        <VBtn color="primary" @click="onConfirm">Konfirmasi</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
```

**Gunakan untuk:** semua komponen dialog — delete confirm, form create/edit modal.

---

### `components/cards/` — Card Patterns
Pattern untuk stat dan KPI cards:
- `StatCard.vue` — single metric card dengan icon
- `KPICard.vue` — KPI dengan trend indicator
- `SummaryCard.vue` — summary dengan breakdown

**Pola stat card:**
```vue
<VCard>
  <VCardText class="d-flex align-center">
    <div>
      <span class="text-h4 font-weight-bold">{{ value }}</span>
      <p class="text-caption text-medium-emphasis mb-0">{{ label }}</p>
    </div>
    <VSpacer />
    <VAvatar color="primary" variant="tonal" size="42">
      <VIcon :icon="icon" size="26" />
    </VAvatar>
  </VCardText>
</VCard>
```

**Gunakan untuk:** dashboard summary, laporan ringkasan.

---

### `components/tables/` — Data Table Patterns
Pattern untuk halaman list dengan data table:
- Search field dengan debounce
- Filter dropdown (status, departemen, dll)
- VDataTable dengan action buttons
- Pagination

**Pola utama:**
```vue
<VTextField
  v-model="searchQuery"
  placeholder="Cari..."
  prepend-inner-icon="ri-search-line"
  class="mb-4"
/>
<VDataTable
  :headers="headers"
  :items="items"
  :items-per-page="itemsPerPage"
  :search="searchQuery"
>
  <template #item.actions="{ item }">
    <IconBtn @click="onEdit(item)">
      <VIcon icon="ri-edit-line" />
    </IconBtn>
    <IconBtn @click="onDelete(item)" color="error">
      <VIcon icon="ri-delete-bin-line" />
    </IconBtn>
  </template>
</VDataTable>
```

**Gunakan untuk:** semua halaman list — apprentice list, employee list, dll.

---

### `pages/user-management/` — Template CRUD Lengkap
Referensi utama untuk halaman modul baru. Berisi:
- `index.vue` — halaman list dengan search, filter, table, pagination
- `[id].vue` — halaman detail dengan tab sections
- Pattern: load data di `onMounted`, update via store action

**Ini adalah template paling sering dirujuk.** Setiap modul baru yang punya halaman list wajib baca ini dulu.

---

### `pages/forms/` — Form Layouts
Pattern untuk form create/edit:
- Layout dua kolom vs satu kolom
- VForm dengan `ref="formRef"` dan `validate()`
- Error handling dari API response
- Submit button dengan loading state

**Gunakan untuk:** halaman create/edit yang tidak pakai dialog (form full page).

---

### `pages/charts/` — ApexCharts Patterns
Konfigurasi chart yang theme-aware:
- `RadarChart.vue` — competency radar chart
- `BarChart.vue` — comparison bar chart
- `DonutChart.vue` — composition donut chart

**Pola penting:**
```javascript
// Theme-aware colors — jangan hardcode
const chartColors = computed(() => ({
  primary: vuetifyTheme.current.value.colors.primary,
  ...
}))
```

**Gunakan untuk:** semua chart di dashboard dan report.

---

### `layouts/` — Layout Reference
Struktur layout utama:
- Default layout: sidebar + navbar + main content
- Blank layout: tanpa sidebar (untuk login, error pages)
- Component nav: breadcrumb, user menu, theme switcher

**Gunakan untuk:** jika ada perubahan layout atau navigasi baru.

---

## Alur Kerja dengan .template/

```
1. Terima task membangun komponen baru
        │
        ▼
2. Identifikasi jenis komponen
   (list? form? dialog? chart? card?)
        │
        ▼
3. Baca referensi yang sesuai di .template/
   Fokus pada: struktur, Vuetify props, event pattern
        │
        ▼
4. Konfirmasi pemahaman ke user/agent
   "Saya akan adaptasi pola dari .template/pages/user-management/
    dengan perubahan: [sebutkan adaptasi]"
        │
        ▼
5. Implementasikan dengan adaptasi
   - Nama sesuai modul (apprentice, bukan user)
   - Data dari store Pinia modul ini
   - Ikuti rules CLAUDE.md (ps-/pe-, PascalCase, dll)
        │
        ▼
6. Jangan lupa: tidak ada axios di View,
   semua API call via services/
```

---

*File ini adalah panduan untuk AI agent. Letakkan di `.template/README.md`.*
*Update jika ada penambahan subfolder template baru.*
