# AI-Assisted Development — Agent & Sprint Workflow

> Source: Kompilasi Modul Tech Stack — Laravel 12 + Vue.js (v20260525)

> Pages: 191–228

---

## Tech Stack

| Layer       | Teknologi                              |
|-------------|----------------------------------------|
| AI Tool     | Claude Code / Open Code                |
| Runtime     | Node.js 18+                            |
| Backend     | Laravel 12                             |
| Auth        | Sanctum + LDAP                         |
| Frontend    | Vue.js 3                               |
| Build       | Vite + Vitest                          |
| State       | Pinia                                  |
| HTTP Client | Axios                                  |
| Styling     | Vuetify 3.7                            |
| Database    | Postgres SQL                           |
| Testing     | PHPUnit + Vitest                       |
| Design      | Ecogreen Design System                 |
| Skills      | obra/superpowers                       |

> **Arsitektur: Decoupled** — backend (port 8000) dan frontend (port 5173) berjalan sebagai dua project terpisah, berkomunikasi via HTTP REST API.

---

## AI-Assisted Project Development

> End-to-End: Convention · Sprint · Skills · Design System
>
> Diperuntukkan bagi: **Full-Stack Developer (FSD)** dan **Software Development Staff (SDS)**

---

## Daftar Isi

| # | Topik | Deskripsi |
|---|-------|-----------|
| 1 | Apa Itu Sistem Ini? | Konsep 3 lapisan — Convention, Blueprint, Skills |
| 2 | Setup Project | Instalasi, struktur folder, dan shell wrapper `dev-agent.sh` |
| 3 | Layer 1 — Convention Files | `CLAUDE.md` · `ARCHITECTURE.md` · `.template/` sebagai UI reference |
| 4 | Layer 2 — Sprint Blueprint | `SPRINT.md` lengkap · features · schema · acceptance criteria |
| 5 | Layer 3 — Process Skills | 14 skills obra/superpowers · kapan diaktifkan · cara menulis skill sendiri |
| 6 | Menjalankan Agent | Claude Code · shell wrapper · opsi CI/CD · validasi setup |
| 7 | Eksekusi Sprint End-to-End | Lifecycle 4 fase · 10 langkah implementasi · prompt per langkah |
| 8 | Referensi Prompt — Copy & Use | 15+ prompt siap pakai untuk setiap aktivitas sprint |
| 9 | Mengelola Banyak Sprint | Versioning · handoff · carry-over tasks · sprint closing |
| 10 | Codebase yang Sudah Ada | 6 fase codebase archaeology · draft `CLAUDE.md` dari kode lama |
| 11 | Studi Kasus: AnD Human Capital | Implementasi nyata 3 lapisan — CLAUDE.md, ARCHITECTURE.md, sprint roadmap |
| 12 | Troubleshooting & Anti-Patterns | 7 masalah umum beserta solusinya |
| 13 | Pro Tips & Checklist Pre-Sprint | Best practices dan checklist sebelum sprint dimulai |

---

## 1. Apa Itu Sistem Ini?

Masalah paling umum saat menggunakan AI untuk coding: hasilnya tidak konsisten. Sprint pertama bagus, sprint ketiga agent mulai melanggar naming convention, menulis validasi di controller, dan melupakan pola yang sudah disepakati. Sistem ini memecahkan masalah itu dengan memberi agent tiga lapisan konteks permanen.

### Tiga Lapisan Konteks

| Layer   | Nama        | File                                  | Menjawab Pertanyaan                              | Dibaca Kapan                              |
|---------|-------------|---------------------------------------|--------------------------------------------------|-------------------------------------------|
| Layer 1 | Convention  | `CLAUDE.md` · `ARCHITECTURE.md`       | Bagaimana menulis kode? Bagaimana sistem dibangun? | Setiap sesi, sebelum koding               |
| Layer 2 | Blueprint   | `SPRINT-XX.md` · `app-blueprint.md`   | Apa yang dibangun sprint ini? Detail domain?     | Per sprint / saat butuh detail domain     |
| Layer 3 | Skills      | `.skills/` SKILL.md files             | Bagaimana mendekati task ini? Proses apa?        | Triggered per jenis task (TDD, debug, dll) |

