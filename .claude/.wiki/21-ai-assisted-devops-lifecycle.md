# AI-Assisted DevOps Lifecycle — Planning to Commit with Audit Trail

> Source: EO-PIMS Implementation Guide (v20260605)

> Modul ini melanjutkan Modul 20 (AI-Assisted Enhancement). Jika Modul 20 membahas
> memory sistem dan executor collaboration, Modul 21 menutup loop terakhir:
> **menghubungkan sprint planning, eksekusi kode, dan task tracking menjadi satu
> siklus terpadu dengan audit trail penuh dari Azure DevOps board hingga git commit.**

---

## Daftar Isi

| # | Topik | Deskripsi |
|---|-------|-----------|
| 1 | Masalah yang Dipecahkan | Kenapa traceability penting dan apa yang hilang tanpa ini |
| 2 | Arsitektur Sistem | Tiga komponen: Claude + OpenCode + Azure DevOps |
| 3 | Prerequisites | Tools dan akses yang dibutuhkan |
| 4 | Setup Part 1 — Install Tools | Claude Code, OpenCode, 9Router |
| 5 | Setup Part 2 — Azure DevOps PAT | Cara buat Personal Access Token |
| 6 | Setup Part 3 — File .env.claude | Simpan credentials lokal |
| 7 | Setup Part 4 — Seed Sprint ke Azure | Claude otomatis buat board dari sprint doc |
| 8 | Workflow Harian | Step-by-step satu siklus penuh |
| 9 | Format Commit — Wajib AB#ID | Konvensi commit dengan traceability |
| 10 | Audit Trail — Board to Commit | Cara baca jejak lengkap per task |
| 11 | State Transitions | To Do → Doing → Done — siapa yang update |
| 12 | Strict Conventions | Tiga aturan yang tidak boleh dilanggar |
| 13 | Setup Git Convention untuk Claude | Cara definisi + enforce konvensi git agar diikuti Claude |
| 14 | Troubleshooting | Error umum dan solusinya |

---

## 1. Masalah yang Dipecahkan

### Masalah — Gap antara planning dan eksekusi

Tim yang sudah mengimplementasikan Modul 20 (memory + executor) berhasil mempercepat
eksekusi kode. Tapi masih ada satu gap: **tidak ada visibilitas status pekerjaan secara
real-time**, dan tidak ada cara untuk menelusuri **commit mana yang mengerjakan task mana**.

Tanpa sistem ini:
- Lead tidak tahu task mana yang sedang dikerjakan hari ini
- Jika ada bug, tidak bisa langsung tahu commit mana yang memperkenalkannya
- Sprint review harus tanya-tanya manual ke developer
- Tidak ada catatan kapan sebuah task mulai dan selesai dikerjakan

### Solusinya — DevOps Lifecycle Loop

```
Sprint Doc (.docs/sprints/)
        │
        ▼
Azure DevOps Board          ←── Claude seed otomatis saat sprint baru
  Epic → Issue → Task
        │
        ▼
   Claude + OpenCode         ←── Task → Doing saat brief dikirim
   (eksekusi kode)
        │
        ▼
  git commit [progress]      ←── Sprint doc dicentang → commit → AB#ID
        │
        ▼
  Azure Board → Done         ←── Claude update state setelah verifikasi
        │
        ▼
  Audit Trail Lengkap        ←── Board + commit history + timeline work item
```

Hasilnya: setiap task di board bisa di-trace ke commit yang spesifik, dan setiap
commit bisa di-trace kembali ke work item yang mengotorisasinya.

---

## 2. Arsitektur Sistem

### Tiga Komponen

| Komponen | Peran | Tool |
|---|---|---|
| **Claude Code** | Orchestrator | Claude Code CLI — baca sprint doc, tulis brief, verifikasi, update Azure |
| **OpenCode via 9Router** | Executor | OpenCode CLI — implementasi kode berdasarkan brief Claude |
| **Azure DevOps** | Tracker | Board, iteration, work items, audit trail |

### Hierarki Work Item (Basic Process)

```
Epic
 └── Issue  (= satu Brief)
      ├── Task  (= satu unit pekerjaan)
      ├── Task
      └── Task
```

Contoh nyata Sprint 01:
```
Epic [291]: Sprint 01 — Organization + Employee
  Issue [292]: Brief 1 — Backend: Organization Module
    Task [293]: Migration: create_departments_table
    Task [294]: Migration: create_sections_table
    Task [295]: Department & Section models, factories, seeders
    Task [296]: OrganizationRepositoryInterface + EloquentOrganizationRepository
    Task [297]: OrganizationService (CRUD department + section)
    Task [298]: DepartmentController + SectionController + FormRequests + Resources
    Task [299]: OrganizationServiceProvider + module.json + routes/api.php
    Task [300]: Feature tests: DepartmentController + SectionController
  Issue [301]: Brief 2 — Backend: Employee Module
    ...
```

### Siapa Yang Melakukan Apa

