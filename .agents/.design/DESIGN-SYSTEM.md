# DESIGN-SYSTEM.md — Ecogreen Oleochemicals
## Visual Design Reference for AI Agent
> Baca file ini saat membangun komponen frontend apapun.
> Semua keputusan visual mengacu ke token di sini — bukan ke default Vuetify.

---

## Brand Identity

| Atribut | Value |
|---------|-------|
| Company | PT Ecogreen Oleochemicals |
| Tagline | Natural. Precise. Trusted. |
| Brand Primary | `#42B240` — vivid leaf green (warna logo utama) |
| Tone | Professional, authoritative, sustainability-conscious |
| No emoji | Murni profesional — emoji tidak digunakan |

### Assets Location
```
.design/assets/
├── logo.svg               ← logo utama (dark bg)
├── logo-white.svg         ← logo reversed (light bg)
├── logo-horizontal.jpg    ← logo + wordmark horizontal
├── logo-stacked.jpg       ← logo + wordmark vertikal
├── logo-symbol.svg        ← icon only (favicon)
└── favicon.ico
```

---

## Color System

### Brand Palette

| Token | Hex | Penggunaan |
|-------|-----|------------|
| `green-500` | `#42B240` | **★ Brand Primary** — logo, CTA utama, active states |
| `green-700` | `#2A8030` | Button hover, link color |
| `green-800` | `#1E5C20` | Header text, nav active |
| `green-900` | `#1C3220` | Wordmark, footer background |
| `green-100` | `#E2F6E2` | Background muted, tag bg |
| `green-50`  | `#F3FBF3` | Hover bg, subtle tint |
| `amber-500` | `#C8873A` | Accent — callout, highlight (rare) |
| `amber-100` | `#FDF3E3` | Accent background |

### Neutral Palette

| Token | Hex | Penggunaan |
|-------|-----|------------|
| `neutral-900` | `#1C2420` | Body text primary |
| `neutral-600` | `#5A6860` | Text secondary, captions |
| `neutral-400` | `#97A49C` | Muted, placeholder |
| `neutral-200` | `#D4DDD8` | Border default |
| `neutral-100` | `#EBF0EC` | Border subtle |
| `neutral-50`  | `#F7F4EF` | Page background (warm off-white) |

### Semantic Colors

| Tujuan | Hex |
|--------|-----|
| Success | `#2E7D32` |
| Warning | `#E65100` |
| Error | `#C62828` |
| Info | `#01579B` |

---

## Vuetify Theme Configuration

Terapkan token Ecogreen ke Vuetify theme di `plugins/vuetify/index.js`:

```js
// frontend/resources/js/plugins/vuetify/index.js
import { createVuetify } from 'vuetify'

const ecoTheme = {
  dark: false,
  colors: {
    // Brand
    primary:          '#42B240',  // brand green
    'primary-darken-1': '#2A8030',
    'primary-darken-2': '#1E5C20',
    'primary-lighten-1': '#65C463',
    'primary-lighten-2': '#E2F6E2',

    secondary:        '#2A8030',  // heading green
    accent:           '#C8873A',  // amber (rare)

    // Semantic
    error:            '#C62828',
    warning:          '#E65100',
    info:             '#01579B',
    success:          '#2E7D32',

    // Surface
    background:       '#F7F4EF',  // warm off-white
    surface:          '#FFFFFF',
    'on-primary':     '#FFFFFF',
    'on-secondary':   '#FFFFFF',
    'on-background':  '#1C2420',
    'on-surface':     '#1C2420',

    // Brand extras (accessible via theme.current.colors)
    'green-900':      '#1C3220',
    'green-800':      '#1E5C20',
    'neutral-50':     '#F7F4EF',
    'neutral-200':    '#D4DDD8',
    'neutral-600':    '#5A6860',
    'amber-500':      '#C8873A',
  },
}

const ecoDarkTheme = {
  dark: true,
  colors: {
    primary:          '#65C463',  // lighter green legible on dark
    'primary-darken-1': '#42B240',
    secondary:        '#90D58E',
    accent:           '#E8B878',
    error:            '#EF9A9A',
    warning:          '#FFCC80',
    info:             '#81D4FA',
    success:          '#A5D6A7',
    background:       '#0F1410',
    surface:          '#1C2420',
    'on-primary':     '#0D3B22',
    'on-background':  '#E2F6E2',
    'on-surface':     '#D4DDD8',
  },
}

export default createVuetify({
  theme: {
    defaultTheme: 'eco',
    themes: {
      eco:     ecoTheme,
      ecoDark: ecoDarkTheme,
    },
  },
  defaults: {
    // Global defaults — semua component ikuti design system
    VBtn: {
      rounded: 'md',        // radius-md = 4px
      elevation: 0,
    },
    VCard: {
      rounded: 'lg',        // radius-lg = 6px
      elevation: 1,
    },
    VTextField: {
      rounded: 'md',
      variant: 'outlined',
      density: 'comfortable',
    },
    VSelect: {
      rounded: 'md',
      variant: 'outlined',
      density: 'comfortable',
    },
    VChip: {
      rounded: 'pill',      // radius-pill = 100px
    },
  },
})
```