### Bagaimana Ketiganya Bekerja Bersama

Sebelum mengerjakan task apapun, agent membaca Layer 1 (konvensi dan arsitektur), mengidentifikasi Layer 2 (sprint apa yang aktif), lalu memilih Layer 3 (skill proses yang sesuai dengan jenis task). Hasilnya: agent berperilaku seperti developer yang sudah onboarding penuh, bukan asisten yang mengimprovisasi.

**Contoh alur — Task: "Implementasikan Feature 1 dari SPRINT-03.md"**

```bash
1. Agent baca CLAUDE.md        → tahu: naming, restrictions, pattern C→S→R
2. Agent baca ARCHITECTURE.md  → tahu: domain hierarchy, auth flow, API rules
3. Agent baca SPRINT-03.md     → schema, endpoint, acceptance criteria
4. Agent baca SKILL.md (TDD)   → tahu: tulis test dulu, baru implementasi
5. Agent koding                → hasilnya konsisten dan compliant sejak baris pertama
```

> **Priority stack:** `CLAUDE.md` selalu menang atas skill apapun. Skills menang atas default behavior agent. Rules project tidak bisa di-override oleh metodologi skill.

---

### Struktur Folder Lengkap — Standard Ecogreen A&D

Arsitektur decoupled: backend dan frontend berada di folder terpisah, masing-masing modular per business process. Backend menggunakan Laravel modular (nwidart), frontend menggunakan Vue modular.