| Aksi | Pelaku |
|---|---|
| Buat sprint doc (`.docs/sprints/sprint-XX.md`) | Developer / Lead |
| Seed sprint + work items ke Azure | Claude |
| Set task → Doing | Claude (saat brief dikirim ke OpenCode) |
| Implementasi kode | OpenCode (executor) |
| Verifikasi output, centang checklist | Claude |
| Commit dengan AB#ID | Claude |
| Set task + issue → Done | Claude |
| Pantau board | Lead / Tim |

**Developer tidak perlu update Azure manual — Claude yang handle semua state transitions.**

---

## 3. Prerequisites

### 3.1 Tools yang Harus Terinstall

| Tool | Versi | Cara Cek |
|---|---|---|
| Node.js | >= 18 | `node -v` |
| Claude Code CLI | Latest | `claude --version` |
| OpenCode | Latest | `opencode --version` |
| Git | Latest | `git -v` |
| PowerShell | >= 7 | `pwsh --version` |
| GitHub Copilot CLI | Latest | `gh copilot --version` *(opsional)* |

### 3.2 Akses yang Dibutuhkan

- **Azure DevOps account** dengan akses ke project tim
- **Personal Access Token (PAT)** — dibuat di langkah §5
- **Anthropic account** untuk Claude Code CLI

### 3.3 Yang Sudah Harus Ada (dari Modul 20)

- Memory sistem aktif di `.claude/memory/`
- Sprint doc ada di `.docs/sprints/sprint-XX.md`
- `/start-session` command berfungsi
- OpenCode + 9Router terinstall

---

## 4. Setup Part 1 — Install Tools

### 4.1 Install Claude Code CLI

```powershell
# Install global via npm
npm install -g @anthropic-ai/claude-code

# Verifikasi
claude --version
```

Saat pertama dijalankan di folder project, browser akan terbuka untuk login OAuth
dengan akun Anthropic. Ikuti langkah di browser, lalu kembali ke terminal.

```powershell
# Masuk ke folder project dan jalankan Claude
cd "D:\Apps\[nama-project]"
claude
```

### 4.2 Install OpenCode

```powershell
# Install global via npm
npm install -g opencode-ai

# Verifikasi
opencode --version
```

### 4.3 Setup 9Router

9Router adalah proxy model yang mengarahkan request ke berbagai AI model (Claude, GPT, dsb.)
berdasarkan konfigurasi `combo` yang dipilih. OpenCode menggunakan 9Router sebagai model backend.

```powershell
# Start 9Router (simpan di background, port 20128)
9router -n

# Verifikasi — harus return list model
Invoke-WebRequest -Uri "http://localhost:20128/v1/models" -UseBasicParsing | Select-Object -ExpandProperty Content
```

> **Tip:** 9Router harus distart ulang setiap kali PC restart. Tambahkan ke startup
> jika ingin otomatis: Task Scheduler → action: `9router -n` saat login.

### 4.4 Start OpenCode Serve

OpenCode harus berjalan dalam mode `serve` agar Claude bisa kirim brief via HTTP API.
**Jangan gunakan mode CLI langsung** — brief panjang akan terpotong di newline.

```powershell
# Start opencode serve di port 4099 (background)
$exe = "C:\nvm4w\nodejs\node_modules\opencode-ai\bin\opencode.exe"
Start-Process -FilePath $exe `
  -ArgumentList @("serve","--port","4099") `
  -RedirectStandardOutput "C:\Users\$env:USERNAME\AppData\Local\Temp\oc-serve.txt" `
  -RedirectStandardError  "C:\Users\$env:USERNAME\AppData\Local\Temp\oc-serve-err.txt" `
  -NoNewWindow -WorkingDirectory "D:\Apps\[nama-project]"

# Verifikasi (tunggu ~5 detik)
Start-Sleep -Seconds 5
(Invoke-WebRequest -Uri "http://localhost:4099" -UseBasicParsing -TimeoutSec 3).StatusCode
# Harus return: 200
```

> **Catatan path:** Sesuaikan path `$exe` jika Node.js diinstall di lokasi berbeda.
> Cek dengan: `(Get-Command opencode).Source`

---

## 5. Setup Part 2 — Azure DevOps PAT

Personal Access Token (PAT) adalah kredensial yang digunakan Claude untuk mengakses
Azure DevOps API tanpa username/password.

### 5.1 Cara Membuat PAT

1. Buka **Azure DevOps** → `https://dev.azure.com/[org-name]`
2. Klik **foto profil** di pojok kanan atas → **Personal Access Tokens**
3. Klik **+ New Token**
4. Isi form:
   - **Name:** `claude-devops-[nama-project]` *(deskriptif agar mudah diidentifikasi)*
   - **Organization:** pilih organisasi yang sesuai
   - **Expiration:** maksimal 180 hari *(catat tanggal expired!)*
   - **Scopes:** pilih **Custom defined**, lalu centang:
     - `Work Items` → **Read & Write**
     - `Project and Team` → **Read**
