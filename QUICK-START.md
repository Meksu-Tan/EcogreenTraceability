# 🚀 Quick Start - EODS Login Fix

## ⚡ Langkah Cepat

### 1. Start Backend
```bash
# Double-click file ini:
start-backend.bat

# Atau manual:
cd backend
php artisan serve --host=127.0.0.1 --port=8000
```

### 2. Start Frontend
```bash
# Double-click file ini:
start-frontend.bat

# Atau manual:
cd frontend
npm run dev
```

### 3. Login
- Buka: `http://127.0.0.1:5173`
- Email: `admin@eods.local`
- Password: `password123`

## ❌ Jika Masih Error "CSRF Token Mismatch"

### Solusi 1: Clear Everything
```bash
# 1. Stop backend (Ctrl+C)

# 2. Clear cache
cd backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 3. Restart backend
php artisan serve --host=127.0.0.1 --port=8000

# 4. Clear browser cookies
# F12 → Application → Cookies → Clear All

# 5. Refresh browser
```

### Solusi 2: Gunakan Debug Tool
1. Buka `test-csrf-debug.html` di browser
2. Ikuti step by step
3. Lihat error di console

### Solusi 3: Pastikan Konfigurasi Benar

**Backend `.env` harus:**
```env
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=null
```

**Frontend `.env` harus:**
```env
VITE_API_BASE_URL=http://127.0.0.1:8000
```

## 📋 Checklist

- [ ] Backend running di `http://127.0.0.1:8000`
- [ ] Frontend running di `http://127.0.0.1:5173`
- [ ] Cache sudah di-clear
- [ ] Browser cookies sudah di-clear
- [ ] Gunakan **127.0.0.1** bukan localhost

## 🔧 Files yang Sudah Diperbaiki

1. ✅ `backend/.env` - Session config
2. ✅ `backend/bootstrap/app.php` - XSRF-TOKEN exception
3. ✅ `frontend/.env` - API URL
4. ✅ `backend/config/cors.php` - CORS config

## 📚 Dokumentasi Lengkap

- `README-LOGIN-FIX.md` - Overview perbaikan
- `FIX-CSRF-TOKEN-MISMATCH.md` - Detail fix CSRF
- `CARA-MENJALANKAN.md` - Panduan lengkap
- `test-csrf-debug.html` - Debug tool

## 💡 Tips

1. **Selalu gunakan 127.0.0.1** bukan localhost
2. **Clear cache** setiap kali ubah `.env`
3. **Clear browser cookies** jika masih error
4. **Cek console browser** (F12) untuk error detail

---

**Status:** ✅ Ready
**Last Updated:** 2026-05-12
