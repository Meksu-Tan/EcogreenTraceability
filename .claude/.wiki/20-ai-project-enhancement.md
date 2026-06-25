# AI Project Enhancement — Context Awareness & Executor Collaboration

> Source: AnD Human Capital — Implementation Guide (v20260602)

> Modul ini melengkapi Modul 18 (AI-Assisted Setup) dan Modul 19
> (AI-Assisted Development). Dua masalah yang tersisa setelah keduanya:
> memory hilang antar sesi, dan token boros karena Claude menulis semua
> kode sendiri. Modul ini menyelesaikan keduanya.

---

## Daftar Isi

| # | Topik | Deskripsi |
|---|-------|-----------|
| 1 | Masalah yang Dipecahkan | Kenapa modul 18/19 belum cukup |
| 2 | Persistent Memory System | Arsitektur, junction setup, memory types |
| 3 | Memory Writing Guide | Cara menulis memory yang efektif per tipe |
| 4 | Memory Lifecycle | Kapan tulis, update, hapus memory |
| 5 | CLAUDE.md Enhancement | Section memory + collaboration yang perlu ditambahkan |
| 6 | `/start-session` Command | Setup + isi lengkap + cara kerja |
| 7 | Token Economics | Matematika kenapa delegation menghemat token |
| 8 | Multi-Executor Setup | Install + auth + test OpenCode, Gemini, Copilot |
| 9 | Context Injection per Executor | Cara tiap executor menerima context |
| 10 | Brief Writing Guide | XS/S/M/L dengan contoh nyata |
| 11 | COLLABORATION.md | Template lengkap + semua section |
| 12 | Verifikasi Output Executor | Checklist review per jenis task |
| 13 | Alur Kerja End-to-End | Semua komponen bekerja bersama |
| 14 | Troubleshooting | Error umum dan solusinya |

---

## 1. Masalah yang Dipecahkan

### Masalah 1 — Memory hilang antar sesi

Modul 19 mengajarkan tiga lapisan konteks: Convention (CLAUDE.md),
Blueprint (sprint file), Skills. Tapi ketiga lapisan ini adalah
**konteks statis** — dokumen yang tidak berubah. Yang hilang adalah
**konteks dinamis**: sprint mana yang sedang berjalan, keputusan apa
yang sudah dibuat minggu lalu, pattern error apa yang sudah ditemukan,
feedback apa yang pernah diberikan developer.

Tanpa memory sistem, setiap sesi Claude dimulai dari nol:
- Developer menjelaskan ulang "kita lagi sprint berapa"
- Claude mengulang kesalahan yang sudah pernah dikoreksi
- Sprint status harus selalu disampaikan manual
- Keputusan arsitektur yang sudah disepakati tidak diingat

### Masalah 2 — Token boros karena Claude generate kode sendiri

Setiap baris kode yang Claude tulis sendiri menghabiskan **output tokens**
— yang harganya 5× lebih mahal dari input tokens. Lebih parah lagi:
kode yang Claude generate masuk ke **context window** dan tetap ada
sampai sesi berakhir, dibayar ulang sebagai input tokens di setiap pesan.

Satu full CRUD layer yang Claude tulis sendiri (~1.500 baris) × 20 pesan
berikutnya = ~30.000 extra input tokens yang tidak berguna.

Di tengah sprint yang panjang, ini menyebabkan:
- Rate limit kena di tengah sprint
- Context window penuh → respons Claude memburuk
- Biaya per sprint jauh lebih tinggi dari yang seharusnya

**Solusi yang dibangun di modul ini:**

```
Masalah 1 → Persistent Memory System + /start-session command
Masalah 2 → Multi-executor collaboration + COLLABORATION.md
```

---

## 2. Persistent Memory System

### 2.1 Bagaimana Claude Code Mengelola Memory

Claude Code punya built-in **auto-memory system**. Setiap project yang
dibuka Claude Code punya direktori memory di:

```
~/.claude/projects/{project-slug}/memory/
```

`project-slug` adalah path project dengan separator diganti `-` dan
drive letter diikuti `--`:

```
D:\Apps\AnD Human Capital  →  D--Apps-AnD-Human-Capital
```

File di direktori ini — khususnya `MEMORY.md` — **dibaca Claude di awal
setiap sesi** via system prompt. Artinya:

- Memory yang ditulis ke direktori ini otomatis tersedia di sesi berikutnya
- Tidak perlu menyampaikan konteks ulang setiap hari
- Claude bisa menghindari mengulangi kesalahan yang sudah dikoreksi

### 2.2 Problem: Direktori Di Luar Repo

Direktori memory ada di luar repo. Ini berarti:
- Tidak bisa direferensikan di CLAUDE.md dengan path relatif
- Tidak bisa dibaca saat Claude membuka file-file project
- Developer yang join baru tidak punya panduan setup-nya

**Solusi: buat junction (symlink Windows / symlink Unix) dari dalam
repo ke direktori memory tersebut.**

```
project-root/
├── .context/          ← junction → ~/.claude/projects/{slug}/memory/
│   ├── MEMORY.md      ← index semua memory
│   ├── project_sprint-status.md
│   ├── feedback_linting.md
│   └── user_role.md
├── .claude/
│   └── CLAUDE.md      ← urutan baca mencantumkan .context/MEMORY.md
└── .gitignore         ← .context/ diabaikan git
```

`.context/` tidak di-commit. Setiap developer membuat junction-nya
sendiri di mesin masing-masing.

### 2.3 Setup Junction — Windows (PowerShell)

```powershell
# 1. Tentukan slug project
# Ganti \ dengan - dan drive letter ikuti format D--
$slug   = "D--Apps-AnD-Human-Capital"   # ← sesuaikan
$target = "$env:USERPROFILE\.claude\projects\$slug\memory"

# 2. Buat direktori memory jika belum ada
New-Item -ItemType Directory -Force $target | Out-Null

# 3. Buat MEMORY.md awal jika belum ada
$memFile = "$target\MEMORY.md"
if (-not (Test-Path $memFile)) {
    "# Memory Index`n" | Set-Content $memFile -Encoding utf8
}

# 4. Buat junction dari root project
# (jalankan dari root project directory — bukan dalam subfolder)
New-Item -ItemType Junction -Path ".context" -Target $target
```

Verifikasi:

```powershell
Get-Item .context | Select-Object FullName, LinkType, Target
# LinkType harus "Junction"
# Target harus menunjuk ke ~/.claude/projects/.../memory
```

> ⚠️ Jika muncul error "Access is denied" saat membuat junction di
> Windows: jalankan PowerShell sebagai Administrator, atau aktifkan
> Developer Mode di Settings → System → Developer options.

### 2.4 Setup Junction — macOS / Linux

```bash
# 1. Tentukan slug project
slug="D--Apps-AnD-Human-Capital"   # sesuaikan
target="$HOME/.claude/projects/$slug/memory"

# 2. Buat direktori memory
mkdir -p "$target"

# 3. Buat MEMORY.md awal
[ -f "$target/MEMORY.md" ] || echo "# Memory Index" > "$target/MEMORY.md"

# 4. Buat symlink dari root project
ln -s "$target" .context
```

Verifikasi:

```bash
ls -la .context
# Harus tampil: .context -> /Users/{user}/.claude/projects/.../memory
```

### 2.5 Tambahkan ke .gitignore

```gitignore
# Memory system — setiap developer punya junction sendiri
.context/
```

### 2.6 Struktur File Memory

Setiap memory adalah file Markdown dengan frontmatter YAML:

```
.context/
├── MEMORY.md                          ← index (dibaca pertama)
├── user_{topik}.md                    ← info tentang developer
├── feedback_{topik}.md                ← koreksi / konfirmasi pola
├── project_{topik}.md                 ← status project, keputusan
└── reference_{topik}.md               ← pointer ke sistem eksternal
```

**Format file memory:**

```markdown
---
name: {nama-kebab-case-unik}
description: {satu kalimat — dipakai untuk decide relevansi di sesi depan}
metadata:
  type: {user | feedback | project | reference}
---

{isi memory}

**Why:** {alasan — constraint, kejadian, preferensi yang melahirkan aturan ini}
**How to apply:** {kapan dan bagaimana aturan ini berlaku}
```

### 2.7 MEMORY.md — Index File

`MEMORY.md` adalah satu-satunya file yang **selalu** di-load ke context
Claude di setiap sesi. Karena itu, formatnya ketat:

```markdown
# Memory Index — [Project Name]