5. Klik **Create**
6. **SALIN TOKEN SEKARANG** — token hanya tampil sekali, tidak bisa dilihat lagi

> ⚠️ **Penting:** Token yang expired akan menyebabkan error `401 Unauthorized` saat
> Claude mencoba update Azure. Jika ini terjadi, buat PAT baru dan update `.env.claude`.

---

## 6. Setup Part 3 — File .env.claude

File `.env.claude` menyimpan credentials Azure DevOps secara lokal.
File ini **sudah ada di `.gitignore`** — tidak akan masuk repository.

### 6.1 Buat File .env.claude

```powershell
# Di root project
$content = @"
AZURE_DEVOPS_ORG=https://dev.azure.com/[nama-org]
AZURE_DEVOPS_PROJECT=[Nama Project]
AZURE_DEVOPS_PAT=[token-yang-sudah-disalin]
"@
Set-Content -Path "D:\Apps\[nama-project]\.env.claude" -Value $content -Encoding UTF8
```

Atau buat manual — buat file `.env.claude` di root project dengan isi:

```
AZURE_DEVOPS_ORG=https://dev.azure.com/nama-org
AZURE_DEVOPS_PROJECT=Nama Project
AZURE_DEVOPS_PAT=AzAW...token...
```

### 6.2 Verifikasi Koneksi

Test koneksi ke Azure DevOps API:

```powershell
$cfg = Get-Content "D:\Apps\[nama-project]\.env.claude" | ConvertFrom-StringData
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes(":$($cfg.AZURE_DEVOPS_PAT)"))
$headers = @{ Authorization = "Basic $b64" }
$org = $cfg.AZURE_DEVOPS_ORG

$r = Invoke-WebRequest -Uri "$org/_apis/projects?api-version=7.1" -Headers $headers -UseBasicParsing
($r.Content | ConvertFrom-Json).value | Select-Object name, id
```

Output harus menampilkan daftar project di organisasi. Jika error `401` → cek PAT.
Jika error `404` → cek `AZURE_DEVOPS_ORG` URL.

---

## 7. Setup Part 4 — Seed Sprint ke Azure DevOps

Setelah sprint doc dibuat di `.docs/sprints/sprint-XX.md`, Claude otomatis membuat
seluruh struktur di Azure DevOps via API.

### 7.1 Struktur Sprint Doc yang Dibutuhkan

Sprint doc harus memiliki:
- Daftar **Brief** (misal: Brief 1, Brief 2, dst.)
- Setiap Brief berisi daftar **deliverables/tasks** yang jelas
- **Checklist** di bagian bawah untuk tracking progress

Contoh format checklist di sprint doc:
```markdown
## Checklist Selesai

- [ ] Migration 01: departments
- [ ] Migration 02: sections
- [ ] Backend Organization module (Brief 1)
- [ ] Backend Employee module (Brief 2)
- [ ] php artisan migrate clean
- [ ] php artisan test green
```

### 7.2 Cara Seed Sprint

Jalankan `/start-session` di Claude Code. Claude akan:
1. Membaca sprint doc
2. Membuat **Iteration** (sprint) di Azure dengan tanggal start/finish
3. Membuat **Epic** untuk sprint keseluruhan
4. Membuat **Issue** untuk setiap Brief
5. Membuat **Task** untuk setiap deliverable di dalam Brief

```
claude
/start-session
```

Setelah seed selesai, Claude melaporkan semua ID work item yang dibuat.
**Catat atau simpan ID ini** — Claude akan menyimpannya di memory otomatis.

### 7.3 Verifikasi Board

Buka Azure DevOps board dan pastikan semua work items muncul:

```
https://dev.azure.com/[org]/[project]/_boards/board/t/[team]/Issues
```

Semua task harus dalam state **To Do** sebelum sprint dimulai.

---

## 8. Workflow Harian

Berikut adalah siklus lengkap satu sesi kerja dari awal hingga akhir.

### Step 1 — Start Session

```
claude
/start-session
```

Claude akan:
- Cek 9Router running (start jika belum)
- Cek OpenCode serve running (start jika belum)
- Baca memory (MEMORY.md + file relevan)
- Laporkan sprint aktif, pending tasks, status Azure

### Step 2 — Pilih Brief yang Akan Dikerjakan

Claude membaca sprint doc dan menentukan brief mana yang belum selesai.
Brief dikerjakan **secara berurutan** — tidak boleh skip.

### Step 3 — Set Task → Doing di Azure

Sebelum mengirim brief ke OpenCode, Claude update semua task dalam Issue tersebut
ke state `Doing`:

```powershell
# Claude menjalankan ini secara otomatis — tidak perlu dilakukan manual
$cfg = Get-Content ".env.claude" | ConvertFrom-StringData
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes(":$($cfg.AZURE_DEVOPS_PAT)"))
$org = $cfg.AZURE_DEVOPS_ORG
$proj = [Uri]::EscapeDataString($cfg.AZURE_DEVOPS_PROJECT)
$h = @{ Authorization = "Basic $b64"; "Content-Type" = "application/json-patch+json" }

function Set-WIState($id, $state) {
  # WAJIB pakai UTF8.GetBytes — ConvertTo-Json encoding menyebabkan "invalid patch document"
  $bodyBytes = [System.Text.Encoding]::UTF8.GetBytes(
    '[{"op":"add","path":"/fields/System.State","value":"' + $state + '"}]'
  )
  Invoke-WebRequest -Uri "$org/$proj/_apis/wit/workitems/$id`?api-version=7.1" `
    -Method Patch -Headers $h -Body $bodyBytes -UseBasicParsing | Out-Null
  Write-Host "WI $id → $state"
}

# Contoh: set Issue 292 dan semua Task-nya → Doing
292, 293, 294, 295, 296, 297, 298, 299, 300 | ForEach-Object { Set-WIState $_ "Doing" }
```

### Step 4 — Kirim Brief ke OpenCode

Claude menulis brief teknis dan mengirimnya ke OpenCode via HTTP API:

```powershell
$baseUrl = "http://localhost:4099"

# Buat session
$wc = New-Object System.Net.WebClient
$wc.Headers.Add("Content-Type", "application/json")
$sess = $wc.UploadString("$baseUrl/session", "POST", '{"title":"s01-b1-nama-task"}') | ConvertFrom-Json
$sessId = $sess.id

# Kirim brief
$briefText = Get-Content 'C:\Temp\oc-brief-nama-task.txt' -Raw
$body = [ordered]@{
  parts = @(@{type="text"; text=$briefText})
  model = @{providerID="nineRouter"; modelID="large-context-combo"}
} | ConvertTo-Json -Depth 5 -Compress

$wc2 = New-Object System.Net.WebClient
$wc2.Headers.Add("Content-Type", "application/json")
try {
  $wc2.UploadString("$baseUrl/session/$sessId/message", "POST", $body) | Out-Null
} catch {
  if ($_.Exception.Message -match '500') {
    Write-Host "ERROR 500 — model tidak tersedia"
  } else {
    Write-Host "Warning: timeout — kemungkinan terkirim, lanjut poll"
  }
}
```

**Pilihan model berdasarkan ukuran task:**

| Ukuran Task | Contoh | Model |
|---|---|---|
| XS | 1–2 file, typo fix | Claude langsung (tidak delegasi) |
| S | 1–3 file baru, migration, seeder | `standard-combo` |
| S/M | 3+ file baru sekaligus | `reasoning-combo` |
| M | Vue component panjang >300 baris | `reasoning-combo` |
| L | 5+ file, full CRUD layer | `large-context-combo` |

### Step 5 — Poll Sampai OpenCode Selesai

```powershell
$maxTries = 72  # 12 menit maksimal
$try = 0
do {
  Start-Sleep -Seconds 10; $try++
  $msgs = (Invoke-WebRequest -Uri "$baseUrl/session/$sessId/message" -UseBasicParsing).Content | ConvertFrom-Json
  $lastAsst = $msgs | Where-Object { $_.info.role -eq "assistant" } | Select-Object -Last 1
  Write-Host "[$try/$maxTries] msgs=$($msgs.Count)"
} while (-not ($lastAsst -and $lastAsst.info.time.completed) -and $try -lt $maxTries)

# Tampilkan output
$msgs | Where-Object { $_.info.role -eq "assistant" } |
  ForEach-Object { $_.parts | Where-Object { $_.type -eq "text" } } |
  ForEach-Object { ($_.text -replace '<think>[\s\S]*?</think>', '').Trim() } |
  Where-Object { $_ }
```

### Step 6 — Verifikasi Output OpenCode

Claude membaca LAPORAN dari OpenCode dan memverifikasi:

```
LAPORAN:
- Files changed: [list file yang dibuat/dimodifikasi]
- Tests: [N passed / N total]
- Lint: [pint clean / N issues]
- Errors: [none / deskripsi error]
```

**Checklist verifikasi Claude:**
- [ ] Semua file di brief ada di LAPORAN
- [ ] Test count sesuai ekspektasi
- [ ] Pint clean
- [ ] Tidak ada error

Jika ada gap → catat di `.docs/delegatetasks/sprint-XX-nama.md` sebelum lanjut.

### Step 7 — Centang Checklist Sprint Doc + Commit

Claude mengupdate sprint doc, lalu commit:

```powershell
# Claude update checklist di sprint doc, contoh:
# - [ ] Backend Organization module (Brief 1)
# menjadi:
# - [x] Backend Organization module (Brief 1)