```bash
project-root/
├── backend/                        ← Laravel 12 API (port 8000)
│   ├── Modules/                    ← Business modules (nwidart/laravel-modules)
│   │   ├── Auth/
│   │   │   ├── app/
│   │   │   │   ├── Http/
│   │   │   │   │   ├── Controllers/    ← thin, hanya orchestrate
│   │   │   │   │   ├── Requests/       ← SEMUA validasi di sini
│   │   │   │   │   └── Resources/      ← API response transformer
│   │   │   │   ├── Services/           ← business logic
│   │   │   │   ├── Repositories/
│   │   │   │   │   ├── AuthRepositoryInterface.php
│   │   │   │   │   └── EloquentAuthRepository.php
│   │   │   │   └── Models/
│   │   │   └── routes/
│   │   │       ├── api.php             ← pakai auth:sanctum
│   │   │       └── web.php             ← dikosongkan (SPA)
│   │   ├── Providers/
│   │   │   └── AuthServiceProvider.php ← binding repo
│   │   ├── Organization/               ← [struktur sama]
│   │   ├── Employee/                   ← [struktur sama]
│   │   └── [NamaModul]/                ← tambah modul baru di sini
│   ├── app/
│   │   └── Models/                     ← shared models (jika ada)
│   ├── database/
│   │   ├── migrations/                 ← SEMUA migration di sini (bukan di Modules/)
│   │   ├── seeders/
│   │   └── factories/
│   ├── config/
│   └── routes/api.php                  ← entry point, include module routes
│
├── frontend/                       ← Vue 3 + Vite SPA (port 5173)
│   └── resources/js/
│       ├── modules/                ← Business modules (mirror backend)
│       │   ├── auth/
│       │   │   ├── services/
│       │   │   │   └── authService.js      ← semua axios call
│       │   │   ├── stores/
│       │   │   │   └── authStore.js        ← Pinia store
│       │   │   ├── views/
│       │   │   │   └── LoginView.vue       ← halaman utama
│       │   │   ├── components/             ← komponen lokal modul
│       │   │   └── routes.js               ← route definitions
│       │   ├── organization/           ← [struktur sama]
│       │   ├── employee/               ← [struktur sama]
│       │   └── [namaModul]/            ← tambah modul baru di sini
│       ├── plugins/
│       │   ├── axios.js                ← axios instance + interceptor
│       │   └── router/
│       │       └── routes.js           ← import & spread semua module routes
│       ├── stores/
│       │   ├── authStore.js
│       │   └── toastStore.js
│       └── layouts/
│           └── components/
│               └── NavItems.vue        ← tambah menu modul baru di sini
│
├── .template/                      ← UI Template Reference (READ-ONLY)
│   ├── README.md                   ← panduan cara agent mereferensi template
│   └── frontend/resources/js/
│       ├── @core/                  ← core Materio components & utils
│       │   ├── components/         ← AppTextField, AppSelect, AppCombobox, dll
│       │   └── utils/              ← helper functions, formatters
│       └── components/             ← shared template components
│           ├── dialogs/            ← confirm dialog, form dialog patterns
│           ├── cards/              ← stat card, KPI card patterns
│           ├── tables/             ← data table patterns
│           └── layouts/            ← layout reference (default, blank, nav)
│
├── .claude/
│   ├── CLAUDE.md                   ← Layer 1a: konvensi harian
│   └── settings.local.json         ← Claude Code permissions (NOT in git)
├── AGENTS.md                       ← alias ke CLAUDE.md
├── composer.json                   ← root: wikimedia/composer-merge-plugin
│
├── .docs/
│   ├── ARCHITECTURE.md             ← Layer 1b: arsitektur & patterns
│   ├── TEMPLATE-ADAPTATION.md     ← bridge: cara adapt .template/ ke design system
│   ├── app-blueprint.md            ← Layer 1c: domain model detail
│   └── sprints/
│       ├── sprint-roadmap.md       ← dependency graph antar sprint
│       ├── archive/                ← sprint yang sudah selesai
│       ├── sprint-01.md            ← Layer 2: sprint selesai
│       └── sprint-02.md            ← Layer 2: sprint aktif
│
├── .skills/                        ← Layer 3: process methodology
│   ├── test-driven-development/SKILL.md
│   ├── systematic-debugging/SKILL.md
│   ├── writing-plans/SKILL.md
│   ├── verification-before-completion/SKILL.md
│   └── [custom-skill]/SKILL.md
│
├── .design/                        ← Design System (default: Ecogreen)
│   ├── README.md                   ← brand overview + visual foundations
│   ├── SKILL.md                    ← agent skill descriptor
│   ├── DESIGN-SYSTEM.md            ← Vuetify theme mapping + component guide
│   ├── colors_and_type.css         ← CSS design tokens
│   ├── assets/                     ← logo.svg, logo-white.svg, favicon.ico
│   └── preview/                    ← HTML preview per komponen
│
└── dev-agent.sh                    ← shell wrapper (inject 3 layer otomatis)
```

## 2. Setup Project

Setup dilakukan sekali di awal project. Setelah ini, setiap sprint tinggal update Layer 2 (SPRINT.md) dan mulai koding.
### Install Claude Code

```bash
npm install -g @anthropic-ai/claude-code    # requires Node.js 18+
```
```bash
claude --version
claude login                                   # pertama kali
```

> ✓ Claude Code secara otomatis membaca .claude/CLAUDE.md dari root project di setiap session. Tidak perlu inject manual. AGENTS.md di root adalah alias — beberapa AI tool (Codex, Copilot, Gemini) membaca file ini sebagai primary context. Isi sama dengan CLAUDE.md.

### Buat `.claude/settings.local.json` — Wajib Sebelum Apapun

File ini memberikan permission Claude Code untuk menjalankan commands tanpa approval manual. Buat di root project directory (folder induk dari `backend/` dan `frontend/`). Tidak di-commit ke git — masukkan ke `.gitignore`.

```json
// Lokasi: D:\Apps\[project-name]\.claude\settings.local.json
{
  "permissions": {
    "allow": [
      "Bash(php *)",
      "Bash(composer *)",
      "Bash(npm *)",
      "Bash(node *)",
      "Bash(ls *)",
      "PowerShell(php *)",
      "PowerShell(composer *)",
      "PowerShell(npm *)",
      "PowerShell(node *)",
      "PowerShell(netstat *)",
      "PowerShell(New-Item *)",
      "PowerShell(Get-ChildItem *)",
      "PowerShell(Test-Path *)",
      "PowerShell(Write-Host *)",
      "PowerShell(echo *)",
      "PowerShell(cd *)"
    ]
  }
}
```

