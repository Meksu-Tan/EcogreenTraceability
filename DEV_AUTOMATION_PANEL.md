# 🛠️ Dev Automation Panel — Claude Code Prompt

## Konteks Project

Saya sedang mengembangkan aplikasi web dengan **Laravel** (backend) dan **Vue 3** (frontend). Saya ingin kamu membangun sebuah **floating dev panel** yang bisa digunakan untuk:

1. **Merekam aksi user** secara real-time (klik, isi form, navigasi, pilih dropdown, dsb.)
2. **Memutar ulang (replay)** aksi tersebut secara otomatis
3. **Menyimpan scenario** sebagai named test case
4. **Demo ke client** dengan step-by-step playback yang visual
5. **Export** ke Playwright/Cypress script

Panel ini **hanya muncul di environment development** (`APP_ENV=local`) dan tidak masuk ke production build.

---

## Langkah Pertama: Analisis Project

Sebelum menulis satu baris kode pun, lakukan hal berikut:

1. **Scan seluruh struktur project** — baca direktori root, `resources/js/`, `resources/views/`, `routes/web.php`, `routes/api.php`
2. **Identifikasi semua halaman / route** yang ada
3. **Identifikasi komponen Vue** yang dipakai di setiap halaman
4. **Identifikasi elemen interaktif** di setiap halaman:
   - Form fields (input, textarea, select, checkbox, radio)
   - Tombol aksi (submit, delete, view, edit, export, dll.)
   - Navigation links & sidebar menus
   - Modal triggers
   - Table action buttons (per-row actions)
5. **Baca color palette, font, dan design tokens** dari:
   - `tailwind.config.js` atau CSS variables
   - Komponen UI yang sudah ada (misal: Button, Modal, Badge)
   - `app.css` / `app.scss`

**Output analisis:** Buat ringkasan dalam comment di file utama berupa:
- Daftar semua route/halaman
- Elemen interaktif per halaman
- Design tokens yang ditemukan (warna, border-radius, font)

---

## Arsitektur yang Harus Dibangun

### File Structure

```
resources/js/
└── dev-panel/
    ├── DevPanel.vue              # Komponen utama (floating panel)
    ├── composables/
    │   ├── useRecorder.js        # Logic untuk record aksi
    │   ├── useReplayer.js        # Logic untuk replay aksi
    │   └── useScenarios.js       # Simpan/load/export scenarios
    ├── components/
    │   ├── PanelHeader.vue       # Toolbar (REC, PLAY, STOP, minimize)
    │   ├── StepList.vue          # List aksi yang terekam
    │   ├── StepItem.vue          # Satu item aksi
    │   ├── ScenarioManager.vue   # Save / load scenario
    │   ├── QuickActions.vue      # Shortcut login, navigasi cepat
    │   └── ExportModal.vue       # Export ke Playwright/Cypress
    └── index.js                  # Entry point, inject ke app
```

### Integrasi ke App Vue

Di `resources/js/app.js` (atau bootstrap utama), tambahkan:

```javascript
if (import.meta.env.VITE_APP_ENV === 'local') {
  const { default: DevPanel } = await import('./dev-panel/index.js')
  DevPanel.mount()
}
```

Di `.env`:
```
VITE_APP_ENV="${APP_ENV}"
```

---

## Spesifikasi Fitur Lengkap

### 1. 🔴 Recording Engine (`useRecorder.js`)

Rekam aksi berikut menggunakan event listeners global:

| Aksi | Event yang di-listen | Data yang disimpan |
|------|---------------------|-------------------|
| Klik button/link | `click` | selector, text, href (jika ada) |
| Isi input text | `input` + debounce 500ms | selector, value |
| Pilih dropdown | `change` pada `<select>` | selector, value, label |
| Centang checkbox | `change` pada checkbox | selector, checked state |
| Submit form | `submit` | form selector, semua field values |
| Navigasi halaman | `popstate` + router hook | from, to URL |
| Buka modal | mutation observer pada class modal | modal id/selector |
| Klik tombol delete/view | `click` pada tombol dengan teks/attr tertentu | selector, row data jika dalam table |

**Format satu step yang tersimpan:**
```javascript
{
  id: 'uuid',
  type: 'click' | 'fill' | 'select' | 'navigate' | 'submit' | 'check',
  timestamp: Date.now(),
  selector: 'CSS selector atau data-testid',
  value: 'nilai jika ada',
  label: 'teks human-readable untuk ditampilkan di panel',
  screenshot: null // opsional, untuk future
}
```

**Prioritas selector** (dari paling ke paling tidak spesifik):
1. `[data-testid="..."]`
2. `[name="..."]`
3. `#id`
4. Kombinasi tag + class yang unik
5. XPath sebagai fallback

### 2. ▶️ Replay Engine (`useReplayer.js`)

- Eksekusi setiap step satu per satu dengan delay default **800ms** (configurable)
- **Highlight visual** elemen yang sedang dieksekusi: outline biru beranimasi
- **Scroll otomatis** ke elemen sebelum aksi
- Tampilkan **progress** di panel (step 3 of 7)
- Jika elemen tidak ditemukan: **pause dan tampilkan error** dengan selector yang gagal
- Setelah selesai: tampilkan toast "✅ Scenario selesai"