---

## Typography

### Font Stack

| Role | Font | Weights | Penggunaan |
|------|------|---------|------------|
| Display / Heading | Montserrat | 400–800 | Semua heading (h1–h6), nav, button, label |
| Body | Source Sans 3 | 300–700 | Paragraph, deskripsi, form text |
| Mono / Technical | JetBrains Mono | 400–500 | Kode, formula kimia, spec table |

```css
/* Google Fonts import — tambahkan ke style.css atau main.css */
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Source+Sans+3:ital,wght@0,300;0,400;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500&display=swap');
```

### Type Scale

| Class | Size | Weight | Penggunaan |
|-------|------|--------|------------|
| `.display-1` | 56px | 800 | Hero headline |
| `.display-2` | 44px | 700 | Page title |
| `h3` | 36px | 600 | Section heading |
| `h4` | 30px | 600 | Sub-section |
| `h5` | 24px | 500 | Card title |
| `h6` | 20px | 600 + uppercase | Label/overline |
| body | 16px | 400 | Default body |
| `.body-lg` | 18px | 400 | Lead paragraph |
| `.body-sm` | 14px | 400 | Secondary text |
| `.caption` | 12px | 400 | Metadata |
| `.label` | 12px | 600 + uppercase | Form label, tag |

---

## Spacing & Layout

```
Base unit: 8px

Spacing scale:
  4px   (space-1)  — icon gap, micro padding
  8px   (space-2)  — tight spacing
  12px  (space-3)  — form field gap
  16px  (space-4)  — default component gap
  24px  (space-6)  — card padding
  32px  (space-8)  — section gap
  48px  (space-12) — section padding mobile
  64px  (space-16) — medium section padding
  80px  (space-20) — section padding desktop
  96px  (space-24) — hero padding

Container max-width: 1200px
Grid: 12-column (Vuetify v-container + v-row + v-col)
```

---

## Components

### Buttons

```vue
<!-- Primary — CTA utama -->
<VBtn color="primary" rounded="md" elevation="0">
  Download TDS
</VBtn>

<!-- Secondary — outline -->
<VBtn color="primary" variant="outlined" rounded="md">
  Contact Sales
</VBtn>

<!-- Ghost — subtle -->
<VBtn color="neutral-600" variant="text" rounded="md">
  Learn More
</VBtn>

<!-- Dengan icon — prepend-icon pakai Remix Icon -->
<VBtn color="primary" rounded="md" prepend-icon="ri-file-pdf-line">
  Download TDS
</VBtn>

<!-- Sizes -->
<VBtn size="small">Small</VBtn>
<VBtn size="large">Large</VBtn>
```

### Cards

```vue
<!-- Product card standar -->
<VCard rounded="lg" elevation="1" class="eco-card">
  <div class="eco-card-img bg-green-100 d-flex align-center justify-center">
    <VIcon icon="ri-flask-line" size="40" color="primary" />
  </div>
  <VCardText>
    <p class="text-overline text-medium-emphasis">Saturated Fatty Alcohol</p>
    <p class="text-h6 font-weight-bold">Ecorol® C12–C14</p>
    <p class="text-body-2 text-medium-emphasis">
      Widely used in surfactants, personal care and detergents.
    </p>
    <div class="d-flex gap-1 flex-wrap mt-2">
      <VChip size="x-small" color="primary" variant="tonal">Personal Care</VChip>
      <VChip size="x-small" color="primary" variant="tonal">Cosmetics</VChip>
    </div>
  </VCardText>
  <VDivider />
  <VCardActions>
    <VBtn size="small" variant="text" color="primary" prepend-icon="ri-file-pdf-line">
      TDS
    </VBtn>
    <VSpacer />
    <VBtn size="small" variant="text" color="primary" append-icon="ri-arrow-right-line">
      View
    </VBtn>
  </VCardActions>
</VCard>

<style>
.eco-card { border: 1px solid var(--v-theme-neutral-200) !important; }
.eco-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md) !important; }
.eco-card-img { height: 120px; }
</style>
```