**Buat otomatis via PowerShell:**

```powershell
$dir = "D:\Apps\[project-name]\.claude"
New-Item -ItemType Directory -Force $dir | Out-Null
# ... paste isi JSON ke file
```

Atau minta Claude langsung:

```bash
# "Buat .claude/settings.local.json dengan permission untuk
#  composer, php, npm, netstat, dan PowerShell commands."
```

> ⚠️ Claude Code bisa otomatis menambah entri ke file ini saat menjalankan command baru. Periksa berkala dan kembalikan ke template di atas jika ada entri yang tidak diinginkan.

### Buat Struktur Folder

```bash
# Buat struktur folder project
mkdir -p backend frontend
mkdir -p .claude .docs/sprints .docs/archive .skills
# Layer 1 — convention files

touch .claude/CLAUDE.md
touch .docs/ARCHITECTURE.md
touch .docs/sprints/sprint-roadmap.md
touch .docs/sprints/sprint-01.md
```
# .template/ — UI template reference (copy dari Materio atau template pilihan)
```bash
mkdir -p .template/frontend/resources/js/@core/components
mkdir -p .template/frontend/resources/js/components/{dialogs,cards,tables}
mkdir -p .template/frontend/resources/js/{layouts,pages,views}
```
# Salin file template Materio/Vuetify ke sini — JANGAN modifikasi isinya
# .design/ — Ecogreen Design System (copy dari repo design system)
```bash
mkdir -p .design/{assets,preview,ui_kits}
# Copy file dari Ecogreen_Oleochemicals_Design_System/:
# cp design-system/README.md .design/
# cp design-system/SKILL.md .design/
# cp design-system/DESIGN-SYSTEM.md .design/
# cp design-system/colors_and_type.css .design/
# cp -r design-system/assets/ .design/
# cp -r design-system/preview/ .design/
# Layer 3 — skills
mkdir -p .skills/{test-driven-development,systematic-debugging}
mkdir -p .skills/{writing-plans,verification-before-completion}
# Shell wrapper
touch dev-agent.sh && chmod +x dev-agent.sh
# Setup backend Laravel
```
cd backend && composer create-project laravel/laravel . && cd ..
```bash
composer require nwidart/laravel-modules spatie/laravel-permission
# Setup frontend Vue + Vite
```
cd frontend && npm create vite@latest . -- --template vue && cd ..
```bash
npm install vuetify pinia axios vue-router
```

### Shell Wrapper: `dev-agent.sh`

