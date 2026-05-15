# Panduan Testing Tombol Storage Setup

## Persiapan

### 1. Pastikan Backend Running
```bash
cd c:\XAMPP\htdocs\EODS\Master\backend
php artisan serve
```

### 2. Pastikan Frontend Running
```bash
cd c:\XAMPP\htdocs\EODS\Master\frontend
npm run dev
```

## Testing dengan Test Page

### Akses Test Page
Buka browser dan akses:
```
http://localhost:5173/setup/storage/test
```

### Test yang Tersedia

#### Test 1: Simple Button
- Klik tombol "Click Me (Test 1)"
- Seharusnya muncul timestamp di bawah tombol
- Console log seharusnya muncul

#### Test 2: Button with Icon
- Klik tombol "Click Me (Test 2)" yang ada icon plus
- Seharusnya muncul timestamp di bawah tombol
- Console log seharusnya muncul

#### Test 3: Button with @click.stop
- Klik tombol "Click Me (Test 3)"
- Seharusnya HANYA child yang ter-trigger
- Parent TIDAK boleh ter-trigger
- Console log seharusnya hanya menunjukkan "Test 3 clicked (child)"

#### Test 4: Actual Storage Button Style
- Klik tombol kecil dengan icon list (sama seperti tombol asli)
- Seharusnya muncul timestamp di bawah tombol
- Console log seharusnya muncul

#### Test 5: Modal Open
- Klik tombol "Open Modal"
- Modal seharusnya muncul
- Klik "Close" untuk menutup modal

### Interpretasi Hasil

#### ✅ Semua Test Berhasil
Jika semua test berhasil, berarti:
- Event handler berfungsi dengan baik
- @click.stop berfungsi
- Modal berfungsi
- **Masalah ada di implementasi spesifik StorageDetailTab**

#### ❌ Test Gagal
Jika ada test yang gagal:
- **Test 1 gagal**: Masalah fundamental dengan Vue event binding
- **Test 2 gagal**: Masalah dengan icon pointer-events
- **Test 3 gagal**: Masalah dengan event propagation
- **Test 4 gagal**: Masalah dengan styling yang menghalangi click
- **Test 5 gagal**: Masalah dengan modal v-model binding

## Testing di Halaman Asli

### 1. Login
```
http://localhost:5173/
```
Login dengan credentials yang valid.

### 2. Buka Setup Storage
Navigasi ke: **Setup → Storage**
```
http://localhost:5173/setup/storage
```

### 3. Buka Browser Console
Tekan `F12` atau `Ctrl+Shift+I`

### 4. Test View Details Button

#### Langkah:
1. Pastikan ada data di tabel Storage Tank
2. Klik tombol biru (icon list) pada salah satu row
3. Periksa console

#### Expected Result:
```
View Detail clicked: {id_tank: 1, code: "...", ...}
```

#### Jika Berhasil:
- Detail tank muncul di bawah tabel
- Tabel detail menampilkan data tank

#### Jika Gagal:
- Console log tidak muncul → Event handler tidak terpanggil
- Console log muncul tapi detail tidak muncul → Masalah dengan API atau state management
- Ada error di console → Catat error message

### 5. Test Add Tank Button

#### Langkah:
1. Pastikan sudah klik "View Details" terlebih dahulu
2. Scroll ke bawah, lihat section "Detail Tank"
3. Klik tombol "Tambah Detail"
4. Periksa console

#### Expected Result:
```
Add Tank Detail clicked
```

#### Jika Berhasil:
- Modal "Tambah Storage Detail" terbuka
- Form TF Number muncul

#### Jika Gagal:
- Console log tidak muncul → Event handler tidak terpanggil
- Console log muncul tapi modal tidak muncul → Masalah dengan v-model binding
- Ada error di console → Catat error message

## Debugging dengan Vue DevTools

### Install Vue DevTools
1. Chrome: https://chrome.google.com/webstore/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd
2. Firefox: https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/

### Inspect Component State

#### 1. Buka Vue DevTools
Tekan `F12` → Tab "Vue"

#### 2. Cari Component
- Cari "StorageDetailTab" di component tree
- Klik untuk inspect

#### 3. Periksa State
```
selectedTank: null atau {data...}
showTankModal: false
showDetailModal: false
store.tanks: [array of tanks]
store.details: [array of details]
```

#### 4. Test Manual State Change
Di console Vue DevTools:
```javascript
$vm.showDetailModal = true
```
Jika modal muncul, berarti v-model binding OK.

## Debugging dengan Network Tab

### 1. Buka Network Tab
`F12` → Tab "Network"

### 2. Filter XHR
Klik "XHR" untuk filter hanya AJAX requests

### 3. Reload Page
Refresh halaman Setup Storage

### 4. Periksa API Calls

#### Expected Calls:
```
GET /api/v1/storage-tanks → Status 200
```

#### Jika Gagal:
- Status 401: Belum login atau token expired
- Status 500: Error di backend
- Status 404: Route tidak ditemukan
- No request: Frontend tidak memanggil API

### 5. Test View Details API
Klik "View Details" dan periksa:
```
GET /api/v1/storage-details?id_tank=1 → Status 200
```

## Common Issues & Solutions

### Issue 1: Tombol Tidak Bisa Diklik
**Gejala:** Cursor tidak berubah, tidak ada response

**Solusi:**
1. Periksa z-index CSS
2. Periksa apakah ada overlay
3. Inspect element dengan browser DevTools
4. Periksa computed styles

**Test:**
```javascript
// Di console
document.elementFromPoint(x, y) // koordinat tombol
```

### Issue 2: Event Handler Tidak Terpanggil
**Gejala:** Console log tidak muncul

**Solusi:**
1. Periksa apakah component ter-mount
2. Periksa apakah ada error sebelumnya
3. Periksa Vue DevTools untuk component state
4. Periksa apakah method terdefinisi

### Issue 3: Modal Tidak Muncul
**Gejala:** Console log muncul tapi modal tidak muncul

**Solusi:**
1. Periksa v-model binding
2. Periksa z-index modal
3. Periksa apakah modal component ter-import
4. Test manual state change di Vue DevTools

### Issue 4: Data Tidak Muncul
**Gejala:** Tabel kosong atau loading terus

**Solusi:**
1. Periksa Network tab untuk API response
2. Periksa console untuk error
3. Periksa store state di Vue DevTools
4. Periksa backend logs

### Issue 5: Token Expired
**Gejala:** API return 401 Unauthorized

**Solusi:**
1. Logout dan login ulang
2. Clear localStorage
3. Periksa token expiry di backend

## Reporting Issues

Jika masalah masih berlanjut, laporkan dengan informasi berikut:

### 1. Environment
- Browser: Chrome/Firefox/Edge + version
- OS: Windows/Mac/Linux
- Node version: `node -v`
- NPM version: `npm -v`

### 2. Screenshots
- Screenshot halaman
- Screenshot console errors
- Screenshot network tab
- Screenshot Vue DevTools

### 3. Steps to Reproduce
1. Langkah 1
2. Langkah 2
3. ...

### 4. Expected vs Actual
- **Expected:** Modal seharusnya terbuka
- **Actual:** Tidak ada response

### 5. Console Logs
```
Copy paste semua error dari console
```

### 6. Network Logs
```
Copy paste failed API requests
```

---

**Dibuat:** 2026-05-13
**Versi:** 1.0.0