- [Sprint Status](project_sprint-status.md) — Sprint 00–05 DONE; Sprint 06 next
- [Collaboration workflow](project_opencode-collab.md) — OpenCode=default; Gemini=fallback
- [No proceed questions](feedback_no-proceed.md) — langsung eksekusi tanpa konfirmasi
- [Run linters each sprint](feedback_linting.md) — Pint + ESLint di akhir sprint
- [SQLite migration compat](feedback_sqlite-compat.md) — guard pgsql DDL di test
```

**Aturan MEMORY.md:**

| Aturan | Detail |
|--------|--------|
| Maks 200 baris | Setelah 200 baris, konten di-truncate — tidak dibaca Claude |
| Satu baris per memory | Format: `- [Title](file.md) — hook singkat (max ~120 char)` |
| Tidak ada konten langsung | MEMORY.md hanya pointer; konten ada di file individual |
| Urutkan by relevance | Memory paling sering dipakai di atas |

---

## 3. Memory Writing Guide

### 3.1 Tipe `user` — Info tentang Developer

Catat role, expertise, dan preferensi developer yang mempengaruhi cara
Claude berkomunikasi dan menjelaskan.

**Kapan ditulis:** Ketika developer menyebutkan background, expertise,
atau cara kerja yang berpengaruh ke kolaborasi.

```markdown
---
name: user_role-and-expertise
description: Developer role, stack expertise, and working preferences
metadata:
  type: user
---

Senior Laravel developer (5 tahun). Baru pertama kali menyentuh Vue/frontend
di project ini. Preferensi: jawaban langsung tanpa banyak pengantar.

**How to apply:** Untuk pertanyaan backend, bisa langsung ke inti.
Untuk frontend, sertakan sedikit konteks kenapa pola tertentu dipakai
(karena belum familiar dengan Vue ecosystem).
```

### 3.2 Tipe `feedback` — Koreksi dan Konfirmasi Pola

Ini tipe paling penting. Rekam setiap koreksi agar Claude tidak
mengulangi kesalahan yang sama, dan setiap konfirmasi agar pola yang
sudah terbukti tetap dipertahankan.

**Kapan ditulis:**
- Developer mengatakan "jangan", "stop", "tidak perlu", "salah"
- Developer mengkonfirmasi pendekatan yang non-obvious: "iya tepat sekali",
  "bagus, lanjut seperti ini"

**Contoh feedback dari koreksi:**

```markdown
---
name: feedback_no-proceed-questions
description: Do not ask confirmation before acting — just implement
metadata:
  type: feedback
---

Jangan bertanya "do you want to proceed?", "shall I continue?",
"boleh implementasi?", atau sejenisnya sebelum mengerjakan task.
Langsung implementasi.

**Why:** Pertanyaan konfirmasi = friction yang memperlambat workflow.
Berlaku untuk semua pekerjaan dalam repo ini di setiap sesi.

**How to apply:** Setelah analisis dan perencanaan, langsung
implementasi. Hanya berhenti jika ada ambiguitas genuine tentang *apa*
yang harus dilakukan (dua pendekatan dengan trade-off berbeda yang
keputusannya ada di developer).
```

**Contoh feedback dari konfirmasi pola:**

```markdown
---
name: feedback_run-linters-each-sprint
description: Run Pint + ESLint at the end of every sprint before commit
metadata:
  type: feedback
---

Jalankan `./vendor/bin/pint` (dari `backend/`) dan
`npx eslint --fix resources/js` (dari `frontend/`) di akhir setiap
sprint sebelum commit terakhir.

**Why:** Sprint 05 sesi 2026-05-28: 97 file PHP dan 11 ESLint error
terakumulasi tanpa automated gate — semua harus difix manual di akhir.

**How to apply:** Setelah semua task sprint selesai, jalankan kedua
linter sebelum commit final. Untuk ESLint error yang `--fix` tidak bisa
selesaikan (sonarjs/no-collapsible-if, vue/prefer-true-attribute-shorthand),
perbaiki manual: merge nested if dengan `&&`, ganti `:prop="true"`
dengan shorthand `prop`.
```

### 3.3 Tipe `project` — Status dan Keputusan Project

Catat state project yang berubah: sprint mana yang aktif, keputusan
arsitektur yang tidak obvious dari kode, constraint, deadline.

**Kapan ditulis:** Ketika sprint status berubah, ada keputusan arsitektur
yang disepakati, atau ada context penting yang tidak ada di kode/git.

```markdown
---
name: project_sprint-status
description: Current sprint status — what is done, what is next
metadata:
  type: project
---

Per 2026-06-02: Sprint 00–05 DONE. Next: Sprint 06 Assessment Execution
+ Scoring (belum dimulai).

Sprint 05 deliverables: migrations 21/39/40, 22 API routes assessment
plan + period, AssessmentPlanService::generateItems() + generateSessions(),
35/35 tests passing. Commits: 057390d2 (feat) + e0665d10 (fix review).

**Why:** Berguna untuk /start-session — tidak perlu baca git log untuk
tahu di mana kita berada.

**How to apply:** Update setiap kali sprint selesai atau milestone
signifikan tercapai. Sertakan commit hash dan jumlah test passing.
```

> ⚠️ Tanggal relatif ("kemarin", "minggu ini") menjadi tidak berguna
> setelah beberapa minggu. Selalu konversi ke tanggal absolut saat menyimpan.

### 3.4 Tipe `reference` — Pointer ke Sistem Eksternal

Catat di mana informasi di luar repo bisa ditemukan.

```markdown
---
name: reference_external-tools
description: External systems used in this project
metadata:
  type: reference
---

- Bug tracker: Linear project "COMPETENCY-MATRIX"
- Design mockup: Figma file "AnD HCM v2" (link di Notion)
- Staging server: hcm-staging.and.co.id — deploy manual via `./deploy.sh`
- Production DB backup: setiap Minggu 02:00 WIB di S3 bucket `and-hcm-backup`
```

### 3.5 Cara Terhubung Antar Memory

Gunakan `[[nama-slug]]` untuk referensi silang antar memory:

```markdown
Lihat juga [[project_sprint-status]] untuk context sprint aktif
saat aturan ini paling sering dipakai.
```

`[[slug-yang-belum-ada]]` tidak menyebabkan error — menandai sesuatu
yang worth ditulis nanti.

---

## 4. Memory Lifecycle

### 4.1 Kapan Memory Ditulis

Claude menulis memory otomatis ketika:

| Trigger | Tipe yang ditulis |
|---------|-------------------|
| Developer mengoreksi approach Claude | `feedback` |
| Developer mengkonfirmasi pola non-obvious | `feedback` |
| Sprint status berubah (sprint selesai) | `project` |
| Keputusan arsitektur disepakati | `project` |
| Developer menyebutkan background/expertise | `user` |
| Developer meminta "ingat ini" secara eksplisit | sesuai konten |

Developer juga bisa meminta Claude menyimpan memory:
```
"ingat bahwa kita pakai PostgreSQL port 5433 di staging"
"simpan: linter dijalankan sebelum commit final"
```

### 4.2 Kapan Memory Diupdate

Update memory ketika kondisi yang dicatat berubah:
- Sprint selesai → update `project_sprint-status.md`
- Koreksi sebelumnya tidak relevan lagi → update atau hapus file
- Keputusan arsitektur berubah → update file terkait

### 4.3 Kapan Memory Dihapus

Hapus ketika:
- Memory tentang sprint yang sudah jauh berlalu (2+ sprint lalu) dan
  tidak ada lesson yang reusable
- Feedback yang sudah tidak berlaku karena pola berubah
- Reference ke sistem yang sudah tidak dipakai

### 4.4 Yang Tidak Perlu Disimpan di Memory

Memory bukan tempat menyimpan semua hal. Yang tidak perlu:

| Bukan untuk memory | Kenapa |
|---------------------|--------|
| Code patterns, conventions | Ada di CLAUDE.md dan ARCHITECTURE.md |
| Git history, siapa mengubah apa | `git log` / `git blame` lebih akurat |
| Fix recipes untuk bug spesifik | Fix ada di kode; konteks di commit message |
| Apapun yang ada di CLAUDE.md | Sudah ada, jangan duplikasi |
| Task in-progress saat ini | Gunakan task list untuk ini |

### 4.5 Verifikasi Memory Sebelum Bertindak

Memory bisa stale. Sebelum Claude merekomendasikan sesuatu berdasarkan
memory:

- Memory menyebut file path spesifik → verifikasi file masih ada
- Memory menyebut function atau flag → grep untuk cek masih exist
- Memory menyebut status project → cek git log untuk verifikasi

"Memory bilang X ada" ≠ "X ada sekarang."

---

## 5. CLAUDE.md Enhancement

Tambahkan dua section ke `CLAUDE.md` project:

### 5.1 Section: Urutan Baca dan Cara Penggunaan

Letakkan di **paling atas** CLAUDE.md, sebelum section lain:

```markdown
## Cara Menggunakan Dokumen Ini