Untuk AI CLI selain Claude Code, gunakan wrapper ini agar Layer 1 dan Layer 2 selalu disertakan otomatis:
```bash
#!/bin/bash
CLAUDE_MD=".claude/CLAUDE.md"
ARCH_MD=".docs/ARCHITECTURE.md"
SPRINT_NUM="${SPRINT:-1}"
SPRINT_MD=".docs/sprints/sprint-$(printf '%02d' $SPRINT_NUM).md"
TASK="${1:-}"
[[ -z "$TASK" ]] && { echo "Usage: ./dev-agent.sh 'Task'"; exit 1; }
LAYER1=$(cat "$CLAUDE_MD" 2>/dev/null)
LAYER1_ARCH=$(cat "$ARCH_MD" 2>/dev/null)
LAYER2=$(cat "$SPRINT_MD" 2>/dev/null || echo "No sprint file.") claude chat <<EOF
=== LAYER 1: CONVENTION (CLAUDE.md) ===
$LAYER1

=== LAYER 1: ARCHITECTURE (ARCHITECTURE.md) ===
$LAYER1_ARCH
=== LAYER 2: SPRINT BLUEPRINT ($SPRINT_MD) ===
$LAYER2
=== TASK ===
$TASK
Ikuti SEMUA rules di CLAUDE.md. Hanya bangun yang ada di SPRINT.md.

Output kode lengkap dengan path. List semua file yang dibuat/dimodifikasi.
EOF
# Usage:
# ./dev-agent.sh "Implement Feature 1"
# SPRINT=3 ./dev-agent.sh "Build mentor dashboard API"

### Validasi Setup

```
```bash
./dev-agent.sh "Baca CLAUDE.md dan SPRINT-01.md.
Rangkum: stack, 3 aturan terpenting, dan semua fitur sprint ini.
Jangan tulis kode dulu."
# → Jika agent menjawab akurat, setup benar.

## 3. Layer 1 — Convention Files

Layer 1 adalah memori permanen agent. Dibaca setiap session, berlaku di semua sprint. Layer ini terdiri dari satu atau beberapa file tergantung ukuran project. Prinsipnya: satu file, satu tanggung jawab.

### Kapan Tetap 1 File, Kapan Dipisah?

| Kondisi Project | Struktur yang Direkomendasikan |
|-----------------|-------------------------------|
| Sprint 0–1, tim 1–2 developer, `CLAUDE.md` < 150 baris | Cukup 1 file: `.claude/CLAUDE.md` berisi semua |
| Sprint 2+, modul mulai banyak, `CLAUDE.md` > 150 baris | 2 file: `CLAUDE.md` (konvensi harian) + `.docs/ARCHITECTURE.md` (arsitektur) |
| Sprint 5+, domain kompleks, ada blueprint detail | 3+ file: + `app-blueprint.md` + `sprint-roadmap.md` (lihat Studi Kasus §11) |
### Konten yang Masuk ke Masing-Masing File

| Konten | `CLAUDE.md` | `ARCHITECTURE.md` |
|--------|------------|-------------------|
| Stack ringkas (1 tabel) | ✅ | — |
| Stack detail dengan versi & implementasi | — | ✅ |
| Dev commands (serve, migrate, lint) | ✅ | — |
| Urutan baca file referensi | ✅ | — |
| Naming conventions (tabel) | ✅ | — |
| Restrictions & forbidden patterns | ✅ | — |
| Contoh anti-pattern dengan kode | ✅ | — |
| ESLint / Pint rules yang sering dilanggar | ✅ | — |
| Field naming traps | ✅ | — |
| System architecture diagram | — | ✅ |
| Domain hierarchy / entity relationships | — | ✅ |
| Architecture patterns detail (C→S→R) | — | ✅ |
| Auth & authorization flow | — | ✅ |
| API design rules & route patterns | — | ✅ |
| Frontend folder structure detail | — | ✅ |
| Cara tambah modul baru (step-by-step) | — | ✅ |
| Database design principles | — | ✅ |
| Testing strategy & tools | ringkas | detail |
| Sprint roadmap & dependency | — | ✅ |
### CLAUDE.md — Template Lengkap

```
# CLAUDE.md — Project Convention Reference
# Dibaca agent setiap sesi. Last updated: YYYY-MM-DD
#### Stack (ringkas)
| Layer    | Teknologi                                           |
|----------|-----------------------------------------------------|
| Backend  | Laravel 12, PHP 8.3 — folder: backend/              |
| Frontend | Vue 3 + Vite + Vuetify 3.7 — folder: frontend/      |
| Database | MySQL port 3308                                     |
| Auth     | Sanctum (Bearer token di localStorage)              |
| Modules  | Backend: nwidart/laravel-modules, Frontend: modular |
#### Dev Commands
| Task          | Folder     | Command                              |
|---------------|------------|--------------------------------------|
| Backend dev   | backend/   | php artisan serve (port 8000)        |
| Frontend dev  | frontend/  | npm run dev (port 5173)              |
| Reset DB      | backend/   | php artisan migrate:fresh --seed     |
| New BE module | backend/   | php artisan module:make [NamaModul]  |
| BE lint       | backend/   | ./vendor/bin/pint                    |
| FE lint       | frontend/  | npx eslint --fix [file]              |
| Routes list   | backend/   | php artisan route:list --path=api    |
#### Urutan Baca File (wajib tiap sprint baru)
1. File ini (CLAUDE.md)
2. .docs/ARCHITECTURE.md — arsitektur, patterns, domain
3. .design/DESIGN-SYSTEM.md — color tokens, Vuetify theme, component rules
4. .docs/sprints/sprint-roadmap.md — dependency antar sprint
5. .docs/sprints/sprint-XX.md — detail sprint aktif
6. `.skills/[skill].md` — pilih skill sesuai jenis task (lihat section Skills di CLAUDE.md)
#### Read-Only — JANGAN PERNAH DIMODIFIKASI
- .template/  — UI template reference (Materio/Vuetify). READ-ONLY.
Agent BOLEH lihat dan adaptasi pola dari sini ke modules/.

