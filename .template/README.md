# .template/frontend/ — Materio Vuetify 3 Admin Template

Referensi pola UI dari Materio Vuetify 3 Admin Template.
**READ-ONLY — jangan modifikasi file apapun di folder ini.**

---

## Ringkasan Isi

### Layout System (`resources/js/layouts/`, `@layouts/`, `@core/`)
- `layouts/default.vue` + `layouts/blank.vue` — dua layout utama
- `layouts/components/DefaultLayoutWithVerticalNav.vue` — gabungan sidebar + navbar
- `layouts/components/VerticalNav.vue` — sidebar navigasi dengan nested groups
- `layouts/components/NavItems.vue` — daftar menu item
- `layouts/components/NavbarThemeSwitcher.vue` — theme toggle di navbar
- `layouts/components/UserProfile.vue` — user menu dropdown
- `layouts/components/Footer.vue` — footer component
- `@core/components/ThemeSwitcher.vue` — theme FAB global
- `@core/components/MoreBtn.vue` — 3-dot action menu

### Pages / Views (`resources/js/pages/`, `views/`)
- **Auth:** `login.vue`, `register.vue` — form auth dengan `auth-wrapper`
- **Dashboard:** `dashboard.vue` + 9 komponen analytics di `views/dashboard/`
- **Tables:** `tables.vue` + 5 demo table variants di `views/pages/tables/` dan `views/user-interface/tables/`
- **Forms:** `form-layouts.vue` + 5 form layout patterns di `views/pages/form-layouts/`
- **Cards:** `card-basic.vue` + 3 card demo components di `views/pages/cards/card-basic/`
- **Account Settings:** 3 tab pages (Account, Security, Notification)
- **Error:** error page `[...error].vue`
- **Typography:** headlines + text samples
- **Icons:** icon picker demo

### Plugins (`resources/js/plugins/`)
- **Vuetify:** `index.js` (setup), `theme.js` (eco/ecoDark), `defaults.js`, `icons.js` (Remix)
- **Router:** `index.js` + `routes.js` — struktur routing lengkap
- **Pinia:** store setup
- **Iconify:** Remix + Boxicons, custom build script
- **Webfontloader:** font loading

### Utils & Helpers
- `@core/utils/formatters.js` — currency, date, truncate
- `@core/utils/helpers.js` — debounce, deepClone
- `@core/utils/colorConverter.js` — hexToRgb, hexToRgba
- `@core/utils/plugins.js` — plugin registration helper
- `utils/paginationMeta.js` — pagination meta helper

### Styles (`resources/styles/`)
- SCSS base (@core variables, mixins, utilities, vertical-nav)
- Vuetify overrides untuk setiap komponen (button, card, table, dialog, dll)
- ApexCharts theme-aware styling
- Page-specific: auth pages, misc pages
- Placeholders untuk layout + vertical-nav

### Assets (`resources/images/`, `public/`)
- 15 avatar PNGs
- Brand logos (Google, GitHub, Slack, Stripe, dll)
- Page background images (auth, 404, misc)
- SVG checkbox/radio icons
- `public/favicon.ico`, `public/logo.png`, `public/loader.css`

---

## Yang Kurang (Hanya README, Tanpa Implementasi)

| Referensi di TEMPLATE.md | Status |
|---|---|
| `@core/components/AppTextField.vue` | Hanya README — tidak ada file .vue |
| `@core/components/AppSelect.vue` | Hanya README — tidak ada file .vue |
| `@core/components/AppCombobox.vue` | Hanya README — tidak ada file .vue |
| `@core/components/AppDateTimePicker.vue` | Hanya README — tidak ada file .vue |
| `components/dialogs/ConfirmDialog.vue` | Hanya README — tidak ada file .vue |
| `components/dialogs/FormDialog.vue` | Hanya README — tidak ada file .vue |
| `components/cards/StatCard.vue` | Tidak ada file sama sekali |
| `components/cards/KPICard.vue` | Tidak ada file sama sekali |
| `components/cards/SummaryCard.vue` | Tidak ada file sama sekali |
| `components/tables/` | Hanya README — tidak ada file .vue |
| `pages/user-management/index.vue` | Hanya README — tidak ada file .vue |
| `pages/user-management/[id].vue` | Hanya README — tidak ada file .vue |
| `pages/charts/RadarChart.vue` | Hanya README — tidak ada file .vue |
| `pages/charts/BarChart.vue` | Hanya README — tidak ada file .vue |
| `pages/charts/DonutChart.vue` | Hanya README — tidak ada file .vue |
| `pages/forms/` (full-page form) | Tidak ada folder — form patterns hanya ada di `views/pages/form-layouts/` |

---

## Catatan Penting
- **Icons:** Materio template ini menggunakan Remix Icon (`ri-*`), bukan MDI
- **ESLint:** `semi: never`, 2-space indent, trailing commas on multiline
- **Directional classes:** Vuetify logical (`ps-`, `pe-`, `ms-`, `me-`), bukan `pl-`, `pr-`
- **Auto-import:** Vue APIs (ref, computed, onMounted) + Vuetify components auto-imported
- **useRouter/useRoute:** Wajib import eksplisit dari `vue-router`