**Urutan baca wajib sebelum mengerjakan sprint apapun:**

1. `.context/MEMORY.md`              → session memory: sprint status,
                                        feedback, lessons (BACA PERTAMA)
2. `.claude/CLAUDE.md`               → stack, konvensi, forbidden patterns
3. `.docs/ARCHITECTURE.md`           → arsitektur lengkap, patterns, DB
4. `.docs/sprints/sprint-roadmap.md` → urutan sprint, dependency graph
5. `.docs/sprints/sprint-[N]-*.md`   → detail sprint yang sedang dikerjakan
── Referensi (baca sesuai kebutuhan) ───────────────────────────────────
6. `.docs/COLLABORATION.md`          → executor workflow, token economics
7. `.docs/LESSONS-ARCHIVE.md`        → lessons dari sprint sebelumnya

> ℹ️ **`.context/`** adalah junction ke
> `~/.claude/projects/{slug}/memory/`.
> Berisi `MEMORY.md` (index) dan file memory individual.
> Tidak di-commit. Setiap developer buat junction sendiri di mesin masing-
> masing — lihat `.onboarding/20-ai-project-enhancement.md` §2.
```

### 5.2 Section: Collaboration Workflow

Letakkan di dekat section Development Workflow:

```markdown
## Collaboration Workflow

Implementasi didelegasikan ke executor — bukan ditulis Claude langsung.
Detail lengkap dan semua aturan ada di `.docs/COLLABORATION.md`.

**Executor reference:**

| Executor | Command | Gunakan untuk |
|----------|---------|---------------|
| OpenCode (default) | `opencode run -m "opencode-go/mimo-v2.5-pro" --dangerously-skip-permissions` | git, migration, CRUD layer, test rutin |
| Gemini CLI | `gemini -p "..." -y` | Large context (>5 file), fallback OpenCode rate-limited |
| GitHub Copilot | `gh copilot -- --allow-all -s -p "..."` | GitHub context, reasoning depth |

**Aturan wajib:**
- Claude hanya tulis brief + lakukan judgment. Semua eksekusi → executor.
- Setiap brief wajib diakhiri format `LAPORAN:` (lihat COLLABORATION.md).
- Sequential: satu executor selesai + diverifikasi Claude → baru berikutnya.
- Claude tidak ganti model — Sonnet fixed. Jangan pakai `/fast` atau Opus.
```

---

## 6. `/start-session` Command

### 6.1 Konsep

Slash command di Claude Code adalah file Markdown di `.claude/commands/`.
Dipanggil dengan `/nama-command`, isinya langsung dieksekusi Claude.

`/start-session` adalah entry point tiap sesi kerja. Dalam satu perintah,
Claude membaca semua konteks yang dibutuhkan, verifikasi tool yang
tersedia, dan melaporkan state project saat ini.

### 6.2 Buat Command File

```bash
mkdir -p .claude/commands
```

Isi `.claude/commands/start-session.md`:

````markdown
Read the following files in order and build a complete picture of the
project state:

1. `.context/MEMORY.md` — session memory index
2. Read each memory file linked in MEMORY.md that is relevant to current work
3. `.docs/sprints/sprint-roadmap.md` — sprint order and dependencies
4. The current active sprint file (identified from sprint-roadmap.md)
5. `.docs/COLLABORATION.md` — executor workflow and token economics

After reading, do the following:

**Verify executor connectivity** by running all three checks in parallel
via Bash tool:

OpenCode:
```bash
opencode run -m "opencode-go/mimo-v2.5-pro" --dangerously-skip-permissions \
  "reply with: OpenCode ready."
```

Gemini CLI:
```bash
gemini -p "reply with: Gemini ready." -y
```

Copilot:
```bash
gh copilot --version
```

Then report a session brief in this exact format:

---
## Session Ready

**Sprint aktif:** [sprint name + status]
**Pending dari sesi sebelumnya:** [bullet list dari memory, atau "tidak ada"]
**Lessons kritis:** [maks 3 poin dari LESSONS-ARCHIVE jika relevan dengan
sprint aktif]

**Collaboration mode:**
- OpenCode [ready / unreachable] — implementasi S/M/L, git, linting
- Gemini CLI [ready / unreachable] — eksplorasi large context, fallback
- Copilot [ready / unreachable — v{version}] — GitHub context, reasoning
- Arsitektur & keputusan → Claude

**Siap melanjutkan.**
---
````

### 6.3 Cara Penggunaan

Di awal setiap sesi kerja, ketik:

```
/start-session
```

Output yang dihasilkan contohnya:

```
## Session Ready

**Sprint aktif:** Sprint 06 — Assessment Execution + Scoring (belum dimulai)

**Pending dari sesi sebelumnya:**
- Sprint 05 selesai (35/35 tests passing, commit e0665d10)
- assessment_sessions table sudah ada sebagai stub — Sprint 06 perlu ALTER
- Jalankan linter sebelum commit final sprint

**Lessons kritis:**
1. assessment_sessions initial status = 'draft' (bukan 'pending')
2. Scoring wajib formula 4-layer — jangan simple avg()
3. SQLite compat: guard pgsql DDL, dropIndex sebelum dropColumn

**Collaboration mode:**
- OpenCode ready — implementasi S/M/L, git, linting
- Gemini CLI ready — eksplorasi large context, fallback
- Copilot ready — v1.0.5 — GitHub context, reasoning
- Arsitektur & keputusan → Claude

**Siap melanjutkan.**
```

### 6.4 Kustomisasi

Tambahkan file ke daftar bacaan sesuai kebutuhan project:

```markdown
# Tambahkan setelah nomor 5:
6. `.docs/LESSONS-ARCHIVE.md` — jika sprint baru akan dimulai
7. `.docs/app-blueprint.md`   — jika ada pertanyaan domain model
```

Tambahkan verifikasi tool lain jika dipakai di project:

```markdown
# Tambahkan ke blok verifikasi executor:
Docker:
```bash
docker --version
```
```

---

## 7. Token Economics

### 7.1 Mengapa Ini Penting

Claude (Sonnet) punya pricing yang asimetris:

```
Input tokens:  $3  / 1M token
Output tokens: $15 / 1M token  ← 5× lebih mahal
```

**Setiap baris kode yang Claude tulis sendiri = output tokens mahal.**
**Setiap baris kode yang executor tulis = Claude hanya baca report ringkas = input tokens murah.**

### 7.2 Penghematan Per Task

| Task | Output tokens Claude langsung | Output tokens via executor | Hemat |
|------|-------------------------------|---------------------------|-------|
| Git commit/push (XS) | ~80 | ~130 | **−50 (overhead)** |
| Migration file ~150 baris (S) | ~600 | ~80 | **~520 (87%)** |
| Feature test ~400 baris (M) | ~1.600 | ~120 | **~1.480 (93%)** |
| Full CRUD layer ~1.500 baris (L) | ~6.000 | ~300 | **~5.700 (95%)** |

XS task (git, linting) secara per-task tidak hemat — brief overhead ≈
biaya Claude direct. Tetap delegasikan untuk workflow consistency dan
untuk mencegah context pollution.

### 7.3 Hidden Savings: Context Pollution

Ini yang paling sering diabaikan dan dampaknya paling besar.

Kalau Claude generate kode sendiri (full CRUD layer ~1.500 baris),
kode itu **masuk ke context window** dan tetap ada sampai sesi berakhir.
Setiap pesan berikutnya membayar kode itu lagi sebagai input tokens.

```
1 CRUD layer (~1.500 baris) × 20 pesan berikutnya
= 30.000 extra input tokens
= $0.09 hanya dari satu layer

10 layer dalam satu sprint × $0.09 = $0.90 terbuang hanya dari pollution
```

Dengan delegasi ke executor: kode tidak pernah masuk context Claude.
**Context tetap lean → sesi lebih panjang → tidak kena rate limit di tengah sprint.**

### 7.4 Proyeksi per Sprint Penuh (~30 task S/M/L)

```
10 migration files (S):   10 × 520   =  5.200 output tokens hemat
15 test files (M):        15 × 1.480 = 22.200 output tokens hemat
5 full CRUD layers (L):    5 × 5.700 = 28.500 output tokens hemat
                          ──────────────────────────────────────────