# Kemudian commit dengan format wajib
git add .docs/sprints/sprint-01-organization-employee.md
git commit -m @'
[progress] sprint-01 brief-1 done — Organization module backend AB#292 AB#293 AB#294 AB#295 AB#296 AB#297 AB#298 AB#299 AB#300
'@
```

### Step 8 — Update Azure DevOps → Done

```powershell
# Set semua Task + Issue → Done
292, 293, 294, 295, 296, 297, 298, 299, 300 | ForEach-Object { Set-WIState $_ "Done" }
```

### Step 9 — Lanjut Brief Berikutnya

Ulangi dari Step 2 untuk Brief 2, Brief 3, dst.

---

## 9. Format Commit — Wajib AB#ID

### Format

```
[progress] sprint-{N} brief-{N} done — {deskripsi singkat} AB#{issue-id} AB#{task-id} AB#{task-id} ...
```

### Contoh Nyata

```
[progress] sprint-01 brief-1 done — Organization module backend AB#292 AB#293 AB#294 AB#295 AB#296 AB#297 AB#298 AB#299 AB#300
```

```
[progress] sprint-01 brief-2 done — Employee module backend AB#301 AB#302 AB#303 AB#304 AB#305 AB#306 AB#307 AB#308
```

```
[progress] sprint-01 brief-3 done — Organization frontend views AB#309 AB#310 AB#311 AB#312 AB#313 AB#314
```

### Kenapa AB#ID?

`AB#` adalah prefix khusus yang dikenali Azure DevOps secara otomatis.
Saat commit dengan format ini di-push ke repository yang terhubung ke Azure:

1. Azure DevOps **otomatis membuat link** antara commit dan work item
2. Di halaman detail work item → tab **Development** → commit muncul di sana
3. Di halaman commit di Azure Repos → link ke work item muncul

> **Claude yang menulis commit ini** — developer tidak perlu hafal ID work item.
> Claude membaca ID dari memory (`reference_azure-devops.md`) yang disimpan saat seed.

### ID yang Wajib Dicantumkan

| Wajib | Penjelasan |
|---|---|
| Issue ID | ID parent (satu per brief) |
| Semua Task ID | ID setiap task dalam brief tersebut |

Epic ID **tidak perlu** dicantumkan di commit — cukup Issue + Tasks.

---

## 10. Audit Trail — Board to Commit

Setelah beberapa brief selesai, ada jejak lengkap yang bisa ditelusuri dari dua arah.

### 10.1 Dari Board ke Commit

1. Buka Azure DevOps board:
   ```
   https://dev.azure.com/[org]/[project]/_boards/board/t/[team]/Issues
   ```
