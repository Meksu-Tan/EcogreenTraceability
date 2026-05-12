# 🔧 Perbaikan Login EODS

## ✅ Masalah yang Diperbaiki

Setelah decoupling dari monorepo ke multirepo (backend & frontend terpisah), login tidak berfungsi karena:

1. **Session driver tidak cocok** - Backend menggunakan `cookie` tapi tidak ada konfigurasi yang tepat
2. **Frontend API URL kosong** - Frontend tidak tahu kemana harus request
3. **Session domain tidak diset** - Cookie tidak bisa dishare antara port 5173 dan 8000
4. **Folder sessions tidak ada** - File-based session butuh folder storage

## 🔨 Perubahan yang Dilakukan

### 1. Backend Configuration (`backend/.env`)
```env
SESSION_DRIVER=file                    # ✅ Changed from: cookie
SESSION_DOMAIN=                        # ✅ Changed from: null  
SESSION_SECURE_COOKIE=false            # ✅ Added
SESSION_SAME_SITE=lax                  # ✅ Added
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,localhost,127.0.0.1
```

### 2. Frontend Configuration (`frontend/.env`)
```env
VITE_API_BASE_URL=http://127.0.0.1:8000   # ✅ Changed from: empty
```

### 3. Backend CORS (`backend/config/cors.php`)
- ✅ Menambahkan `http://localhost:5173` ke allowed origins

### 4. Storage Directory
- ✅ Membuat folder `backend/storage/framework/sessions`

## 🚀 Cara Menjalankan

### Opsi 1: Menggunakan Batch Files (Mudah)

1. **Jalankan Backend:**
   - Double-click `start-backend.bat`
   - Backend akan berjalan di `http://127.0.0.1:8000`

2. **Jalankan Frontend:**
   - Double-click `start-frontend.bat`
   - Frontend akan berjalan di `http://127.0.0.1:5173`

3. **Clear Cache (jika perlu):**
   - Double-click `clear-cache.bat`

### Opsi 2: Manual

**Terminal 1 - Backend:**
```bash
cd backend
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 - Frontend:**
```bash
cd frontend
npm run dev
```

## 🔐 Login Credentials

- **Email:** `admin@eods.local`
- **Password:** `password123`

## 🧪 Testing

### Test dengan Browser:
1. Buka `http://127.0.0.1:5173`
2. Login dengan kredensial di atas
3. Seharusnya berhasil masuk

### Test dengan Test File:
1. Buka `test-login.html` di browser
2. Klik tombol "Login"
3. Lihat hasilnya

## 🐛 Troubleshooting

### Jika masih tidak bisa login:

1. **Clear cache backend:**
   ```bash
   cd backend
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Clear browser cookies:**
   - Tekan F12 → Application → Cookies
   - Hapus semua cookies untuk localhost dan 127.0.0.1

3. **Restart kedua server:**
   - Stop backend (Ctrl+C)
   - Stop frontend (Ctrl+C)
   - Jalankan ulang keduanya

4. **Cek user admin ada:**
   ```bash
   cd backend
   php artisan tinker --execute="echo App\Models\User::where('email', 'admin@eods.local')->first();"
   ```

5. **Jika user tidak ada:**
   ```bash
   cd backend
   php artisan db:seed --class=RolePermissionSeeder
   ```

## 📝 Catatan Penting

- ✅ **Tidak ada perubahan database** (sesuai permintaan)
- ✅ **Gunakan 127.0.0.1** bukan localhost untuk konsistensi
- ✅ **Session menggunakan file storage** karena lebih reliable untuk development
- ✅ **CORS sudah dikonfigurasi** untuk cross-origin requests
- ✅ **Sanctum menggunakan cookie-based auth** (bukan token)

## 📊 Alur Autentikasi

```
1. Frontend → GET /sanctum/csrf-cookie
2. Backend → Send XSRF-TOKEN cookie
3. Frontend → POST /api/login (with XSRF-TOKEN)
4. Backend → Validate & create session
5. Backend → Send session cookie
6. Frontend → Store user data
7. Future requests → Auto include session cookie
```

## 🎯 Files yang Diubah

1. ✅ `backend/.env` - Session & Sanctum config
2. ✅ `frontend/.env` - API base URL
3. ✅ `backend/config/cors.php` - CORS origins
4. ✅ `backend/storage/framework/sessions/` - Created folder

## 📞 Support

Jika masih ada masalah, cek:
- Console browser (F12) untuk error JavaScript
- Laravel logs di `backend/storage/logs/laravel.log`
- Network tab di browser untuk melihat request/response

---

**Status:** ✅ Ready to use
**Tested:** ✅ Login working
**Database:** ✅ No changes needed