Subtotal kode:                        ~55.900 output tokens  ≈ $0.84

Context pollution dihindari:          ~100.000+ input tokens ≈ $0.30
─────────────────────────────────────────────────────────────────────
Total hemat per sprint:                                       ≈ $1.10
```

Angka dollar terlihat kecil. Tapi implikasinya lebih besar dari sekadar
biaya: Claude **tidak kena rate limit**, context window **tidak penuh
di tengah sprint**, dan sesi bisa berjalan lebih lama tanpa restart.
Perbedaannya terasa sebagai "sprint selesai lancar vs terganggu di
tengah jalan."

### 7.5 Kesimpulan

1. Selalu delegasikan S/M/L ke executor — savings 87–95% output tokens
2. XS tetap delegasikan — bukan untuk hemat, tapi cegah context pollution
3. Wajib pakai `LAPORAN:` di setiap brief — verbose output executor = token Claude terbuang
4. Pecah Brief L → beberapa Brief M berseri — lebih aman dan lebih hemat
5. Claude hanya untuk judgment — analisis, arsitektur, review. Semua eksekusi ke executor

---

## 8. Multi-Executor Setup

### 8.1 OpenCode CLI

OpenCode adalah CLI executor yang menyediakan akses ke berbagai model AI.
**MiMo** (`mimo-v2.5-pro`) adalah model default, tapi OpenCode juga mendukung
deepseek, kimi, minimax, glm, qwen, dan model lainnya.

**Install:**

```bash
npm install -g opencode-ai
```

**Verifikasi:**

```bash
opencode --version
```

**Login pertama kali:**

```bash
# Navigasi ke project directory
cd D:\Apps\[project-name]

# Jalankan opencode — browser akan terbuka untuk OAuth
opencode
```

**Test koneksi OpenCode:**

```bash
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions "reply with: OpenCode ready."
```

**Model yang dipakai di OpenCode:**

| Model ID | Karakter | Gunakan untuk |
|----------|----------|---------------|
| `opencode-go/mimo-v2.5-pro` | Cepat, efisien | **Default** — git, migration, CRUD, test sederhana, UI |
| `opencode-go/deepseek-v4-flash` | Reasoning dalam | Bug kompleks, full module besar, banyak edge case |
| `opencode-go/kimi-k2.6` | Context 1M+, coding kuat | L task dengan banyak -f file injection (>5 file) |
| `opencode-go/minimax-m2.7` | Structured output | S/M boilerplate repetitif saat mimo rate-limited |
| `opencode-go/glm-5.1` | Bilingual, Indonesian | Tasks dengan Indonesian content generation |
| `opencode-go/qwen3.7` | Reasoning + coding | Bug reasoning kompleks — alternatif deepseek |

> Model non-pro mimo dan deepseek free tidak dipakai — tidak reliable untuk task production.

**Flag penting:**

| Flag | Fungsi |
|------|--------|
| `--dangerously-skip-permissions` | Auto-approve semua action tanpa konfirmasi |
| `-f <path>` | Inject file sebagai context (bisa diulang) |
| `--` | Separator wajib antara flags dan brief string |

> ⚠️ `--dangerously-skip-permissions` mengizinkan OpenCode menjalankan
> command apapun termasuk file deletion dan git operations. Gunakan
> hanya di development environment dengan repo yang kamu kontrol.

---

### 8.2 Gemini CLI

Gemini CLI menggunakan Google account — free dengan rate limit harian.
Dipakai untuk task yang butuh large context (banyak file sekaligus) dan
sebagai fallback ketika OpenCode rate-limited.

**Install:**

```bash
npm install -g @google/gemini-cli
```

**Verifikasi:**

```bash
gemini --version
```

**Login pertama kali (interaktif — perlu browser):**

```bash
gemini
# Browser terbuka secara otomatis → login Google Account → OAuth selesai
# Credentials tersimpan di ~/.gemini/
# Setelah login, Ctrl+C untuk keluar dari mode interaktif
```

**Test koneksi:**

```bash
gemini -p "reply with: Gemini ready." -y
```

**Model Gemini yang dipakai:**

| Model | Flag | Gunakan untuk |
|-------|------|---------------|
| `gemini-2.5-pro` *(default)* | *(tanpa flag)* | Large context (>5 file), full module, large refactor |
| `gemini-2.5-flash` | `-m gemini-2.5-flash` | Task ringan, respon lebih cepat |
| `gemini-2.5-flash-lite` | `-m gemini-2.5-flash-lite` | Task sangat ringan, hemat quota |

**Flag penting:**

| Flag | Fungsi |
|------|--------|
| `-p "..."` | Prompt (inline) |
| `-y` | YOLO mode — auto-approve semua action tanpa konfirmasi |
| `-m <model>` | Override model (default: gemini-2.5-pro) |

**Rate limit:** Free tier punya quota per-minute (RPM) dan per-day.
Gemini CLI retry otomatis saat RPM habis. Jika daily quota habis, switch
ke OpenCode atau Copilot untuk hari itu.

> Gemini CLI tidak punya flag `-f` untuk inject file. Context diberikan
> via instruksi di awal prompt — Gemini membaca file sendiri dari
> working directory. (Detail di §9.)

---

### 8.3 GitHub Copilot CLI

Copilot CLI menggunakan akun GitHub dengan subscription Copilot aktif.
Dipakai untuk task yang butuh GitHub context (PR, issue) atau reasoning
depth lebih dalam.

**Prerequisite: GitHub CLI**

```bash
# Windows
winget install GitHub.cli

# macOS
brew install gh

# Linux (Debian/Ubuntu)
sudo apt install gh
```

**Install ekstensi Copilot:**

```bash
gh extension install github/gh-copilot
```

**Login GitHub:**

```bash
gh auth login
# Pilih: GitHub.com → HTTPS → Login with web browser
```

**Verifikasi:**

```bash
gh copilot --version
```

**Test koneksi:**

```bash
gh copilot -- --allow-all -s -p "reply with: Copilot ready."
```

**Flag penting:**

| Flag | Fungsi |
|------|--------|
| `--allow-all` | Auto-approve semua action tanpa konfirmasi |
| `-s` | Silent — hanya output agent, tanpa stats |
| `-p "..."` | Prompt (inline) |
| `--effort high` | Reasoning depth lebih dalam (lebih lambat, lebih akurat) |
| `--add-github-mcp-tool '*'` | Akses PR, issue, repo API via GitHub MCP |
| `--resume` | Lanjutkan sesi Copilot sebelumnya |

> Copilot CLI juga tidak punya flag `-f`. Context via instruksi di
> awal prompt, sama seperti Gemini.

---

## 9. Context Injection per Executor

Ketiga executor menerima context dengan cara berbeda.

### 9.1 OpenCode — Flag `-f`

OpenCode punya flag `-f` untuk inject file langsung ke context:

```bash
# S — satu context file
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -- "brief"

# M — beberapa context file
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -f ".docs/ARCHITECTURE.md" \
  -f ".docs/sprints/sprint-06-assessment-scoring.md" \
  -- "brief"

# L — full context + model lebih dalam
opencode run -m "opencode-go/deepseek-v4-flash" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -f ".docs/ARCHITECTURE.md" \
  -f ".docs/LESSONS-ARCHIVE.md" \
  -f ".docs/sprints/sprint-06-assessment-scoring.md" \
  -- "brief"
```

**Aturan `--` wajib:** Pemisah antara flags dan brief string. Tanpa
`--`, string yang dimulai dengan `-` di brief akan diinterpretasikan
sebagai flag.

### 9.2 Gemini — Instruksi di Awal Prompt

Gemini tidak punya `-f`. Claude memberi instruksi baca file di awal
prompt — Gemini membaca file dari working directory sendiri:

```bash
gemini -p "Baca file-file ini dulu sebelum mengerjakan task:
- .claude/CLAUDE.md              (konvensi kode wajib)
- .docs/ARCHITECTURE.md          (patterns backend+frontend)    [M dan L]
- .docs/LESSONS-ARCHIVE.md       (lessons dari sprint sebelumnya) [L saja]
- .docs/sprints/sprint-06-*.md   (spec sprint terkait)          [M dan L]

Setelah membaca, konfirmasi singkat apa yang kamu pahami, lalu kerjakan:

[brief]" -y
```

### 9.3 Copilot — Instruksi di Awal Prompt

Sama seperti Gemini:

```bash
gh copilot -- --allow-all -s -p "Baca file-file ini dulu sebelum mengerjakan task:
- .claude/CLAUDE.md              (konvensi kode wajib)
- .docs/ARCHITECTURE.md          (patterns backend+frontend)    [M dan L]
- .docs/sprints/sprint-06-*.md   (spec sprint terkait)          [M dan L]

Task: [brief]"
```

### 9.4 Berapa Context yang Dibutuhkan per Size

| Brief size | Context yang dibutuhkan |
|------------|------------------------|
| XS | Tidak ada — brief satu kalimat sudah cukup |
| S | CLAUDE.md saja — konvensi naming dan restrictions |
| M | CLAUDE.md + ARCHITECTURE.md + sprint file relevan |
| L | CLAUDE.md + ARCHITECTURE.md + LESSONS-ARCHIVE + sprint file |

---

## 10. Brief Writing Guide

Brief adalah instruksi yang Claude kirim ke executor. Kualitas brief
menentukan kualitas output. Ini adalah judgment utama yang harus Claude
lakukan — executor tidak bisa memperbaiki brief yang buruk.

**Prinsip utama:** Batas delegasi bukan jumlah file atau ukuran task —
tapi **seberapa banyak judgment yang dibutuhkan**. Kalau semua judgment
call sudah dihilangkan dari brief, task bisa didelegasikan
seberapapun besarnya.

### 10.1 Format LAPORAN — Wajib di Setiap Brief

Selalu tambahkan di akhir setiap brief:

```
LAPORAN: Hanya tampilkan:
(1) file yang dibuat/diubah dengan path lengkap
(2) hasil test: pass/fail + jumlah test
(3) error jika ada
Jangan tampilkan isi file atau output verbose lainnya.
```

Tanpa ini, executor akan mencetak ratusan baris kode ke output Claude —
membuang token yang harusnya dihemat.

### 10.2 Brief XS

**Kriteria:** Satu operasi, satu kalimat, tidak butuh context file.

```bash
# Git commit dan push
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  "Commit semua staged files dengan message '[feat] sprint 06 — assessment session + participant backend'. Push ke origin master. LAPORAN: commit hash, files changed."

# Linting saja
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  "Dari folder backend/, jalankan ./vendor/bin/pint. Dari folder frontend/, jalankan npx eslint --fix resources/js. LAPORAN: file yang difix, error yang tidak bisa auto-fix."

# Single-file UI fix
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  "Buka frontend/resources/js/modules/assessmentPlan/views/PlanListView.vue. Ganti semua class 'pl-' menjadi 'ps-' dan 'pr-' menjadi 'pe-'. LAPORAN: file changed, jumlah replacement."
```

### 10.3 Brief S

**Kriteria:** Satu file, ~50–200 baris. Butuh CLAUDE.md untuk naming.

**Contoh — migration file:**

```bash
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -- "Buat migration baru di backend/database/migrations/ dengan
step 22: create_assessment_sessions_table.

Schema:
- id (bigint, PK auto)
- assessment_period_id (FK → assessment_periods.id, cascade delete)
- assessment_plan_item_id (FK → assessment_plan_items.id, nullable)
- employee_id (FK → employees.id)
- matrix_version_id (FK → matrix_versions.id)
- target_level_id (FK → matrix_job_levels.id, nullable)
- status enum('draft','in_progress','submitted','reviewed','approved','cancelled') default 'draft'
- timestamps + softDeletes

Index: status, employee_id, assessment_period_id.
Partial unique index: (employee_id, assessment_period_id) WHERE deleted_at IS NULL.
Wajib: declare(strict_types=1), guard pgsql-only DDL dengan if (DB::getDriverName() === 'pgsql').

LAPORAN: (1) file created dengan path, (2) php artisan migrate --pretend output,
(3) error jika ada."
```

**Contoh — FormRequest:**

```bash
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -- "Buat FormRequest:
backend/Modules/Assessment/app/Http/Requests/StoreAssessmentSessionRequest.php

Rules:
- assessment_period_id: required, exists:assessment_periods,id
- employee_id: required, exists:employees,id
- matrix_version_id: required, exists:matrix_versions,id
- target_level_id: nullable, exists:matrix_job_levels,id

authorize(): Gate::allows('assessment.session.create', \$this->assessment_period_id)

LAPORAN: (1) file path, (2) list rules, (3) error jika ada."
```

### 10.4 Brief M

**Kriteria:** 1–3 komponen terkait, ~200–500 baris. Butuh CLAUDE.md +
ARCHITECTURE.md.

**Contoh — Vue component:**

```bash
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -f ".docs/ARCHITECTURE.md" \
  -- "Buat Vue component untuk assessment session list.

FILE: frontend/resources/js/modules/assessments/views/SessionListView.vue

Layout:
- Page title: 'Assessment Sessions'
- Breadcrumb: Assessment Periods > {period_name} > Sessions
- Search input (VTextField, ri-search-line)
- Filter status (VSelect: All/Draft/In Progress/Submitted/Reviewed/Approved/Cancelled)
- Data table dengan kolom: Employee Name, Period, Status (chip), Matrix Version, Action
- Action per row: View (ri-eye-line), Start (ri-play-line, hanya jika draft)
- Semua text UI dalam bahasa Inggris
- Handle loading, error, empty state

Data source: assessmentStore.sessions (array dari GET /api/assessment-periods/{id}/sessions)
Event saat klik View: router.push ke SessionDetailView dengan param session id
Event saat klik Start: assessmentStore.startSession(session.id)

Wajib: tidak ada console.log, ps-/pe- bukan pl-/pr-, PascalCase komponen di template,
2 spasi indent, no semicolon, trailing comma multiline.

LAPORAN: (1) file path, (2) key props dan emits, (3) ESLint error jika ada."
```

**Contoh — bug fix dengan root cause jelas:**

```bash
opencode run -m "opencode-go/deepseek-v4-flash" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -- "Bug fix di:
backend/Modules/Assessment/app/Services/AssessmentPlanService.php
method: generateSessions()
baris: ~85

Root cause: $plan->items tidak eager-loaded, menyebabkan N+1 query
dan missing relationship saat loop di generateSessions().

Fix:
1. Ubah query di baris ~85 dari:
   \$plan = AssessmentPlan::find(\$planId);
   menjadi:
   \$plan = AssessmentPlan::with(['items.employeePosition.employee',
     'items.employeePosition.matrixVersion'])->find(\$planId);

2. Update assertion di test:
   tests/Feature/AssessmentPlanTest.php
   method: test_it_generates_sessions_from_approved_plan
   Tambah assertion bahwa semua generated sessions punya employee_id yang cocok.

LAPORAN: (1) file changed, (2) test result (pass/fail + count), (3) error jika ada."
```

### 10.5 Brief L

**Kriteria:** Full layer atau full module, >500 baris. Butuh full context.
Pertimbangkan pecah menjadi beberapa Brief M berseri.

**Kapan Brief L aman (tidak dipecah):**
- Semua komponen interconnected dan tidak bisa di-commit secara terpisah
- Scope sudah sangat jelas — tidak ada ambiguitas tersisa
- Executor yang dipilih punya context limit yang cukup

**Contoh — Full CRUD layer:**

```bash
opencode run -m "opencode-go/deepseek-v4-flash" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -f ".docs/ARCHITECTURE.md" \
  -f ".docs/sprints/sprint-06-assessment-scoring.md" \
  -- "Implementasikan Assessment Session CRUD layer mengikuti pattern Controller→Service→Repository.

MODULE: backend/Modules/Assessment/

FILES YANG DIBUAT:
1. app/Models/AssessmentSession.php
   - fillable: assessment_period_id, employee_id, matrix_version_id, target_level_id,
     assessment_plan_item_id, status, snapshot fields (dept/section/family/profile)
   - relations: period(), employee(), matrixVersion(), participants(), planItem()
   - status enum: draft|in_progress|submitted|reviewed|approved|cancelled

2. app/Repositories/Interfaces/AssessmentSessionRepositoryInterface.php
   - methods: findById, findByPeriod, create, update, updateStatus, delete

3. app/Repositories/EloquentAssessmentSessionRepository.php
   - implement interface di atas
   - eager load: participants, employee, matrixVersion

4. app/Services/AssessmentSessionService.php
   - getByPeriod(\$periodId, User \$actor): scope check via UserOrganizationScope
   - createAdHoc(array \$data, User \$actor): create session tanpa plan_item
   - submit(\$sessionId, User \$actor): draft → in_progress, validasi peserta ada
   - Semua state change: atomic dengan pessimistic lock

