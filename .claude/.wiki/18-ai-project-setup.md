# AI-Assisted Project Setup — Claude Code CLI

> Source: Kompilasi Modul Tech Stack — Laravel 12 + Vue.js (v20260525)

> Pages: 184–190

---

> AI-Assisted Project Setup
> Menggunakan Claude Code CLI untuk Initial Setup Lengkap

> 📌 Overview

> Modul ini memandu penggunaan Claude Code CLI — AI coding assistant berbasis terminal —
> untuk melakukan setup awal proyek Laravel + Vue.js secara otomatis dan terarah. Dengan
> pendekatan ini, Claude bertindak layaknya Senior Developer yang memandu setup dari nol
> hingga aplikasi berjalan dengan halaman welcome, login, register, dan dashboard.

Modul ini menggunakan tiga file markdown sebagai panduan kerja bagi Claude: dokumen instruksi setup permissions, prompt utama yang mendefinisikan peran Claude, dan dokumen arsitektur lengkap sebagai referensi konvensi kode.

> 💡 Apa bedanya dengan setup manual?
Setup manual (Modul 01) membutuhkan eksekusi perintah satu per satu. Dengan Claude Code, kamu cukup memberikan prompt dan arsitektur referensi — Claude akan menjalankan perintah, menulis file,
dan memvalidasi hasilnya secara otomatis sesuai konvensi yang sudah didefinisikan.

> 📂 Penjelasan File Markdown

Tiga file markdown berikut menjadi pondasi kerja di modul ini. Masing-masing memiliki peran yang berbeda dan digunakan pada tahap yang berbeda pula. Sumber file markdown dapat diakses melalui Azure Devops > eoads05 > laravel-vue-onboarding.

0-claude-cli-setup.md
Panduan instalasi dan konfigurasi Claude Code CLI — dijalankan sekali di awal sebelum apapun.
- Instalasi global via npm: @anthropic-ai/claude-code
- Langkah autentikasi OAuth dengan akun Anthropic
- Inisialisasi project dengan perintah /init untuk membuat CLAUDE.md
- Referensi perintah penting: /help, /clear, /review, /config
- Penjelasan cara CLAUDE.md menjadi konteks persisten di tiap sesi

1-claude-app-basic-prompt.md
Prompt utama yang mendefinisikan peran dan tugas Claude — dikirim ke Claude Code di awal sesi setup.
- Menetapkan peran Claude sebagai Senior Laravel Developer & Software Architect
- Menginstruksikan Claude untuk membaca dan mematuhi file claude-app-basic-setup.md
- Meminta setup langkah-demi-langkah: dari inisialisasi proyek hingga struktur direktori
- Aturan: tidak boleh berasumsi di luar konvensi yang tertulis di file MD
- Instruksi pemisahan tahapan logis agar mudah dieksekusi dan divalidasi

> 2-claude-app-basic-setup.md

Dokumen arsitektur lengkap — menjadi satu-satunya referensi konvensi kode yang diikuti Claude selama setup.
- Versi package yang sudah divalidasi dari build nyata (PHP 8.2, Laravel 12, Vue 3, Vuetify 3.7)
- Pre-flight check: deteksi port MySQL otomatis via netstat
- Backend: urutan install wajib (allow-plugins → require → install:api → ldaprecord →
Vendor:publish)
- User Model, Migration, LDAP/SQL Auth Strategy, AuthController, FormRequests
- Frontend: struktur folder, Vuetify + Pinia + Vue Router + Axios setup
- Auth Store, Toast Store, GuestLayout, AuthLayout, Router dengan navigation guard
- Testing: PHPUnit Feature Tests + Vitest unit tests dengan localStorage mock
- Checklist setup, troubleshooting jaringan npm/composer, dan catatan dari build nyata

> ✅ Prerequisites Sebelum Memulai

> Pastikan semua tools berikut sudah tersedia di sistem sebelum mengikuti modul ini:

## Tool Versi & Cara Cek

Node.js >= 18 — node -v
```bash
npm Bawaan Node.js — npm -v
```
```PHP >= 8.2 — php -v Composer >= 2 — composer -v MySQL / MariaDB Aktif via XAMPP / Laragon / Herd VSCode Versi terbaru — dengan terminal terintegrasi Akun Anthropic Dibutuhkan untuk autentikasi Claude Code CLI

> ⚠️  Catatan untuk Pengguna Windows
> Seluruh perintah di modul ini menggunakan PowerShell. Pastikan terminal di VSCode sudah
> dikonfigurasi ke PowerShell (bukan Command Prompt) sebelum memulai.

## Part 1 — Install Claude Code CLI

Instalasi dilakukan sekali secara global. Setelah terinstall, Claude Code dapat diakses dari terminal manapun.

1 Buka Terminal di VSCode Gunakan menu View > Terminal atau shortcut Ctrl + ` untuk membuka terminal. Pastikan shell yang aktif adalah PowerShell.