JANGAN copy-paste mentah. JANGAN import langsung dari .template/.
- .design/    — Design system reference (Ecogreen). READ-ONLY.
Baca DESIGN-SYSTEM.md untuk tokens dan Vuetify config.

Gunakan colors_and_type.css dengan import di style.css.

JANGAN modifikasi. JANGAN import komponen langsung dari sini.
- .claude/settings.local.json — dikelola Claude Code. Jangan timpa permission.
#### Cara Agent Menggunakan .template/ (wajib untuk semua Vue component baru)
Sebelum membangun Vue component, selalu cari referensi di .template/ terlebih dulu:
| Jenis Komponen                  | Referensi di .template/frontend/resources/js/ |
|---------------------------------|-----------------------------------------------|
| Halaman CRUD (list + search)    | pages/user-management/                        |
| Form create / edit              | pages/forms/                                  |
| Data table + filter + pagination| components/tables/                            |
| Dialog (confirm / form dialog)  | components/dialogs/                           |
| Stat card / KPI card            | components/cards/                             |
| Chart (radar / bar / donut)     | pages/charts/                                 |
| Layout & navigation sidebar     | layouts/                                      |
| Core input wrapper (VTextField) | @core/components/                             |

Alur wajib membangun komponen baru:

- 1. Baca referensi .template/ yang paling sesuai dengan komponen target
- 2. Pahami: struktur template, Vuetify props, event pattern yang dipakai
- 3. Adaptasi ke module konteks (nama, props, data dari store Pinia)
- 4. Tetap ikuti semua frontend rules di CLAUDE.md (ps-/pe-, PascalCase, dll)
#### Decoupled Architecture — Aturan Utama
- Backend: API-only (SPA) — semua routes/web.php di tiap modul DIKOSONGKAN
- Frontend: SPA — semua API call via axios instance di plugins/axios.js
- Tidak ada render view dari Laravel — semua UI di frontend/
- CORS sudah dikonfigurasi; backend hanya mengembalikan JSON
#### Modular Architecture — Lokasi File
# Backend: Modules/[Nama]/app/
Http/Controllers/   Http/Requests/   Http/Resources/
Services/           Repositories/    Models/
Providers/          routes/api.php   routes/web.php (kosong)
# Migration: backend/database/migrations/ ← SELALU DI SINI (bukan di Modules/)
# Frontend: resources/js/modules/[nama]/
Services/[nama]Service.js    stores/[nama]Store.js views/[Nama]IndexView.vue    components/ routes.js → import di plugins/router/routes.js
# Global: stores/ | plugins/ | layouts/components/NavItems.vue
# Reference: .template/ (read-only) | .design/ (read-only)
#### Naming Conventions
| Artifact              | Convention          | Contoh                        |
|-----------------------|---------------------|-------------------------------|
| BE Module folder      | PascalCase          | Organization, JobCatalog      |
| PHP Controller        | PascalCase+suffix   | ApprenticeController          |
| PHP Model             | Singular PascalCase | Apprentice                    |
| PHP Service           | PascalCase+suffix   | ApprenticeService             |
| PHP Repository Iface  | PascalCase+suffix   | ApprenticeRepositoryInterface |
| PHP Repository Impl   | PascalCase+prefix   | EloquentApprenticeRepository  |
| DB Table              | snake_case plural   | apprentice_notes              |
| DB Column             | snake_case          | created_at, mentor_id         |
| API Route             | kebab-case          | /api/apprentice-notes         |
| FE Module folder      | kebab-case          | organization, job-catalog     |
| Vue Component         | PascalCase          | MentorDashboard.vue           |
| Pinia Store           | camelCase+suffix    | apprenticeStore.js            |
| API Service           | camelCase+suffix    | apprenticeService.js          |
| Vue Router file       | camelCase           | routes.js                     |
| Git Branch            | kebab-case          | feature/PROJ-42-reg-form      |
#### Architecture Rules — Controller→Service→Repository
- WAJIB: HTTP Request → Controller → Service → Repository → Model
- Tidak boleh skip layer (Controller tidak boleh akses Model langsung)
- Validasi HANYA di Form Request
- Business logic HANYA di Service class
- Query DB HANYA di Repository (Eloquent atau QueryBuilder)
- API response WAJIB pakai ApiResponse::success() / ApiResponse::error()
- UUID di URL publik, bukan sequential ID
- Multi-table mutations WAJIB DB::transaction()
- Migration SELALU di backend/database/migrations/ (bukan di dalam Modules/)