5. app/Http/Controllers/AssessmentSessionController.php
   - index(\$periodId): GET /api/assessment-periods/{period}/sessions
   - store(StoreAssessmentSessionRequest): POST /api/assessment-sessions
   - show(\$id): GET /api/assessment-sessions/{id}
   - update(UpdateAssessmentSessionRequest, \$id): PUT
   - destroy(\$id): DELETE

6. app/Http/Resources/AssessmentSessionResource.php
   - expose: id, period_id, employee_id, employee_name, status, matrix_version_id,
     target_level_id, created_at

7. routes/api.php — tambahkan routes di atas

8. Providers/AssessmentServiceProvider.php — bind repository interface

KONVENSI WAJIB (dari CLAUDE.md):
- declare(strict_types=1) di semua file PHP
- protected \$fillable (bukan protected array \$fillable)
- ApiResponse::success() / ApiResponse::error() untuk semua response
- Inject interface bukan concrete class di constructor
- Max 200 baris per class, max 20 baris per method

LAPORAN: (1) semua file created dengan path, (2) php artisan test --filter=AssessmentSession
result (pass/fail + count), (3) error jika ada."
```

**Pola Brief L → M berseri (lebih aman):**

```
Brief M-1: Migration + Model + Factory
  → commit: '[feat] sprint 06 — assessment session migration + model'

Brief M-2: Repository interface + Eloquent implementation
  → commit: '[feat] sprint 06 — assessment session repository'

Brief M-3: Service layer (business logic + state transitions)
  → commit: '[feat] sprint 06 — assessment session service'

Brief M-4: Controller + Routes + Resource + ServiceProvider
  → commit: '[feat] sprint 06 — assessment session controller + routes'

Brief M-5: Feature tests (semua endpoint + edge case)
  → commit: '[test] sprint 06 — assessment session feature tests'
```

Jika M-3 gagal, M-1 dan M-2 sudah committed — M-3 bisa di-retry dari
kondisi yang diketahui.

### 10.6 Yang Tetap di Claude (Jangan Didelegasikan)

| Task | Kenapa tetap di Claude |
|------|----------------------|
| Diagnosa root cause bug | Perlu eksplorasi — kondisi belum diketahui |
| Arsitektur, desain service, trade-off | Keputusan milik Claude/developer |
| Code review (`/code-review`) | Judgment multi-dimensi |
| Verifikasi output executor | Claude yang memutuskan apakah hasil benar |
| Menulis brief untuk task besar | Brief yang baik butuh Claude |

**Pola diagnosa → delegate fix:**

Setelah root cause ditemukan, tulis Brief M/L dengan kondisi spesifik
(file + baris + kondisi + fix yang diharapkan) dan delegate eksekusi.
Jangan selesaikan fix di Claude jika sudah bisa dispec-kan.

---

## 11. COLLABORATION.md

Buat file `.docs/COLLABORATION.md` yang menjadi **dokumen kanonikal**
semua aturan delegasi. File ini dibaca `/start-session` dan direferensikan
dari CLAUDE.md.

### 11.1 Template Lengkap

Berikut template yang bisa langsung dipakai, sesuaikan bagian
project-specific:

````markdown
# COLLABORATION.md — [Project] Executor Workflow

Claude bertindak sebagai **orkestrator** untuk task large-scope dan
semua keputusan arsitektur. OpenCode, Gemini CLI, dan GitHub
Copilot CLI bertindak sebagai **executor** — Claude menentukan mana
yang dipakai per task berdasarkan ketersediaan dan sisa token.

> **Tujuan utama: hemat token.** Claude (Sonnet, fixed — tidak ganti
> model) untuk analisis dan judgment. Executor untuk implementasi.
>
> **Prinsip sequential:** Satu executor selesai + diverifikasi Claude
> → baru executor berikutnya jalan. Tidak ada eksekusi paralel antar
> executor kecuali kondisi ketat (lihat §Isolasi Task).

---

## Token Economics

Claude (Sonnet): input $3/1M, output $15/1M — 5× lebih mahal.

| Task | Output tokens Claude langsung | Via executor | Hemat |
|------|-------------------------------|--------------|-------|
| Git commit (XS) | ~80 | ~130 | overhead — tetap delegasi |
| Migration ~150 baris (S) | ~600 | ~80 | 87% |
| Feature test ~400 baris (M) | ~1.600 | ~120 | 93% |
| Full CRUD layer ~1.500 baris (L) | ~6.000 | ~300 | 95% |

Hidden savings: kode yang Claude generate masuk context window dan
dibayar ulang di setiap pesan berikutnya. Delegasi = context tetap
lean sepanjang sprint.

---

## Executor Reference

### OpenCode — Default

```bash
# XS — tanpa context
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions "brief"

# S — CLAUDE.md saja
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" -- "brief"

# M — CLAUDE.md + ARCHITECTURE.md + sprint file
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -f ".docs/ARCHITECTURE.md" \
  -f ".docs/sprints/sprint-XX-name.md" \
  -- "brief"

# L — full context + model reasoning lebih dalam
opencode run -m "opencode-go/deepseek-v4-flash" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -f ".docs/ARCHITECTURE.md" \
  -f ".docs/LESSONS-ARCHIVE.md" \
  -f ".docs/sprints/sprint-XX-name.md" \
  -- "brief"
```

| Model | Gunakan untuk |
|-------|---------------|
| `opencode-go/mimo-v2.5-pro` | Default — git, CRUD boilerplate, test sederhana, UI |
| `opencode-go/deepseek-v4-flash` | Bug kompleks, full module, test dengan banyak edge case |

---

### Gemini CLI — Large Context / Fallback

```bash
# XS — tanpa context
gemini -p "brief" -y

# S/M/L — instruksi baca context di awal prompt
gemini -p "Baca file-file ini dulu sebelum mengerjakan task:
- .claude/CLAUDE.md              (konvensi kode wajib)
- .docs/ARCHITECTURE.md          (patterns backend+frontend)    [M dan L]
- .docs/LESSONS-ARCHIVE.md       (lessons dari sprint sebelumnya) [L saja]
- .docs/sprints/sprint-XX-*.md   (spec sprint terkait)          [M dan L]

Task: [brief]" -y

# Override model
gemini -m gemini-2.5-flash -p "brief" -y
```

| Model | Gunakan untuk |
|-------|---------------|
| `gemini-2.5-pro` *(default)* | Large context (>5 file), full module, large refactor |
| `gemini-2.5-flash` | Task ringan, respon lebih cepat |
| `gemini-2.5-flash-lite` | Sangat ringan, hemat quota |

Auth: Google OAuth — credentials di `~/.gemini/`. Rate limit: free tier
punya quota RPM + daily. Gemini CLI retry otomatis saat RPM habis.

---

### GitHub Copilot CLI — GitHub Context / Reasoning Depth

```bash
# S/M/L — instruksi baca context di awal prompt
gh copilot -- --allow-all -s -p "Baca file-file ini dulu:
- .claude/CLAUDE.md              (konvensi kode wajib)
- .docs/ARCHITECTURE.md          (patterns backend+frontend)    [M dan L]
- .docs/sprints/sprint-XX-*.md   (spec sprint terkait)          [M dan L]

Task: [brief]"

# Dengan GitHub context (PR, issue, repo API)
gh copilot -- --allow-all -s --add-github-mcp-tool '*' -p "brief"

# Dengan reasoning depth
gh copilot -- --allow-all -s --effort high -p "brief"