### Tags / Chips

```vue
<!-- Application tag -->
<VChip color="primary" variant="tonal" rounded="pill" size="small">
  Personal Care
</VChip>

<!-- Status badge -->
<VChip color="success" variant="tonal" size="small" prepend-icon="ri-checkbox-circle-line">
  Active
</VChip>

<!-- Certification badge -->
<VChip variant="outlined" color="primary" size="small">
  ISO 9001
</VChip>
```

### Forms

```vue
<!-- Form field standar -->
<VTextField
  v-model="value"
  label="Product Name"
  variant="outlined"
  rounded="md"
  density="comfortable"
  color="primary"
/>

<!-- Select -->
<VSelect
  v-model="selected"
  :items="items"
  label="Application"
  variant="outlined"
  rounded="md"
  density="comfortable"
  color="primary"
/>
```

---

## Design Rules

### Yang WAJIB
- Gunakan `#42B240` (primary green) sebagai warna aksi utama
- Font heading: `Montserrat`, font body: `Source Sans 3`
- Cards: `rounded="lg"` (6px), border 1px `#D4DDD8`, shadow subtle
- Buttons: `rounded="md"` (4px), tidak ada rounded full kecuali chip
- Gunakan `F7F4EF` sebagai background halaman (bukan putih murni)
- Icons: Remix Icon (`ri-*`) — konsisten dengan konvensi project
- Spacing berbasis 8px grid

### Yang DILARANG
- ❌ Jangan hardcode warna — selalu gunakan CSS variable atau theme token
- ❌ Jangan gunakan gradients kecuali untuk hero/banner (subtle)
- ❌ Jangan gunakan animasi bounce atau spring physics
- ❌ Jangan gunakan emoji sebagai icon
- ❌ Jangan gunakan font selain Montserrat/Source Sans 3/JetBrains Mono
- ❌ Jangan override rounded-full untuk buttons (hanya chip)
- ❌ Jangan gunakan heavy drop shadow

### Hover Pattern
```css
/* Cards */
transition: box-shadow 200ms ease, transform 200ms ease;
:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }

/* Buttons */
transition: background 200ms ease;
/* Vuetify handles ini otomatis dengan color="primary" */

/* Nav links */
transition: color 150ms ease;
```

---

## Referensi File di `.design/`

```
.design/
├── README.md                     ← company overview + visual foundations
├── SKILL.md                      ← agent skill descriptor (auto-load)
├── DESIGN-SYSTEM.md              ← file ini (Vuetify integration guide)
├── colors_and_type.css           ← CSS design tokens (import ke frontend)
├── assets/                       ← logo files (SVG + JPG + ICO)
└── preview/                      ← HTML preview tiap komponen
    ├── components-buttons.html
    ├── components-cards.html
    ├── components-forms.html
    ├── components-badges.html
    ├── components-nav.html
    ├── colors-primary.html
    ├── type-display.html
    └── ...
```

### Cara Agent Menggunakan `.design/`

```
Sebelum membangun komponen frontend apapun:
1. Baca DESIGN-SYSTEM.md (file ini) — untuk tokens dan Vuetify config
2. Lihat preview/ yang relevan jika butuh referensi visual komponen
3. Gunakan colors_and_type.css sebagai import di frontend/style.css
4. Assets logo dari .design/assets/ — copy ke frontend/public/ saat setup

Jangan:
- Hardcode warna hex tanpa referensi ke token
- Buat komponen yang bertentangan dengan design rules di atas
```

---

## SPRINT-01 Integration Checklist

Saat setup project baru, lakukan ini di Sprint 01:

- [ ] Copy `colors_and_type.css` ke `frontend/resources/js/styles/design-tokens.css`
- [ ] Import di `main.js` atau `App.vue`: `import './styles/design-tokens.css'`
- [ ] Copy logo assets ke `frontend/public/` (atau `frontend/resources/assets/`)
- [ ] Apply Vuetify theme config dari section "Vuetify Theme Configuration" di atas
- [ ] Set Google Fonts import di `style.css`
- [ ] Verify: `npm run dev` → warna primary #42B240, font Montserrat di heading

---

*Ecogreen Oleochemicals Design System — Agent Reference v1.0*
*Source: Web research April 2026. Refine dengan asset resmi jika tersedia.*
