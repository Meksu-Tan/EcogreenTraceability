# TEMPLATE-ADAPTATION.md — Panduan Build Komponen Frontend

> **Panduan AI Agent:** Baca file ini setelah membaca `.design/DESIGN-SYSTEM.md`.
>
> **Hierarki referensi — WAJIB dipatuhi:**
> 1. `.design/DESIGN-SYSTEM.md` — **PRIMARY**: visual spec, brand, warna, typography, spacing
> 2. `.template/` — **STRUCTURAL**: pola Vuetify component (layout, props, events)
> 3. File ini — **BRIDGE**: cara menerapkan (1) menggunakan struktur dari (2)
>
> Jika `.design` dan `.template` konflik → **`.design` selalu menang.**

---

## Daftar Isi

1. [Prinsip Design-First](#1-prinsip-design-first)
2. [Design Token → Vuetify Quick Reference](#2-design-token-vuetify-quick-reference)
3. [Otomatis via Vuetify Theme](#3-otomatis-via-vuetify-theme)
4. [Yang WAJIB Diubah Manual](#4-yang-wajib-diubah-manual)
5. [Yang DILARANG Dari Template](#5-yang-dilarang-dari-template)
6. [Build Per Komponen](#6-build-per-komponen)
7. [Logo Assets](#7-logo-assets)
8. [Icon Reference](#8-icon-reference)
9. [Alur Kerja](#9-alur-kerja)

---

## 1. Prinsip Design-First

```
PERTANYAAN YANG BENAR saat membangun komponen:

  "Design system menentukan ini harus tampil seperti apa?"
          │
          ▼
  "Pola Vuetify mana dari .template/ yang bisa saya
   gunakan sebagai struktur?"
          │
          ▼
  "Apa yang perlu saya ubah agar struktur itu sesuai
   dengan design spec?"
```

**Jangan** mulai dari "template punya apa, lalu saya modifikasi" — itu menghasilkan
komponen yang template-driven, bukan design-driven.

### Aturan Konflik

| Situasi | Aturan |
|---------|--------|
| Template pakai `color="#8C57FF"`, design pakai `color="primary"` | Pakai `color="primary"` (theme token) |
| Template pakai `text-h6`, design spec minta heading level 5 | Pakai `text-h5` (design spec menang) |
| Design spec tidak menyebut komponen ini, atau template punya pattern bagus yang tidak dibahas design | Boleh pakai struktur template, terapkan semua token design system (§2) |

---

## 2. Design Token → Vuetify Quick Reference

Quick reference mapping dari design token ke implementasi Vuetify.
Gunakan tabel ini untuk tidak bolak-balik ke DESIGN-SYSTEM.md saat coding.

### Warna

| Design Token | Hex | Vuetify prop |
|---|---|---|
| Brand Primary | `#42B240` | `color="primary"` |
| Brand Primary Hover | `#2A8030` | `color="primary-darken-1"` |
| Success | `#2E7D32` | `color="success"` |
| Warning | `#E65100` | `color="warning"` |
| Error | `#C62828` | `color="error"` |
| Info | `#01579B` | `color="info"` |
| Text Primary | `#1C2420` | CSS: `var(--v-theme-on-background)` |
| Text Secondary | `#5A6860` | class: `text-medium-emphasis` |
| Border Default | `#D4DDD8` | CSS: `var(--v-theme-neutral-200)` |
| Page Background | `#F7F4EF` | `color="background"` (auto via theme) |

### Typography

| Design Spec | Vuetify Class | Font | Size |
|---|---|---|---|
| Display / Hero | `text-h1` – `text-h2` | Montserrat | 56–44px |
| Page Title | `text-h3` | Montserrat | 36px |
| Section Heading | `text-h4` | Montserrat | 30px |
| Card Title / Stat Value | `text-h5 font-weight-bold` | Montserrat | 24px |
| Label / Overline | `text-h6` | Montserrat | 20px |
| Body Default | `text-body-1` | Source Sans 3 | 16px |
| Table Content | `text-body-2` | Source Sans 3 | 14px |
| Caption / Metadata | `text-caption` | Source Sans 3 | 12px |

### Komponen Defaults (sudah di-set via Vuetify config)

| Komponen | Default | Artinya |
|---|---|---|
| `VBtn` | `rounded="md" elevation="0"` | Tidak perlu ditulis ulang |
| `VCard` | `rounded="lg" elevation="1"` | Tidak perlu ditulis ulang |
| `VTextField` | `variant="outlined" density="comfortable" rounded="md"` | Tidak perlu ditulis ulang |
| `VSelect` | `variant="outlined" density="comfortable" rounded="md"` | Tidak perlu ditulis ulang |
| `VChip` | `rounded="pill"` | Tidak perlu ditulis ulang |

---

## 3. Otomatis via Vuetify Theme

Kode berikut **otomatis sesuai design system Ecogreen** via Vuetify theme —
tidak ada yang perlu diubah dari template untuk hal-hal ini:

### Warna semantic — otomatis

```vue
<!-- Vuetify theme menerjemahkan color tokens ke hex Ecogreen -->
<VBtn color="primary">Simpan</VBtn>               <!-- → #42B240 -->
<VChip color="success">Active</VChip>             <!-- → #2E7D32 -->
<VChip color="error">Inactive</VChip>             <!-- → #C62828 -->
<VChip color="warning">Pending</VChip>            <!-- → #E65100 -->
<VTextField color="primary" />
<VIcon color="primary" />
```

### Font dan spacing — otomatis via theme + design-tokens.css

```vue
<!-- Vuetify utility classes menggunakan font dari theme -->
<span class="text-h5">...</span>      <!-- Montserrat via theme -->
<span class="text-body-2">...</span>  <!-- Source Sans 3 via theme -->
<span class="text-caption">...</span>
```

### Component props yang sudah default

Props berikut sudah di-set sebagai global defaults (lihat tabel di §2) — boleh ditulis explicit untuk readability, hasilnya identik. Yang penting: **jangan ubah nilai defaultnya** kecuali ada alasan design yang jelas.

```vue
<VCard rounded="lg" elevation="1">...</VCard>      <!-- sama dengan <VCard> -->
<VBtn rounded="md" elevation="0">...</VBtn>        <!-- sama dengan <VBtn> -->
<VTextField density="comfortable" variant="outlined" />  <!-- sama dengan <VTextField> -->
```

---

## 4. Yang WAJIB Diubah Manual

### Template utility classes

| Template (❌ Jangan) | Ecogreen (✅ Pakai) | Alasan |
|---|---|---|
| `text-base` | `text-body-2` | `text-base` adalah custom Materio |
| `text-high-emphasis` | hapus | Materio custom opacity class |
| `text-medium-emphasis` | `text-medium-emphasis` | ✅ Vuetify native — boleh dipakai |
| `font-weight-semibold` | `font-weight-semibold` | ✅ Vuetify native |
| `text-h6 font-weight-semibold` pada stat value | `text-h5 font-weight-bold` | Design spec: stat value = Montserrat 24px bold |
| Hardcode warna: `:color="'#8C57FF'"` | `color="primary"` | Design token |
| Emoji dalam teks | Hapus / ganti dengan VIcon | Design system melarang emoji |
| `class="trophy"` atau dekorasi Materio | Hapus | Aset Materio |

### Import paths yang perlu diganti

| Template (❌) | Ecogreen (✅) |
|---|---|
| `import trophy from '@images/misc/trophy.png'` | Hapus — tidak ada aset dekoratif |
| `import { kFormatter } from '@core/utils/formatters'` | Buat di `resources/js/utils/formatters.js` |

---

## 5. Yang DILARANG Dari Template

| ❌ Jangan copy | Alasan |
|---|---|
| `@core-scss/template/` SCSS imports | Materio override — bentrok dengan design system |
| `@layouts/` components (VerticalNav, dll) | Layout project sudah punya sendiri |
| `@images/` paths | Tidak ada di project |
| `plugins/iconify/` setup | Project pakai Remix Icon |
| `plugins/webfontloader.js` | Font sudah di `design-tokens.css` |
| Hardcode hex color | Selalu pakai theme token |
| `IconBtn` alias | Project belum define — pakai `VBtn variant="text" icon` |
| `controlledComputed()` | Materio utility — pakai `computed()` |
| `document.documentElement.classList` / DOM API | Vuetify state WAJIB via composables |

---

## 6. Build Per Komponen

Setiap komponen dibangun dengan urutan: **Design Spec → Template Structure → Adaptasi**.

> ⚠ **Penting:** Banyak file `.vue` yang direferensikan di section ini **tidak ada sebagai file implementasi** —
> hanya ada sebagai README pola. Lihat `.template/README.md` section "Yang Kurang" untuk daftar lengkap.
> Saat "Ambil struktur dari X.vue" — baca README-nya untuk memahami polanya, bukan file .vue-nya.

### 6.1 Cards & Statistics

**Design spec** (dari `.design/DESIGN-SYSTEM.md`):
- Card title: `text-h5 font-weight-bold` (Montserrat 24px)
- Avatar icon: `rounded="md"`, `color="primary"`, `variant="tonal"`
- Card border: 1px `neutral-200`, shadow subtle
- Stat value: `text-h5 font-weight-bold`
- Caption: `text-caption text-medium-emphasis`

**Ambil struktur dari** `.template/components/cards/StatCard.vue` atau `KPICard.vue`.

```vue
<!-- Build hasil — design-first, struktur dari template -->
<VCard class="eco-card">
  <VCardText class="d-flex align-center">
    <VAvatar size="44" rounded="md" color="primary" variant="tonal" class="me-4">
      <VIcon :icon="icon" size="30" />
    </VAvatar>
    <div>
      <span class="text-caption text-medium-emphasis">{{ label }}</span>
      <div class="d-flex align-center flex-wrap ga-2 mt-1">
        <span class="text-h5 font-weight-bold">{{ value }}</span>
        <div v-if="trend" class="d-flex align-center"
             :class="trend > 0 ? 'text-success' : 'text-error'">
          <VIcon :icon="trend > 0 ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" size="20" />
          <span class="text-body-2">{{ Math.abs(trend) }}%</span>
        </div>
      </div>
    </div>
  </VCardText>
</VCard>

<style scoped>
.eco-card { border: 1px solid var(--v-theme-neutral-200) !important; }
.eco-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12) !important; }
</style>
```

### 6.2 Tables & Lists

**Design spec**: body text `text-body-2` (Source Sans 3 14px), action icons pakai Remix Icon.

**Ambil struktur dari** `.template/components/tables/` dan `.template/pages/user-management/index.vue`.

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
  class="text-body-2"
>
  <template #item.actions="{ item }">
    <VBtn variant="text" color="default" density="comfortable" icon @click="onEdit(item)">
      <VIcon icon="ri-edit-line" />
    </VBtn>
    <VBtn variant="text" color="error" density="comfortable" icon @click="onDelete(item)">
      <VIcon icon="ri-delete-bin-line" />
    </VBtn>
  </template>
</VDataTable>
```

### 6.3 Forms & Inputs

**Design spec**: field label `text-body-2`, semua field `variant="outlined" density="comfortable"` (sudah default), submit button `color="primary"`.

**Ambil struktur dari** `.template/pages/forms/`.

```vue
<VForm ref="formRef" @submit.prevent="onSubmit">
  <VRow>
    <VCol cols="12" md="6">
      <VTextField v-model="form.name" label="Nama" :rules="[required]" />
    </VCol>
    <VCol cols="12" md="6">
      <VSelect v-model="form.type" :items="types" label="Tipe" />
    </VCol>
  </VRow>
  <VRow>
    <VCol cols="12">
      <VBtn type="submit" :loading="loading" color="primary">
        Simpan
      </VBtn>
      <VBtn variant="outlined" class="ms-3" @click="onCancel">
        Batal
      </VBtn>
    </VCol>
  </VRow>
</VForm>
```

### 6.4 Dialogs & Modals

**Design spec**: dialog title `text-h5 font-weight-bold`, confirm button `color="primary"`, cancel button `variant="outlined"`.

**Ambil struktur dari** `.template/components/dialogs/ConfirmDialog.vue` atau `FormDialog.vue`.

```vue
<VDialog v-model="isOpen" max-width="500">
  <VCard>
    <VCardTitle class="text-h5 font-weight-bold pa-6 pb-2">
      {{ title }}
    </VCardTitle>
    <VCardText>{{ message }}</VCardText>
    <VCardActions class="pa-6 pt-0">
      <VSpacer />
      <VBtn variant="outlined" @click="isOpen = false">Batal</VBtn>
      <VBtn color="primary" :loading="loading" @click="onConfirm">Konfirmasi</VBtn>
    </VCardActions>
  </VCard>
</VDialog>
```

### 6.5 Charts

**Design spec**: warna chart mengikuti Vuetify theme — jangan hardcode hex.

**Ambil struktur dari** `.template/pages/charts/`.

```javascript
// Theme-aware — otomatis sesuai eco/ecoDark
const chartColors = computed(() => ({
  primary:    vuetifyTheme.current.value.colors.primary,   // #42B240
  success:    vuetifyTheme.current.value.colors.success,   // #2E7D32
  warning:    vuetifyTheme.current.value.colors.warning,   // #E65100
  surface:    vuetifyTheme.current.value.colors.surface,
  background: vuetifyTheme.current.value.colors.background,
}))
```

### 6.6 Theme Switching

**Design spec**: `eco` (light) dan `ecoDark` (dark) — nama theme sesuai Vuetify config.

**WAJIB** via Vuetify composables. **Ambil pola dari** `.template/@core/components/ThemeSwitcher.vue`.

```vue
<script setup>
import { useTheme } from 'vuetify'

const { global: globalTheme } = useTheme()
const isDark = computed(() => globalTheme.name.value === 'ecoDark')

const toggleTheme = () => {
  globalTheme.name.value = isDark.value ? 'eco' : 'ecoDark'
  localStorage.setItem('theme', globalTheme.name.value)
}
</script>
```

```javascript
// ❌ JANGAN — tidak berpengaruh ke Vuetify
document.documentElement.classList.toggle('dark')
document.documentElement.setAttribute('data-theme', 'dark')
```

---

## 7. Logo Assets

Aset logo dari `.design/assets/` **WAJIB** di-copy ke `frontend/public/` saat setup project.

### File List

| Source (`.design/assets/`) | Dest (`frontend/public/`) | Penggunaan |
|---|---|---|
| `logo-symbol.svg` | `/logo-symbol.svg` | Favicon — referensi di `index.html` |
| `logo-stacked.jpg` | `/logo-stacked.jpg` | Sidebar & login page |
| `logo-horizontal.jpg` | `/logo-horizontal.jpg` | App bar / navbar |
| `logo.svg` | `/logo.svg` | Fallback vector |
| `logo-white.svg` | `/logo-white.svg` | Dark background |
| `favicon.ico` | `/favicon.ico` | Fallback favicon |

### Penggunaan di Komponen

```vue
<!-- Sidebar / Login -->
<v-img src="/logo-stacked.jpg" max-width="180" alt="Ecogreen Oleochemicals" />

<!-- App bar -->
<v-img src="/logo-horizontal.jpg" max-height="32" max-width="180" alt="Ecogreen Oleochemicals" />
```

```html
<!-- index.html -->
<link rel="icon" type="image/svg+xml" href="/logo-symbol.svg" />
```

| ✅ WAJIB | ❌ JANGAN |
|---|---|
| Copy dari `.design/assets/` ke `frontend/public/` | Import logo via JS bundler |
| Pakai `/logo-*` path langsung (static) | Pindahkan atau rename aset di `.design/assets/` |
| Set `alt` text deskriptif | Biarkan `alt` kosong |
| `logo-white.svg` di atas background gelap | `logo.svg` di atas background gelap |

---

## 8. Icon Reference

Template dan project sama-sama pakai **Remix Icon** (`ri-*`). Tidak perlu mapping.

| Aksi | Icon |
|------|------|
| Search | `ri-search-line` |
| Add / Create | `ri-add-line` |
| Edit | `ri-edit-line` |
| Delete | `ri-delete-bin-line` |
| View / Detail | `ri-eye-line` |
| Download | `ri-download-line` |
| Upload | `ri-upload-line` |
| Close | `ri-close-line` |
| Back | `ri-arrow-left-line` |
| Forward | `ri-arrow-right-line` |
| Check / Success | `ri-checkbox-circle-line` |
| Warning | `ri-alert-line` |
| Info | `ri-information-line` |
| More / Menu | `ri-more-2-line` |
| Filter | `ri-filter-line` |
| Sort | `ri-arrow-up-down-line` |
| PDF / Document | `ri-file-pdf-line` |
| User | `ri-user-line` |
| Dashboard | `ri-dashboard-line` |
| Settings | `ri-settings-line` |
| Logout | `ri-logout-box-line` |

Jika template pakai ikon dari library lain, cari padanan Remix Icon di https://remixicon.com.

---

## 9. Alur Kerja

```
TERIMA TASK: "Buat komponen X"
        │
        ▼
1. Baca .design/DESIGN-SYSTEM.md
   → Apa warna, font, spacing, radius yang berlaku?
   → Cek .design/preview/ jika butuh visual reference
        │
        ▼
2. Buka .template/TEMPLATE.md
   → Pola mana yang sesuai? (card? table? form? dialog? chart?)
   → Baca file spesifik di .template/ yang relevan
        │
        ▼
3. Baca §2 file ini (Quick Reference)
   → Map design token ke Vuetify props/classes
        │
        ▼
4. Build komponen — baca §6.x untuk jenis komponen yang sedang dibangun:
   • Struktur layout dari .template/
   • Hapus Materio-specific imports & classes (§4, §5)
   • Terapkan design tokens (§2, §3)
   • Font: sesuai type scale §2 (heading h1–h6 → Montserrat, body → Source Sans 3)
   • Warna: selalu via theme token, BUKAN hardcode hex
   • Emoji: hapus atau ganti VIcon
        │
        ▼
5. Verifikasi terhadap .design/DESIGN-SYSTEM.md:
   ✓ Warna primary = #42B240? (bukan purple #8C57FF)
   ✓ Heading = Montserrat?
   ✓ Body = Source Sans 3?
   ✓ Cards: rounded="lg", border neutral-200
   ✓ Buttons: rounded="md"
   ✓ Tidak ada DOM manipulation untuk Vuetify state
```

---

*File ini adalah living document. Update jika ada komponen template baru
atau aturan design system yang berubah.*