### 2. Install Claude Code secara global via npm

```
```bash
```
```bash
npm install -g @anthropic-ai/claude-code
```

### 3. Verifikasi instalasi
Claude --version

Output akan menampilkan nomor versi Claude Code yang terinstall. Jika perintah tidak dikenali, tutup dan buka kembali terminal lalu coba lagi.

### 4. Autentikasi dengan akun Anthropic
Navigasi ke direktori kerja proyek, lalu jalankan:
# Masuk ke direktori project
Cd D:\Apps\[nama-project]

# Jalankan Claude Code
Claude

Pada pertama kali dijalankan, Claude Code akan membuka browser secara otomatis dan meminta login dengan akun Anthropic via OAuth. Setelah login selesai, kembali ke terminal — sesi Claude Code akan aktif.

💡 Akun Anthropic Kamu membutuhkan akun di claude.ai atau Anthropic Console. Jika belum punya, daftar dulu di https://claude.ai sebelum menjalankan perintah ini.

## Part 2 — Setup Project Directory & Permissions

Sebelum memberikan prompt ke Claude, kita perlu menyiapkan direktori project dan file permissions agar Claude dapat menjalankan perintah tanpa harus meminta persetujuan manual setiap saat.

### 1. Buat root directory project
New-Item -ItemType Directory -Force "D:\Apps\[nama-project]" cd "D:\Apps\[nama-project]"

2 Buat file .claude/settings.local.json File ini memberikan permission kepada Claude untuk menjalankan perintah tanpa konfirmasi manual: $dir = "D:\Apps\[nama-project]\.claude" New-Item -ItemType Directory -Force $dir | Out-Null

@' { "permissions": { "allow": [ "Bash(php *)", "Bash(composer *)", "Bash(npm *)", "Bash(node *)", "Bash(ls *)", "PowerShell(php *)", "PowerShell(composer *)", "PowerShell(npm *)", "PowerShell(node *)", "PowerShell(netstat *)", "PowerShell(New-Item *)", "PowerShell(Get-ChildItem *)", "PowerShell(Test-Path *)",

"PowerShell(echo *)", "PowerShell(cd *)" ]```php
```php
```
```bash
}
}
```
'@ | Set-Content "$dir\settings.local.json" -Encoding utf8

🔒 Mengapa perlu file ini? Tanpa permissions yang tepat, Claude akan meminta persetujuan untuk setiap perintah composer, php artisan, npm, dan PowerShell — yang memperlambat proses setup drastis. File Settings.local.json bersifat lokal dan tidak perlu di-commit ke git.

```bash
3 Salin file markdown referensi ke root directory
Pastikan ketiga file markdown berikut ada di direktori root project:
D:\Apps\[nama-project]\
├── .claude\
│   └── settings.local.json    ← sudah dibuat di Step 2
├── 0-claude-cli-setup.md
├── 1-claude-app-basic-prompt.md
└── 2-claude-app-basic-setup.md
```

### 4. Inisialisasi Claude Code di project directory
Cd "D:\Apps\[nama-project]" claude

# Setelah sesi aktif:
/init

## Part 3 — Menjalankan claude-app-basic-prompt.md

Ini adalah langkah inti — mengirimkan prompt ke Claude dengan file arsitektur sebagai referensi.

### 1. Pastikan sesi Claude Code aktif
Cd "D:\Apps\[nama-project]" claude

### 2. Minta Claude membaca file arsitektur terlebih dahulu
Baca isi file 2-claude-app-basic-setup.md yang ada di direktori ini.

Ini adalah dokumen arsitektur dan konvensi yang wajib kamu ikuti.

Konfirmasi setelah kamu membacanya.

✅  Mengapa ini penting? Dengan meminta Claude membaca 2-claude-app-basic-setup.md lebih dulu, kamu memastikan
Claude memiliki konteks arsitektur lengkap sebelum menerima instruksi setup. Ini mencegah Claude membuat asumsi di luar konvensi yang sudah kamu tentukan.

### 3. Kirim prompt utama dari 1-claude-app-basic-prompt.md
Setelah Claude mengkonfirmasi, salin seluruh isi file prompt dan paste ke sesi Claude Code:

- Role: Bertindaklah sebagai Senior Laravel Developer dan Software Architect
- eksekutif yang sangat disiplin terhadap clean code, design patterns,
- dan konvensi framework.