#### Frontend Rules
- Semua axios call di module services/ — JANGAN langsung di View/Component
- Semua state di Pinia stores — jangan reactive() di luar store untuk shared state
- useRouter / useRoute WAJIB diimport eksplisit dari vue-router
- Komponen wajib PascalCase di template (VBtn, bukan v-btn)
- Tidak ada semicolon, 2 spasi indent, trailing comma multiline
- DILARANG: Tailwind directional (pl-, pr-, ml-, mr-) → pakai ps-, pe-, ms-, me-
- Icons: gunakan Remix Icon (@iconify-json/ri) — JANGAN MDI
- Charts: gunakan vue3-apexcharts — JANGAN Chart.js atau library lain
#### Restrictions

> ❌ Dilarang keras — jangan pernah commit:

- `dd()`, `var_dump()`, die() di kode yang di-commit
- ❌ Hardcode credentials atau URL environment
- ❌ Skip migration untuk perubahan schema
- ❌ API call langsung dari Vue component (pakai services/)
- ❌ Logic di Controller atau View — hanya orchestrate/display
- ❌ Modifikasi file apapun di .template/ dengan alasan apapun
- ❌ Import langsung dari .template/ ke source code production
- ❌ Merge tanpa CI passing + 1 reviewer approval
#### Testing Requirements
- Setiap API endpoint: Feature test di backend/tests/Feature/
- Setiap Service method: Unit test di backend/tests/Unit/
- SQLite :memory: untuk test (bukan MySQL)
- Factory untuk data test — JANGAN data real/seeded
- Minimum 80% coverage untuk kode baru
#### Git Workflow
- Branch: feature/PROJ-{id}-{desc} | fix/PROJ-{id}-{desc}
- Commit: [feat] | [fix] | [test] | [refactor] | [docs]
- PR: CI passing + 1 approval sebelum merge ke dev
- Frontend References — Urutan Baca Wajib Sebelum Build Komponen

Hierarki referensi (jika konflik → .design selalu menang):

- 1. .design/DESIGN-SYSTEM.md     ← PRIMARY: brand, warna, font, spacing, visual spec
- 2. .template/TEMPLATE.md        ← STRUCTURAL: pola Vuetify component yang tersedia
- 3. .docs/TEMPLATE-ADAPTATION.md ← BRIDGE: cara terapkan (1) menggunakan struktur (2)