2. Klik work item yang ingin ditelusuri (misal Task #293)
3. Di panel kanan → tab **Development** atau bagian **Links**
4. Semua commit yang mencantumkan `AB#293` akan muncul di sini
5. Klik commit → langsung ke kode di Azure Repos

### 10.2 Dari Commit ke Board

1. Buka Azure Repos → Commits:
   ```
   https://dev.azure.com/[org]/[project]/_git/[repo]/commits
   ```
2. Klik commit yang ingin ditelusuri
3. Di bagian atas commit detail → work item yang di-link muncul sebagai badge
4. Klik badge → langsung ke work item di board

### 10.3 Dari Sprint Timeline

1. Azure DevOps → Boards → Sprints → pilih **Sprint 01**
2. Semua task tampil dengan state saat ini
3. Klik task → lihat history kapan state berubah (To Do → Doing → Done)
4. Tab Development → commit yang mengerjakan task ini

### 10.4 Git Log dengan AB#ID

```powershell
# Cari semua commit yang terkait work item tertentu
git log --oneline --grep="AB#292"

# Lihat semua commit progress sprint 01
git log --oneline --grep="\[progress\] sprint-01"
```

---

## 11. State Transitions

### Diagram Lengkap

```
        Saat brief dibuat            Saat brief terkirim ke OpenCode
To Do ─────────────────────────────────────────────────────► Doing
                                                                │
                                                                │ OpenCode selesai
                                                                │ Claude verifikasi ✅
                                                                │ Commit dilakukan
                                                                ▼
                                                              Done
```

### Siapa Yang Melakukan Transisi

| Transisi | Pelaku | Waktu |
|---|---|---|
| (created) → To Do | Azure (otomatis saat work item dibuat) | Saat seed sprint |
| To Do → Doing | Claude | Saat brief dikirim ke OpenCode |
| Doing → Done | Claude | Setelah verifikasi output + commit |

**Developer tidak update state manual.** Jika ada work item yang statusnya tidak
akurat, tanyakan ke Claude di awal sesi — Claude akan sinkronisasi ulang.

### Jika Brief Gagal / Incomplete

Jika output OpenCode tidak memuaskan (ada gap, test gagal, dsb.):
- Task tetap di state `Doing`
- Catat gap di `.docs/delegatetasks/sprint-XX.md`
- Brief direvisi dan dikirim ulang ke OpenCode
- Setelah revisi selesai dan verifikasi OK → baru update ke `Done`

**Jangan set ke Done jika ada test yang fail.**

---

## 12. Strict Conventions

Tiga aturan berikut bersifat **WAJIB** dan tidak boleh dilanggar dalam kondisi apapun.
Pelanggaran mengakibatkan audit trail tidak akurat dan traceability hilang.

### ❌ Larangan 1 — Commit tanpa AB#ID

```
# SALAH — tidak ada AB#ID
git commit -m "[progress] sprint-01 brief-1 done — Organization backend"

# BENAR — semua ID dicantumkan
git commit -m "[progress] sprint-01 brief-1 done — Organization backend AB#292 AB#293 AB#294 ..."
```

### ❌ Larangan 2 — Update Azure sebelum commit

```
# SALAH — update Azure dulu, commit belakangan
Set-WIState 292 "Done"  # ← JANGAN
git commit ...

# BENAR — urutan wajib: centang checklist → commit → update Azure
# 1. Update sprint doc checklist
# 2. git commit dengan AB#ID
# 3. Set-WIState → Done
```

### ❌ Larangan 3 — Lanjut brief berikutnya sebelum Done

```
# SALAH — Brief 1 masih Doing, langsung kirim Brief 2
# Brief 2 harus menunggu Brief 1 Done di Azure
```

Brief berikutnya hanya boleh dimulai setelah:
- Output OpenCode diverifikasi ✅
- Sprint doc diupdate dan di-commit ✅
- Azure DevOps (Issue + Tasks) → Done ✅

---

## 13. Setup Git Convention untuk Claude

Claude membaca konvensi git dari **dua sumber** yang wajib ada di setiap project:
  
1. **`CLAUDE.md` §Git Workflow** — definisi branch naming, commit format, aturan merge
2. **`.claude/settings.local.json`** — permission agar Claude boleh menjalankan perintah git

Tanpa keduanya, Claude akan menebak-nebak format commit dan minta konfirmasi setiap
perintah git — memperlambat workflow secara signifikan.

---

### 13.1 Definisi Konvensi di CLAUDE.md

Tambahkan section `### Git Workflow` di `CLAUDE.md`. Ini adalah satu-satunya sumber
kebenaran untuk konvensi git — Claude membaca ini di setiap sesi.
  
**Lokasi:** `.claude/CLAUDE.md` → di bawah section `## Development Workflow` 

**Format minimal yang harus ada:** 

```markdown

### Git Workflow

Branches:
  feature/PROJ-{id}-{short-desc}     → fitur baru
  fix/PROJ-{id}-{short-desc}         → bug fix
  refactor/{short-desc}              → refactoring tanpa fitur baru
  
Commit types (wajib lowercase):
  [feat]      fitur baru yang ditambahkan ke codebase
  [fix]       bug fix
  [test]      menambah atau update test
  [refactor]  refactor tanpa mengubah behavior
  [docs]      perubahan dokumentasi saja
  [progress]  update progress sprint — WAJIB diikuti AB#ID (lihat §9)
  [chore]     dependency, config, tooling
  
Commit format:
  [type] deskripsi singkat dalam bahasa Indonesia atau Inggris

Contoh:
  [feat] add department CRUD with soft delete
  [fix] correct unique constraint on section code per department
  [test] add feature tests for DepartmentController
  [progress] sprint-01 brief-1 done — Organization backend AB#292 AB#293 AB#294

Rules:
  - Jangan merge ke main tanpa CI passing dan 1 reviewer approval
  - Jangan force-push ke branch bersama
  - Jangan skip membuat migration untuk setiap perubahan schema
  - Commit [progress] WAJIB menyertakan AB#ID semua task yang selesai
``` 

> **Kenapa di CLAUDE.md bukan file lain?** Claude membaca `CLAUDE.md` di setiap sesi
> sebagai konteks utama. Konvensi yang didefinisikan di sini berlaku persisten — tidak
> perlu diingatkan ulang ke Claude setiap sesi.

--- 

### 13.2 Permission Git di settings.local.json

Claude memerlukan permission eksplisit untuk menjalankan perintah git.
Tanpa ini, setiap `git add`, `git commit`, `git push` akan meminta konfirmasi manual.

**Lokasi:** `.claude/settings.local.json`
  
```json
{
  "permissions": {
    "allow": [
      "Bash(git status*)",
      "Bash(git add*)",
      "Bash(git commit*)",
      "Bash(git log*)",
      "Bash(git diff*)",
      "Bash(git push*)",
      "Bash(git checkout*)",
      "Bash(git branch*)",
      "Bash(git pull*)",
      "PowerShell(git *)"
    ]
  }
}

``` 

> **Catatan:** `settings.local.json` sudah ada di `.gitignore` — tidak perlu di-commit.
> Setiap developer setup file ini di mesin masing-masing.

Cara buat via PowerShell jika belum ada: 

```powershell

$dir = "D:\Apps\[nama-project]\.claude"
New-Item -ItemType Directory -Force $dir | Out-Null

$settings = @'
{
  "permissions": {
    "allow": [
      "Bash(git status*)",
      "Bash(git add*)",
      "Bash(git commit*)",
      "Bash(git log*)",
      "Bash(git diff*)",
      "Bash(git push*)",
      "Bash(git checkout*)",
      "Bash(git branch*)",
      "Bash(git pull*)",
      "PowerShell(git *)"
    ]
  }
}
'@
Set-Content "$dir\settings.local.json" -Value $settings -Encoding UTF8

```

---

### 13.3 Cara Claude Mengikuti Konvensi  

Setelah CLAUDE.md dan settings.local.json terkonfigurasi, Claude akan:
  
| Situasi | Perilaku Claude |
|---|---|
| Commit progress brief | Otomatis pakai `[progress]` + AB#ID tanpa diminta |
| Commit file baru (feat) | Pakai `[feat]` dengan deskripsi sesuai konteks |
| Commit bug fix | Pakai `[fix]` |
| Commit docs / onboarding | Pakai `[docs]` |
| Branch salah nama | Mengingatkan developer sebelum commit |
| Merge tanpa CI pass | Menolak dan meminta developer cek CI dulu |

Claude membaca git log (`git log --oneline -5`) sebelum commit untuk **mendeteksi
pola commit yang sudah ada** di repository — sehingga format barunya konsisten dengan
commit sebelumnya.

---

### 13.4 Verifikasi Konvensi Aktif

Test bahwa Claude membaca dan mengikuti konvensi dengan benar: 

**Test 1 — Commit message format:**

Minta Claude commit file apapun. Perhatikan apakah format mengikuti `[type] deskripsi`.
Jika Claude menggunakan format lain (misal `feat: ...` atau `Add ...`), berarti
CLAUDE.md belum terbaca dengan benar — periksa lokasi dan format file.
  
**Test 2 — AB#ID otomatis:**

Setelah brief pertama selesai, minta Claude commit. Perhatikan apakah AB#ID
dicantumkan otomatis tanpa diminta. Jika tidak muncul, cek apakah konvensi
`[progress] ... AB#ID` sudah ada di section `### Git Workflow` di CLAUDE.md. 

**Test 3 — Permission tidak muncul:**

Jalankan `/start-session`. Jika Claude meminta konfirmasi untuk `git status` atau
`git log` → berarti `settings.local.json` belum ada atau path-nya salah.
  
```powershell

# Cek apakah settings.local.json sudah ada
Test-Path ".claude\settings.local.json"

# Harus return: True

# Cek isinya
Get-Content ".claude\settings.local.json"

```

---

### 13.5 Integrasi dengan Commitlint (Opsional)

Jika tim ingin enforce konvensi juga untuk developer (bukan hanya Claude),
pasang commitlint + husky agar commit manual yang tidak sesuai format ditolak.
  
> **Catatan:** Commitlint menggunakan format `type: description` (conventional commits).
> Project ini menggunakan `[type] description` (bracket format). Keduanya bisa
> dikonfigurasi — pilih salah satu dan konsisten.

  
**Setup commitlint untuk bracket format:**
 

```powershell

cd frontend

npm install -D @commitlint/cli husky

```

Buat `commitlint.config.js` di root project: 

```js

// commitlint.config.js
module.exports = {
  rules: {
    // enforce format: [type] description
    'header-pattern': [2, 'always', /^\[(feat|fix|test|refactor|docs|progress|chore)\] .+/],
    'header-max-length': [2, 'always', 100],
  },
  parserPreset: {
    parserOpts: {
      headerPattern: /^\[(\w+)\] (.+)$/,
      headerCorrespondence: ['type', 'subject'],
    },
  },
}

``` 

Setup husky:
  
```powershell
npx husky init
'npx --no -- commitlint --edit $1' | Set-Content .husky\commit-msg -Encoding UTF8
```

Setelah ini, commit dengan format salah akan otomatis ditolak — baik dari developer
maupun jika Claude kebetulan menggunakan format yang salah.

--- 

### 13.6 Checklist Setup Git Convention

- [ ] Section `### Git Workflow` ada di `.claude/CLAUDE.md` dengan branch naming, commit types, dan rules
- [ ] Format `[progress] ... AB#ID` tercantum eksplisit di konvensi
- [ ] `.claude/settings.local.json` berisi permission untuk `git *` commands
- [ ] Test commit pertama Claude menggunakan format `[type] deskripsi` dengan benar
- [ ] Test commit [progress] otomatis menyertakan AB#ID tanpa diminta
- [ ] (Opsional) commitlint + husky aktif untuk enforce di developer juga

---

## 13. Troubleshooting

### Error: "You must pass a valid patch document"

**Gejala:** `Set-WIState` gagal dengan pesan `400 Bad Request`, pesan error "valid patch document".

**Penyebab:** `ConvertTo-Json` di PowerShell menghasilkan encoding yang tidak diterima Azure API.

**Solusi:** Gunakan `UTF8.GetBytes` untuk body — **jangan** `ConvertTo-Json` untuk body request:

```powershell
# ✅ BENAR
$bodyBytes = [System.Text.Encoding]::UTF8.GetBytes(
  '[{"op":"add","path":"/fields/System.State","value":"Done"}]'
)
Invoke-WebRequest ... -Body $bodyBytes

# ❌ SALAH
$body = @(@{op="add"; path="/fields/System.State"; value="Done"}) | ConvertTo-Json
Invoke-WebRequest ... -Body $body
```

---

### Error: 401 Unauthorized saat akses Azure API

**Penyebab:** PAT sudah expired atau salah di `.env.claude`.

**Solusi:**
1. Buka Azure DevOps → Profile → Personal Access Tokens
2. Cek apakah token masih valid atau sudah expired
3. Jika expired → buat PAT baru (§5)
4. Update `.env.claude` dengan token baru

---

### OpenCode tidak merespons (polling timeout)

**Gejala:** Polling mencapai `[72/72] TIMEOUT`, msgs tidak bertambah.

**Kemungkinan penyebab:**
1. Brief terlalu panjang / kompleks untuk model yang dipilih
2. 9Router crash atau tidak merespons
3. OpenCode serve mati

**Solusi:**

```powershell
# Cek 9Router
Invoke-WebRequest -Uri "http://localhost:20128/v1/models" -UseBasicParsing -TimeoutSec 5

# Cek OpenCode serve
Invoke-WebRequest -Uri "http://localhost:4099" -UseBasicParsing -TimeoutSec 3

# Cek apakah session masih ada output yang masuk
$msgs = (Invoke-WebRequest -Uri "http://localhost:4099/session/$sessId/message" -UseBasicParsing).Content | ConvertFrom-Json
$msgs | ForEach-Object { Write-Host "role=$($_.info.role) parts=$($_.parts.Count) completed=$($_.info.time.completed)" }
```

Jika session masih aktif tapi lambat → tunggu lebih lama dengan menambah `$maxTries`.
Jika OpenCode mati → restart, buat session baru, kirim ulang brief.

---

### Work item tidak ter-link ke commit di Azure

**Gejala:** Commit sudah di-push tapi tidak muncul di Development tab work item.

**Kemungkinan penyebab:**
1. Repository di Azure Repos belum di-setup
2. Commit belum di-push (hanya local)
3. Format `AB#ID` salah (spasi, huruf kecil, dsb.)

**Verifikasi format:**

```powershell
# Cek commit message terakhir
git log --oneline -5

# Format yang benar: AB# (kapital, tanpa spasi antara AB dan #)
# Contoh: AB#292 AB#293
# SALAH: ab#292, AB #292, AB292
```

**Jika repository belum terhubung ke Azure Repos:**
Hubungkan dulu di Azure DevOps → Project Settings → Repos → tambahkan GitHub/Azure Repos connection.

---

### PAT expired — Cara Perpanjang

PAT Azure DevOps memiliki masa berlaku (30–180 hari). Jika expired:

1. Azure DevOps → klik foto profil → **Personal Access Tokens**
2. Temukan token yang expired → klik **Renew** atau buat baru
3. Salin token baru
4. Update `.env.claude`:

```powershell
# Update hanya baris PAT
(Get-Content ".env.claude") -replace '^AZURE_DEVOPS_PAT=.*', "AZURE_DEVOPS_PAT=$newToken" |
  Set-Content ".env.claude" -Encoding UTF8
```

> **Reminder:** Set kalender reminder 1 minggu sebelum expired agar tidak tiba-tiba
> tidak bisa update Azure saat sedang sprint aktif.

---

## Checklist Onboarding Modul 21

Selesaikan checklist berikut sebelum dianggap selesai modul ini:

### Setup

- [ ] Claude Code CLI terinstall dan bisa dijalankan (`claude --version`)
- [ ] OpenCode terinstall (`opencode --version`)
- [ ] 9Router berjalan di port 20128 (verifikasi via Invoke-WebRequest)
- [ ] OpenCode serve berjalan di port 4099 (verifikasi via Invoke-WebRequest)
- [ ] PAT Azure DevOps dibuat dengan scope Work Items R/W + Project Read
- [ ] File `.env.claude` dibuat di root project dengan 3 variable
- [ ] Koneksi Azure API berhasil (test di §6.2 menampilkan list project)

### Sprint Seed

- [ ] Sprint doc sudah ada di `.docs/sprints/sprint-XX.md`
- [ ] Sprint seed berhasil — work items muncul di Azure board
- [ ] Semua task dalam state `To Do` di board

### Workflow

- [ ] `/start-session` berjalan dan melaporkan sprint aktif
- [ ] Brief pertama berhasil dikirim ke OpenCode via HTTP API
- [ ] Output OpenCode berhasil diverifikasi
- [ ] Commit pertama dengan format `[progress] ... AB#ID` berhasil
- [ ] Work items berhasil diupdate ke `Done` via `Set-WIState`
- [ ] Audit trail bisa dilihat di work item → tab Development

---

*Modul 21 • AI-Assisted DevOps Lifecycle • EO-PIMS v20260605*
