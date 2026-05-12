# Cara Menjalankan Aplikasi EODS (Decoupled)

## Persiapan

### 1. Backend (Laravel API)

#### Konfigurasi yang sudah diperbaiki:
- ✅ `SESSION_DRIVER=file` (sebelumnya cookie)
- ✅ `SESSION_DOMAIN=` (kosong untuk localhost)
- ✅ `SESSION_SECURE_COOKIE=false` (untuk development)
- ✅ `SESSION_SAME_SITE=lax` (untuk cross-origin)
- ✅ `SANCTUM_STATEFUL_DOMAINS` sudah include localhost dan 127.0.0.1
- ✅ Folder `storage/framework/sessions` sudah dibuat
- ✅ CORS sudah dikonfigurasi untuk frontend

#### Menjalankan Backend:
```bash
cd backend
php artisan serve --host=127.0.0.1 --port=8000
```

Backend akan berjalan di: `http://127.0.0.1:8000`

### 2. Frontend (Vue.js)

#### Konfigurasi yang sudah diperbaiki:
- ✅ `VITE_API_BASE_URL=http://127.0.0.1:8000`

#### Menjalankan Frontend:
```bash
cd frontend
npm run dev
```

Frontend akan berjalan di: `http://127.0.0.1:5173` atau `http://localhost:5173`

## Kredensial Login

- **Email:** admin@eods.local
- **Password:** password123

## Testing Login

### Opsi 1: Menggunakan Frontend
1. Buka browser ke `http://127.0.0.1:5173`
2. Login dengan kredensial di atas

### Opsi 2: Menggunakan Test File
1. Buka file `test-login.html` di browser
2. Kredensial sudah terisi otomatis
3. Klik tombol "Login"
4. Lihat hasil di console browser (F12)

### Opsi 3: Menggunakan cURL
```bash
# 1. Get CSRF Cookie
curl -X GET http://127.0.0.1:8000/sanctum/csrf-cookie -c cookies.txt

# 2. Login
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -b cookies.txt -c cookies.txt \
  -d '{"email":"admin@eods.local","password":"password123"}'
```

## Troubleshooting

### Jika masih tidak bisa login:

1. **Clear semua cache:**
   ```bash
   cd backend
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

2. **Pastikan database berjalan:**
   - XAMPP MySQL harus running
   - Database `eudr_ts` harus ada
   - Port MySQL: 3309 (sesuai .env)

3. **Cek user admin ada:**
   ```bash
   cd backend
   php artisan tinker --execute="echo App\Models\User::where('email', 'admin@eods.local')->first();"
   ```

4. **Jika user tidak ada, jalankan seeder:**
   ```bash
   cd backend
   php artisan db:seed --class=RolePermissionSeeder
   ```

5. **Clear browser cookies:**
   - Buka Developer Tools (F12)
   - Application/Storage tab
   - Clear cookies untuk localhost dan 127.0.0.1

6. **Pastikan kedua server berjalan:**
   - Backend: `http://127.0.0.1:8000`
   - Frontend: `http://127.0.0.1:5173`

7. **Cek CORS di browser console:**
   - Buka Developer Tools (F12)
   - Lihat tab Console dan Network
   - Pastikan tidak ada error CORS

## Perubahan yang Dilakukan

### Backend (.env):
```env
# Session Configuration
SESSION_DRIVER=file                    # Changed from: cookie
SESSION_DOMAIN=                        # Changed from: null
SESSION_SECURE_COOKIE=false            # Added
SESSION_SAME_SITE=lax                  # Added

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,localhost,127.0.0.1
```

### Frontend (.env):
```env
VITE_API_BASE_URL=http://127.0.0.1:8000   # Changed from: empty
```

### Backend (config/cors.php):
- Menambahkan `http://localhost:5173` ke allowed_origins

### Backend (storage/framework/sessions):
- Folder sessions dibuat untuk file-based session storage

## Catatan Penting

1. **Gunakan 127.0.0.1 bukan localhost** untuk konsistensi
2. **Jangan ubah settingan database** (sesuai permintaan)
3. **Session menggunakan file driver** karena tidak ada migration untuk sessions table
4. **CORS sudah dikonfigurasi** untuk development
5. **Sanctum menggunakan cookie-based authentication** (bukan token)

## Alur Autentikasi

1. Frontend request CSRF cookie dari `/sanctum/csrf-cookie`
2. Backend mengirim XSRF-TOKEN cookie
3. Frontend kirim login request dengan XSRF-TOKEN di header
4. Backend validasi dan buat session
5. Backend kirim session cookie
6. Frontend simpan user data di store
7. Request selanjutnya otomatis include session cookie

## Port yang Digunakan

- **Backend API:** 8000
- **Frontend:** 5173
- **MySQL:** 3309