Aturan wajib:
- ❌ Tiru struktur layout & Vuetify props dari .template/
- ❌ Warna primary/success/error otomatis via Vuetify theme — tidak perlu diubah
- ❌ Vuetify state (theme, drawer, layout) WAJIB via composables: useTheme(), useLayout()
- ❌ Logo dari .design/assets/ → copy ke frontend/public/ (jangan import via JS bundler)
- ❌ Jangan copy import paths @core/, @layouts/, @core-scss/ dari template
- ❌ Jangan document.documentElement.* untuk Vuetify state — selalu pakai composables
- ❌ Jangan emoji — design system melarang emoji
#### 10. Lessons Learned
<!-- Update tiap retrospective sprint -->
| Sprint | Lesson |
|--------|--------|
| 01 | Boolean env: filter_var(env('VAR', false), FILTER_VALIDATE_BOOLEAN) |
| 01 | Vuetify state via useTheme() — JANGAN document.documentElement.* |
| 01 | Axios baseURL: import.meta.env.VITE_API_URL (tanpa fallback hardcoded) |
| 01 | Repository binding di register() bukan boot() di ServiceProvider |

| 01 | Logo: copy dari .design/assets/ ke frontend/public/ — jangan import bundler |
#### Skills (Layer 3 — baca sebelum koding)
| Jenis Task              | Baca Skill                                      |
|-------------------------|-------------------------------------------------|
| Fitur baru              | .skills/test-driven-development/SKILL.md        |
| Bug / test gagal        | .skills/systematic-debugging/SKILL.md           |
| Task kompleks / banyak file | .skills/writing-plans/SKILL.md              |
| Sebelum klaim selesai   | .skills/verification-before-completion/SKILL.md |
| Status change / transition | .skills/atomic-state-transition/SKILL.md     |
| Sebelum buat PR         | .skills/finishing-a-development-branch/SKILL.md |

> ✓ Perhatikan section `## Skills` di `CLAUDE.md` — ini adalah jembatan antara Layer 1 dan Layer 3. Agent tahu skill mana yang dibaca untuk task apa tanpa harus menebak.

### ARCHITECTURE.md — Apa yang Ada di Dalamnya

`ARCHITECTURE.md` adalah standar teknikal A&D yang berlaku untuk semua project. Berisi 19 sections — section 1–16 adalah technical framework yang reusable, section 17–18 diisi spesifik per project.

| #   | Section               | Isi                                                                            |
|-----|-----------------------|--------------------------------------------------------------------------------|
| §1  | System Overview       | Diagram decoupled: Vue SPA → Axios → Laravel API → MySQL                       |
| §2  | Module Types          | DOMAIN → PRODUCT → MODULES → FEATURES · Master/Transaction/Inquiry/Dashboard  |
| §3  | Tech Stack Detail     | Versi lengkap semua dependency + implementation notes                          |
| §4  | Pre-Requisites        | PHP ^8.2, Node ^18, MySQL, VS Code extensions                                  |
| §5  | Patterns — Backend    | Folder structure · C→S→R wajib · ServiceProvider binding · code examples       |
| §6  | Patterns — Frontend   | Module-based SPA · auto-import rules · Vite aliases                            |
| §7  | Auth & Authorization  | Login flow · 8 roles · AccessScopeService · LDAP gotcha                        |
| §8  | API Design            | Conventions · shallow routing · boolean param · breadcrumb gotcha              |
| §9  | Frontend UI           | Layout system · Materio pattern · chart, stat card, nav conventions            |
| §10 | State Management      | Pinia store pattern · router guard logic                                       |
| §11 | Database Principles   | Core entity convention · naming · soft delete + unique · FK patterns           |
| §12 | Migration Strategy    | Urutan timestamp · circular FK resolution · ALTER TABLE pattern                |
| §13 | Code Conventions      | ESLint rules lengkap · backend rules · LDAP env                                |
| §14 | Testing Strategy      | PHPUnit SQLite `:memory:` · Vitest jsdom · localStorage mock                   |
| §15 | Concurrency Patterns  | Atomic CAS · pessimistic lock · queue jobs untuk bulk >20                      |
| §16 | Cara Tambah Modul     | Backend 12 langkah · Frontend 8 langkah                                        |
| §17 | Domain Hierarchy      | PROJECT-SPECIFIC — domain entity relationships                                 |
| §18 | Sprint Roadmap        | PROJECT-SPECIFIC — lihat `.docs/sprints/sprint-roadmap.md`                    |
| §19 | Training Modules      | 18 modul onboarding A&D: Foundation · Advanced · Production                   |