Konteks: Saya sedang memulai proyek aplikasi Laravel baru. Saya memiliki berkas panduan konfigurasi dasar di dalam file claude-app-basic-setup.md.

Tugas Anda:

- 1. Baca dan pahami seluruh isi claude-app-basic-setup.md.
- 2. Bimbing saya melakukan initial setup dari awal hingga struktur direktori.
- 3. Pastikan semua kode patuh 100% pada aturan di file MD tersebut.

Aturan: Jangan berasumsi di luar dokumen. Pisahkan menjadi tahapan logis.

### 4. Ikuti panduan Claude tahap demi tahap

Tahap Yang Dilakukan Claude Tahap 0 Verifikasi .claude/settings.local.json sudah ada Tahap 1 Pre-flight check: deteksi port MySQL, set variabel PS Tahap 2 Install Laravel, konfigurasi composer.json, install packages Tahap 3 Konfigurasi .env, User Model, Migration, jalankan migrate:fresh Tahap 4 Buat modul Auth, routes API, Services, Controller, FormRequests Tahap 5 Init project Vue, install dependencies, setup Vuetify + Pinia + Router Tahap 6 Buat stores, layouts, views (HomeView, LoginView, RegisterView, Dashboard) Tahap 7 Setup testing: PHPUnit feature tests + Vitest unit tests Tahap 8 Verifikasi final: php artisan test + npm run test:run

💬 Tips Berinteraksi dengan Claude Jika Claude menunggu konfirmasi sebelum melanjutkan ke tahap berikutnya, ketik "lanjut" atau "proceed". Jika ada error, paste pesan error ke Claude — ia akan mendiagnosis dan memperbaikinya sesuai troubleshooting guide di 2-claude-app-basic-setup.md.

## Part 4 — Verifikasi Hasil Setup

### 1. Jalankan backend server
Cd D:\Apps\[nama-project]\backend
```bash
php artisan serve
# → http://localhost:8000
```

### 2. Jalankan frontend dev server

Cd D:\Apps\[nama-project]\frontend
```bash
npm run dev
# → http://localhost:5173
```

### 3. Cek API routes

```bash
php artisan route:list --path=api
```

### 4. Jalankan test suite
Backend
```bash
php artisan test
```

Frontend
```bash
npm run test:run

### 5. Verifikasi halaman di browser
URL Halaman yang Seharusnya Muncul http://localhost:5173/ HomeView — halaman welcome http://localhost:5173/login LoginView — form login
http://localhost:5173/register RegisterView — form registrasi
http://localhost:5173/dashboard Redirect ke /login (belum auth)
```

✅  Setup Berhasil! Jika semua halaman muncul, test suite hijau, dan route API terdaftar — setup modul 18 selesai.
Backend dan frontend siap dikembangkan lebih lanjut dengan konvensi yang sudah didefinisikan di 2 - claude-app-basic-setup.md.

Referensi Perintah Claude Code

💻 Perintah Deskripsi /init Buat CLAUDE.md — konteks project persisten untuk setiap sesi /help Tampilkan daftar semua perintah yang tersedia /clear Bersihkan konteks percakapan — mulai sesi baru /review Minta Claude mereview perubahan di branch saat ini /config Buka pengaturan: tema, model, API key, dll ! <command> Jalankan shell command inline, contoh: ! php artisan migrate Ctrl + C Hentikan perintah yang sedang berjalan Ctrl + D Keluar dari sesi Claude Code

## Troubleshooting

Error / Gejala Solusi claude: command not found Tutup dan buka kembali terminal. Cek: npm config get prefix Browser tidak terbuka saat auth Buka URL autentikasi secara manual — Claude menampilkannya di terminal Claude terus meminta konfirmasi Pastikan .claude/settings.local.json dibuat di root directory project Claude tidak membaca file MD Pastikan file MD ada di direktori tempat claude dijalankan. Cek: ls *.md Koneksi MySQL gagal Jalankan: netstat -an | findstr "330" dan sesuaikan DB_PORT di .env```bash
npm install gagal: 403 Jaringan memblok package. Gunakan VPN/hotspot atau
```Lihat Section 10.2 Vitest: localStorage error Pastikan src/test/setup.js berisi localStorage mock penuh (Section 6.2)

📘 Tips: Update CLAUDE.md Setelah setiap sprint atau penambahan fitur besar, update file CLAUDE.md dengan informasi terbaru tentang project — package yang ditambah, konvensi baru, atau progress fitur. Claude membaca file ini di setiap sesi.

―――  Modul 18 • AI-Assisted Project Setup • Selesai  ―――

```
