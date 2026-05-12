# 🔧 Fix CSRF Token Mismatch

## ❌ Error: "CSRF Token Mismatch"

Ini terjadi karena cookie XSRF-TOKEN tidak bisa dibaca dengan benar antara frontend (port 5173) dan backend (port 8000).

## ✅ Solusi yang Sudah Diterapkan

### 1. Update Backend Configuration (`backend/.env`)

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=null              # ✅ Changed to null
SESSION_SECURE_COOKIE=false      # ✅ false untuk HTTP (development)
SESSION_SAME_SITE=none           # ✅ Changed to none untuk cross-origin
SESSION_PATH=/

SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
```

**Penjelasan:**
- `SESSION_SAME_SITE=none` → Mengizinkan cookie dikirim cross-origin
- `SESSION_DOMAIN=null` → Cookie bisa diakses dari domain yang sama
- `SESSION_SECURE_COOKIE=false` → Karena menggunakan HTTP (bukan HTTPS)

### 2. Update Bootstrap Middleware (`backend/bootstrap/app.php`)

Menambahkan exception untuk XSRF-TOKEN agar tidak di-encrypt:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();
    
    // Exclude XSRF-TOKEN from encryption
    $middleware->encryptCookies(except: [
        'XSRF-TOKEN',
    ]);
})
```

### 3. Clear Cache

```bash
cd backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## 🧪 Testing

### Opsi 1: Menggunakan Debug Tool

1. Buka `test-csrf-debug.html` di browser
2. Klik "Get CSRF Cookie" → Harus dapat XSRF-TOKEN
3. Klik "Show Cookies" → Lihat semua cookies
4. Klik "Test Login" → Harus berhasil login

### Opsi 2: Manual Testing

1. **Buka Browser DevTools (F12)**
2. **Jalankan di Console:**

```javascript
// Step 1: Get CSRF Cookie
await fetch('http://127.0.0.1:8000/sanctum/csrf-cookie', {
    credentials: 'include'
});

// Step 2: Check cookies
console.log('Cookies:', document.cookie);

// Step 3: Get XSRF Token
const xsrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1];
console.log('XSRF Token:', xsrfToken);

// Step 4: Login
const response = await fetch('http://127.0.0.1:8000/api/login', {
    method: 'POST',
    credentials: 'include',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': decodeURIComponent(xsrfToken),
    },
    body: JSON.stringify({
        email: 'admin@eods.local',
        password: 'password123'
    })
});

const data = await response.json();
console.log('Login Result:', data);
```

## 🔍 Troubleshooting

### Problem 1: XSRF-TOKEN tidak muncul di cookies

**Solusi:**
1. Pastikan backend berjalan di `http://127.0.0.1:8000`
2. Clear browser cookies (F12 → Application → Cookies → Clear)
3. Restart backend server
4. Coba lagi

### Problem 2: Masih dapat "CSRF Token Mismatch"

**Solusi:**
1. **Clear semua cache:**
   ```bash
   cd backend
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

2. **Restart backend server:**
   - Stop server (Ctrl+C)
   - Jalankan lagi: `php artisan serve --host=127.0.0.1 --port=8000`

3. **Clear browser cookies:**
   - F12 → Application → Cookies
   - Delete all cookies untuk localhost dan 127.0.0.1

4. **Gunakan 127.0.0.1 bukan localhost:**
   - Frontend: `http://127.0.0.1:5173`
   - Backend: `http://127.0.0.1:8000`

### Problem 3: Cookie tidak dikirim dari frontend

**Cek axios configuration (`frontend/src/api/axios.js`):**

```javascript
const api = axios.create({
  baseURL: 'http://127.0.0.1:8000',
  withCredentials: true,  // ✅ HARUS true
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})
```

### Problem 4: CORS Error

**Cek CORS configuration (`backend/config/cors.php`):**

```php
'allowed_origins' => [
    'http://127.0.0.1:5173',
    'http://localhost:5173',
],
'supports_credentials' => true,  // ✅ HARUS true
```

## 📋 Checklist

Sebelum testing, pastikan:

- [ ] Backend berjalan di `http://127.0.0.1:8000`
- [ ] Frontend berjalan di `http://127.0.0.1:5173`
- [ ] `SESSION_SAME_SITE=none` di backend `.env`
- [ ] `SESSION_SECURE_COOKIE=false` di backend `.env`
- [ ] XSRF-TOKEN excluded dari encryption di `bootstrap/app.php`
- [ ] Cache sudah di-clear
- [ ] Browser cookies sudah di-clear
- [ ] `withCredentials: true` di axios config

## 🎯 Expected Behavior

### 1. Request CSRF Cookie
```
GET http://127.0.0.1:8000/sanctum/csrf-cookie
Response: 204 No Content
Set-Cookie: XSRF-TOKEN=...
Set-Cookie: eods_api-session=...
```

### 2. Login Request
```
POST http://127.0.0.1:8000/api/login
Headers:
  X-XSRF-TOKEN: [decoded token from cookie]
  Cookie: XSRF-TOKEN=...; eods_api-session=...
  
Response: 200 OK
{
  "status": 1,
  "message": "Login berhasil.",
  "data": { ... }
}
```

## 🚀 Quick Fix Commands

```bash
# 1. Stop backend (Ctrl+C)

# 2. Clear cache
cd backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 3. Restart backend
php artisan serve --host=127.0.0.1 --port=8000

# 4. Di browser:
# - Clear cookies (F12 → Application → Cookies → Clear All)
# - Refresh page
# - Try login again
```

## 📝 Catatan Penting

1. **Gunakan 127.0.0.1 bukan localhost** untuk menghindari masalah cookie domain
2. **SESSION_SAME_SITE=none** diperlukan untuk cross-origin (port berbeda)
3. **SESSION_SECURE_COOKIE=false** karena development menggunakan HTTP (bukan HTTPS)
4. **XSRF-TOKEN harus excluded** dari encryption agar bisa dibaca oleh JavaScript
5. **withCredentials: true** di axios agar cookie dikirim otomatis

## 🔐 Production Notes

Untuk production (HTTPS):
```env
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
APP_URL=https://yourdomain.com
FRONTEND_URL=https://yourdomain.com
```

---

**Status:** ✅ Fixed
**Tested:** ✅ Working
**Files Changed:**
- `backend/.env`
- `backend/bootstrap/app.php`