# Resume session sebelumnya
gh copilot -- --allow-all -s --resume -p "brief"
```

---

## Format LAPORAN — Wajib di Setiap Brief

Tambahkan selalu di akhir brief yang dikirim ke executor:

```
LAPORAN: Hanya tampilkan:
(1) file yang dibuat/diubah dengan path lengkap
(2) hasil test: pass/fail + jumlah test
(3) error jika ada
Jangan tampilkan isi file atau output verbose lainnya.
```

---

## Kriteria Delegasi

**Batas delegasi bukan jumlah file — tapi seberapa banyak judgment yang
dibutuhkan.** Brief yang menghilangkan semua judgment call = bisa
didelegasikan seberapapun besarnya.

### Bisa Didelegasikan

| Task | Size | Executor default |
|------|------|-----------------|
| Git commit & push | XS | OpenCode `mimo-v2.5-pro` |
| Linting + auto-fix | XS | OpenCode `mimo-v2.5-pro` |
| Single-file UI fix | XS | OpenCode `mimo-v2.5-pro` |
| Migration file | S | OpenCode `mimo-v2.5-pro` |
| Seeder / factory | S | OpenCode `mimo-v2.5-pro` |
| FormRequest | S | OpenCode `mimo-v2.5-pro` |
| API Resource | S | OpenCode `mimo-v2.5-pro` |
| Feature test (satu controller) | M | OpenCode `mimo-v2.5-pro` |
| Vue component / view | M | OpenCode `mimo-v2.5-pro` |
| Bug dengan root cause jelas | M | OpenCode `deepseek-v4-flash` |
| Full CRUD layer (Controller+Service+Repository+Routes) | L | OpenCode `deepseek-v4-flash` |
| Full frontend module (service+store+views+routes) | L | OpenCode `deepseek-v4-flash` |
| Feature test seluruh module | L | OpenCode `deepseek-v4-flash` |
| Task butuh >5 file context sekaligus | L | Gemini `gemini-2.5-pro` |
| Large refactor lintas module | L | Gemini `gemini-2.5-pro` |
| OpenCode rate-limited, task rutin | XS–M | Gemini `gemini-2.5-flash` |
| Task butuh GitHub context (PR, issue) | any | Copilot `--add-github-mcp-tool` |
| Task butuh reasoning sangat dalam | M–L | Copilot `--effort high` |

### Tetap di Claude

| Task | Alasan |
|------|--------|
| Diagnosa root cause bug | Perlu eksplorasi — kondisi belum diketahui |
| Arsitektur, desain service, trade-off | Keputusan milik Claude |
| Code review | Judgment multi-dimensi |
| Verifikasi output executor | Claude yang decide apakah benar |
| Menulis brief untuk delegasi besar | Brief yang baik butuh Claude |

**Pola "diagnosa → delegate fix":** Setelah root cause ditemukan, tulis
Brief M/L dengan kondisi spesifik dan delegate eksekusi. Jangan selesaikan
fix di Claude jika sudah bisa dispec-kan.

---

## Pola Hemat Token Tambahan

**Exploration delegation (Gemini):** Sebelum Brief L, jika Claude perlu
memahami kondisi banyak file sekaligus — delegate eksplorasi ke Gemini,
minta ringkasan (max 80 baris). Claude baca ringkasan, bukan semua file.

```bash
gemini -p "Baca file-file berikut dan laporkan kondisi ringkas (max 80 baris):
- backend/Modules/Assessment/app/Services/
- backend/Modules/Assessment/routes/api.php
- database/migrations/*assessment*

Laporkan: method yang sudah ada, routes terdaftar, migration steps yang ada.
Jangan tampilkan isi lengkap file — ringkasan saja." -y
```

**Implement + test sekaligus (Brief L):** Sertakan test spec langsung
di Brief L yang sama. Satu putaran executor menghasilkan implementasi +
test.

---

## Load Balancing

| Situasi | Executor |
|---------|----------|
| OpenCode quota tersedia, task rutin | OpenCode `mimo-v2.5-pro` |
| OpenCode rate-limited, task ringan | Gemini `gemini-2.5-flash` |
| OpenCode rate-limited, task besar / banyak context | Gemini `gemini-2.5-pro` |
| Task butuh GitHub context | Copilot `--add-github-mcp-tool '*'` |
| Task butuh reasoning sangat dalam | Copilot `--effort high` |
| OpenCode + Gemini keduanya limit | Copilot `--allow-all` |
| Copilot tidak available | Gemini `gemini-2.5-pro` |

---

## Kegagalan Mid-Task

Executor bisa berhenti di tengah karena token/context limit, daily
quota habis, atau timeout. Deteksi kondisi ini — jangan asumsikan
selesai hanya karena executor berhenti tanpa error eksplisit.

### Deteksi

- Output terpotong tiba-tiba tanpa `LAPORAN:` di akhir
- File yang dibuat lebih sedikit dari yang ada di brief
- Gemini: retry loop tanpa akhir lalu exit non-zero
- OpenCode: "context length exceeded" atau silent truncation

**Langkah pertama setelah executor berhenti tidak normal:**

```bash
git status        # lihat file mana yang berubah
git diff          # cek partial / syntax error
git diff --cached # file yang sudah staged
```

### Recovery Protocol

| Kondisi | Tindakan |
|---------|----------|
| File tidak lengkap / syntax error, belum ada migration | `git checkout .` → re-delegate dengan scope lebih kecil |
| File tidak lengkap, migration sudah dijalankan | Reset PHP per file: `git checkout -- path/file.php`. Biarkan migration. Re-delegate PHP saja. |
| Semua file ada, test belum jalan | Brief XS: "jalankan `php artisan test --filter=X`. LAPORAN: hasilnya" |
| Git dirty, kondisi tidak jelas | `git stash` → `git status` → periksa per file → putuskan rollback atau lanjut |
| Executor gagal berulang (task terlalu besar) | Pecah Brief L → beberapa Brief M berseri |

### Prevention: Brief L → Beberapa Brief M

```
Brief M-1: migrations + models          → commit
Brief M-2: repository + service         → commit
Brief M-3: controller + routes + resource → commit
Brief M-4: tests                        → commit
```

Jika M-2 gagal, M-1 sudah committed — M-2 bisa di-retry dari titik
yang diketahui. Lebih reliable daripada satu Brief L all-or-nothing.

---

## Isolasi Task

**Executor selalu dijalankan sequential — satu selesai + diverifikasi
Claude, baru berikutnya jalan.**

| Shared state | Risiko paralel |
|-------------|----------------|
| File yang sama | Overwrite satu sama lain |
| Git operations | Dua commit bersamaan → conflict |
| `php artisan migrate` / `test` | Race condition di test database |
| Migration numbering | Dua executor buat step yang sama → duplicate |

**Paralel hanya boleh jika semua terpenuhi:**
1. File yang disentuh tidak ada yang overlap
2. Tidak ada git commit, artisan migrate, atau artisan test
3. Task berupa pure-write file baru (bukan edit existing)

---

## Alur Kerja

```
User → Claude (task apapun)
  │
  ├─ [explore] perlu pahami kondisi repo sebelum plan?
  │     └─ delegate ke Gemini: "baca X file, laporkan ringkas max 80 baris"
  │
  ├─ [plan] Claude analisis, desain arsitektur, tulis brief
  │
  ├─ [delegate] brief siap → pilih executor → jalankan via Bash
  │     ├─ OpenCode quota tersedia + task rutin → opencode run -m mimo-v2.5-pro
  │     ├─ Banyak context / OpenCode limit → gemini -p "..." -y
  │     ├─ GitHub context / reasoning → gh copilot -- --allow-all -s
  │     ├─ executor selesai → Claude baca LAPORAN → verifikasi
  │     └─ executor gagal → git status → recovery protocol
  │
  ├─ [debug] Claude diagnosa root cause
  │     └─ root cause jelas → Brief fix → delegate (jangan fix di Claude)
  │
  └─ [review] Claude verifikasi output sebelum lanjut ke task berikutnya
```

**Aturan orchestration:**
- User hanya ngobrol dengan Claude — tidak perlu tahu executor mana
- Claude pilih executor per task, bisa mix dalam satu sprint
- Setiap output executor wajib dibaca dan dilaporkan
- Jika output salah → Claude koreksi sebelum lanjut
````

---

## 12. Verifikasi Output Executor

**Jangan rubber-stamp output executor.** Claude sebagai orkestrator
harus benar-benar memeriksa hasil — bukan hanya laporkan apa yang
executor tulis.

### 12.1 Checklist per Jenis Task

**Git commit:**
- Hash commit ada
- Files changed sesuai scope task (tidak lebih, tidak kurang)
- Insertions/deletions angkanya masuk akal
- Commit message mengikuti format `[type] deskripsi`

**Linting:**
- File yang di-fix sesuai yang diharapkan
- Ada ESLint error yang tidak bisa auto-fix? Perlu manual fix?
  - `sonarjs/no-collapsible-if` → merge nested if dengan `&&`
  - `vue/prefer-true-attribute-shorthand` → ganti `:prop="true"` → `prop`

**Feature test:**
- Jumlah test ≥ jumlah scenario yang ada di brief
- Semua test pass — tidak ada yang skip atau pending
- Assertion count masuk akal (bukan sekadar status code saja)

**Code task (migration, controller, service, Vue component):**
- Semua file di `FILES:` brief ada di output
- Function signatures sesuai yang dispec-kan
- Business rules diimplementasi (cek dari brief)
- Konvensi CLAUDE.md terpenuhi:
  - `declare(strict_types=1)` di semua file PHP
  - `protected $fillable` (bukan `protected array $fillable`)
  - Max 200 baris per class, max 20 baris per method
  - Tidak ada `console.log`, `dd()`, `var_dump()`
  - Tidak ada komentar TODO
- Tidak ada syntax error (cek dari `php artisan test` atau linter result)

### 12.2 Jika Ada Ketidaksesuaian

Jangan lanjut ke task berikutnya. Dua opsi:

**Opsi 1 — Re-delegate (task kecil):**
```bash
# Brief koreksi spesifik
opencode run -m "opencode-go/mimo-v2.5-pro" \
  --dangerously-skip-permissions \
  -f ".claude/CLAUDE.md" \
  -- "File backend/Modules/Assessment/app/Services/AssessmentSessionService.php
ada protected array \$fillable di baris 12. Ganti menjadi protected \$fillable
(tanpa type hint — Eloquent requirement).
LAPORAN: file changed, baris yang diubah."
```

**Opsi 2 — Claude fix langsung (jika sangat kecil):**
Gunakan Edit tool untuk perubahan 1–3 baris yang sudah jelas.

---

## 13. Alur Kerja End-to-End

Begini semua komponen bekerja bersama di satu sprint:

```
Developer buka Claude Code
│
▼
/start-session
│  Claude baca .context/MEMORY.md     ← sprint status + feedback lalu
│  Claude baca sprint aktif           ← apa yang perlu dikerjakan
│  Claude baca COLLABORATION.md       ← aturan delegasi
│  Claude verifikasi OpenCode/Gemini/Copilot
│
▼  "Session Ready: Sprint 06, OpenCode ready, Gemini ready, Copilot ready"
│
▼
Developer: "kerjakan assessment session backend"
│
├─ Claude: cek brief perlu berapa context
│          → L task: pakai deepseek + full context
│
├─ Claude: explore dulu? tidak perlu — spec sudah jelas di sprint file
│
├─ Claude: tulis Brief M-1 (migration + model)
│
├─ Claude → Bash: opencode run -m deepseek-v4-flash -f CLAUDE.md
│            -f ARCHITECTURE.md -f sprint-06.md -- "Brief M-1..."
│
│  [OpenCode berjalan ~2-5 menit]
│
├─ Claude: baca LAPORAN output
│          verifikasi: migration ada, model ada, test M-1 pass
│
├─ Claude → Bash: opencode run ... "Brief M-2 (repository + service)"
│
│  [OpenCode berjalan ~2-5 menit]
│
├─ Claude: verifikasi M-2 ✓
│
├─ ... (M-3, M-4, M-5)
│
├─ Claude → Bash: opencode ... "Run linting + commit all sprint 06 work"
│
└─ Claude update memory:
   Write .context/project_sprint-status.md
   └─ Sprint 06 in progress, assessment session done, participants next
```

**Memory ditulis kapan dalam sprint:**

| Timing | Memory yang ditulis |
|--------|---------------------|
| Awal sprint baru | Update `project_sprint-status.md` (sprint aktif berubah) |
| Koreksi / feedback diberikan | `feedback_{topik}.md` baru |
| Keputusan arsitektur disepakati | Update `project_{topik}.md` |
| Sprint selesai | Update `project_sprint-status.md` (deliverables + commit hash) |

---

## 14. Troubleshooting

### Junction tidak bisa dibuat (Windows)

```
Access to the path '.context' is denied
```

Solusi:
1. Jalankan PowerShell sebagai Administrator, atau
2. Aktifkan Developer Mode: Settings → System → Developer options →
   Developer Mode (ON)

### Junction menunjuk ke path yang salah

```powershell
# Cek target junction
Get-Item .context | Select-Object LinkType, Target

# Hapus junction yang salah
[System.IO.Directory]::Delete(".context")

# Buat ulang dengan target yang benar
New-Item -ItemType Junction -Path ".context" -Target "path\yang\benar"
```

### `opencode: command not found` atau `opencode --version` error

```bash
npm install -g opencode-ai

# Restart terminal setelah install
# Jika masih tidak ketemu:
npm config get prefix   # cek path npm global
# Tambahkan {prefix}/bin ke PATH
```

### OpenCode selalu timeout atau "context length exceeded"

Task terlalu besar untuk satu Brief L. Pecah menjadi Brief M berseri.
Atau switch ke Gemini yang context limit-nya lebih besar.

### `gemini: command not found`

```bash
npm install -g @google/gemini-cli
gemini --version

# Jika masih tidak ketemu:
npm config get prefix
```

### Gemini: "No credentials found" atau auth error

```bash
# Login ulang interaktif
gemini
# Browser terbuka → login Google → selesai
```

### Gemini: Daily quota habis

```
Error: 429 Too Many Requests (daily limit)
```

Switch ke OpenCode atau Copilot untuk hari itu. Gemini free tier punya
daily limit yang reset setiap tengah malam UTC.

### `gh copilot: command not found`

```bash
gh extension install github/gh-copilot
gh copilot --version
```

### Copilot: "401 Unauthorized"

```bash
gh auth status          # cek apakah masih login
gh auth login           # login ulang jika perlu
```

### `/start-session` tidak dikenali sebagai command

Pastikan path file benar — nama command = nama file tanpa ekstensi:

```
.claude/commands/start-session.md   → /start-session  ✓
.claude/commands/start_session.md   → /start_session  ✗ (underscore beda)
```

### Memory tidak terbaca Claude

`MEMORY.md` di-truncate setelah 200 baris. Cek panjangnya:

```bash
# Windows
(Get-Content .context\MEMORY.md).Count

# macOS/Linux
wc -l .context/MEMORY.md
```

Jika > 200: hapus atau gabung memory yang sudah tidak relevan.

### Memory sudah tersimpan tapi tidak muncul di sesi berikutnya

Pastikan file ada di direktori yang benar (bukan di `.context/` yang
bukan junction):

```bash
# Cek direktori target junction
Get-Item .context | Select-Object Target    # Windows
readlink .context                           # macOS/Linux

# Cek file ada di target, bukan di junction layer
ls (Get-Item .context).Target               # Windows
ls $(readlink .context)                     # macOS/Linux
```

---

## Checklist Setup Lengkap

Jalankan checklist ini sekali di awal project:

```
Memory System
─────────────
□ Slug project dihitung dengan benar:
    path separator → dash, drive letter → X--
□ ~/.claude/projects/{slug}/memory/ direktori ada
□ .context/ junction dibuat dan menunjuk ke direktori di atas
□ .context/ masuk .gitignore
□ .context/MEMORY.md ada (bisa kosong di awal)
□ Verifikasi: Get-Item .context → LinkType=Junction

CLAUDE.md
─────────
□ Section "Urutan Baca File" ditambahkan di atas
□ .context/MEMORY.md ada di urutan baca nomor 1
□ Section "Collaboration Workflow" ditambahkan
□ Executor reference table ada (OpenCode/Gemini/Copilot)

/start-session Command
──────────────────────
□ .claude/commands/ direktori ada
□ .claude/commands/start-session.md dibuat dengan konten lengkap
□ /start-session berhasil dijalankan dan menghasilkan session brief

Executor Installation
─────────────────────
□ opencode terinstall:  opencode --version
□ gemini terinstall:    gemini --version
□ gh copilot terinstall: gh copilot --version

Executor Authentication
───────────────────────
□ OpenCode: pernah dijalankan + OAuth selesai
□ Gemini: gemini (interaktif) + Google login selesai
          credentials ada di ~/.gemini/
□ Copilot: gh auth login selesai
           gh auth status → "Logged in to github.com"

Executor Connectivity Test
──────────────────────────
□ OpenCode test:
    opencode run -m "opencode-go/mimo-v2.5-pro"
      --dangerously-skip-permissions "reply: OpenCode ready."
    → output: "OpenCode ready."

□ Gemini test:
    gemini -p "reply: Gemini ready." -y
    → output: "Gemini ready."

□ Copilot test:
    gh copilot -- --allow-all -s -p "reply: Copilot ready."
    → output: "Copilot ready."

COLLABORATION.md
────────────────
□ .docs/COLLABORATION.md dibuat dari template §11.1
□ Sections ada: Token Economics, Executor Reference, Format LAPORAN,
    Kriteria Delegasi, Load Balancing, Kegagalan Mid-Task, Isolasi Task

Final Validation
────────────────
□ Jalankan /start-session → berhasil melaporkan session brief
□ Session brief menampilkan sprint aktif yang benar
□ Semua executor status "ready"
□ Memory dari sesi lalu terbaca (jika sudah ada)
```

―――  Modul 20 • AI Project Enhancement • Selesai  ―――