### 3. 💾 Scenario Manager (`useScenarios.js`)

- Simpan ke **localStorage** dengan key `dev_panel_scenarios`
- Setiap scenario punya: `{ id, name, description, steps[], createdAt }`
- Maksimal **20 scenario** tersimpan
- Bisa **rename**, **duplicate**, **hapus** scenario
- **Export ke JSON** (download file)

### 4. ⚡ Quick Actions (`QuickActions.vue`)

Buat shortcut dinamis berdasarkan hasil analisis project di langkah pertama:

- **Login cepat**: Isi form login dengan kredensial test (`admin@test.com` / `password`) dan submit
- **Navigasi cepat**: Dropdown berisi semua route yang ditemukan
- **Reset state**: Clear localStorage + redirect ke home
- Tambahkan quick action lain yang relevan berdasarkan halaman-halaman yang kamu temukan

### 5. 📋 Export (`ExportModal.vue`)

Generate script dari steps yang terekam:

**Playwright (TypeScript):**
```typescript
import { test, expect } from '@playwright/test';

test('scenario name', async ({ page }) => {
  // generated steps...
});
```

**Cypress:**
```javascript
describe('scenario name', () => {
  it('should work', () => {
    // generated steps...
  });
});
```

---

## Desain UI Panel

### Prinsip Desain

- **Sesuaikan warna** dengan design tokens project (baca dari tailwind config / CSS vars)
- Jika tidak ada design tokens yang jelas, gunakan: primary `#6366F1` (indigo), surface `#1E1E2E` (dark), text `#E2E8F0`
- Panel harus **tidak mengganggu** konten utama
- Default position: **bottom-right**, draggable
- Default state: **minimized** (hanya tampil toolbar kecil)
- Expanded: lebar `320px`, tinggi max `500px`, scrollable

### Layout Panel (Expanded)

```
┌─────────────────────────────────────┐
│ ⚙️ Dev Panel          [_] [×]       │  ← header, draggable
├─────────────────────────────────────┤
│ [🔴 REC] [⏹ STOP] [▶ PLAY] [💾]   │  ← toolbar utama
├─────────────────────────────────────┤
│ ⚡ Quick Actions                     │
│ [Login Admin] [→ /users] [→ /...] │  ← dynamic berdasarkan project
├─────────────────────────────────────┤
│ 📋 Steps (3)              [🗑 Clear] │
│ ┌─────────────────────────────────┐ │
│ │ 1. 🖱 Klik "Login"             │ │
│ │ 2. ✏️ Isi email → admin@...    │ │
│ │ 3. ✏️ Isi password → ••••••   │ │
│ └─────────────────────────────────┘ │
├─────────────────────────────────────┤
│ [💾 Save Scenario] [📤 Export]      │
└─────────────────────────────────────┘
```

### State Visual

| State | Indikator |
|-------|-----------|
| Idle | Tombol REC abu-abu |
| Recording | Tombol REC merah berkedip + badge counter di toolbar minimized |
| Replaying | Progress bar biru di bawah panel + highlight elemen |
| Error | Toast merah + step yang gagal di-highlight merah |

---

## Hal-Hal Penting

### Jangan Ganggu Fungsionalitas App

- Semua event listener harus menggunakan `{ passive: true }` jika memungkinkan
- Jangan `preventDefault()` pada aksi apapun
- Gunakan `stopPropagation()` hanya pada klik di dalam panel itu sendiri
- Panel harus punya `z-index: 99999`

### Kompatibilitas Router

- Deteksi apakah project menggunakan **Vue Router** atau **Inertia.js**
- Untuk Inertia: hook ke `router.on('navigate', ...)` dari `@inertiajs/vue3`
- Untuk Vue Router: hook ke `router.afterEach(...)`
- Untuk keduanya tidak ada (multi-page): hook ke `window.popstate`

### Data Sensitif

- **Mask password fields** di step list: tampilkan `••••••` bukan nilai asli
- **Jangan simpan** token/session ke localStorage scenarios

---

## Output yang Diharapkan

Setelah selesai, saya ingin:

1. ✅ Semua file di `resources/js/dev-panel/` sudah dibuat dan berfungsi
2. ✅ Panel muncul di halaman ketika `VITE_APP_ENV=local`
3. ✅ Bisa record aksi nyata di semua halaman yang ada
4. ✅ Replay berjalan dengan highlight visual
5. ✅ Quick actions sudah disesuaikan dengan route dan halaman di project ini
6. ✅ Warna panel sesuai dengan design system project
7. ✅ Export menghasilkan script Playwright yang valid

---

## Catatan Akhir untuk Claude Code

- Baca dulu semua file yang relevan **sebelum menulis kode**
- Jika menemukan pola/komponen UI yang sudah ada di project, **ikuti pola tersebut**
- Jika ada halaman dengan table + action buttons (edit/delete per row), pastikan recorder bisa **menangkap context row** (misal: "Klik Delete pada row User #5")
- Jika ada **multi-step form** atau **wizard**, pastikan navigation antar step ikut terekam
- Tambahkan `data-testid` attribute ke elemen-elemen kunci yang belum punya selector unik, agar replay lebih reliable — lakukan ini secara **minimal dan non-invasif**
