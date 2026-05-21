# EODS Documentation

## Overview

**Enterprise Oil Data System (EODS)** is a modular platform for managing oil material transactions, storage, suppliers, and traceability.

### Architecture
- **Backend**: Laravel 12, nWidart Modular, Sanctum Token Auth, Spatie Permission
- **Frontend**: Vue.js 3 SPA, Vite, Pinia, Vue Router, Tailwind CSS
- **Database**: MySQL, SQLite for testing
- **API**: REST (port 8000), SPA (port 5173)

### API Base URL
```
http://127.0.0.1:8000/api/v1
```

### Authentication
Bearer token via Sanctum:
```
POST /api/login
POST /api/logout
GET  /api/user
```

### Response Format
```json
{
  "status": 1,
  "data": [],
  "message": "Success"
}
```
