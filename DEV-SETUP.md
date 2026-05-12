# Local Dev Setup

Database settings:

- Laravel Master:
  - Host: `172.16.11.101`
  - Port: `3309`
  - Database: `eudr_ts`
  - User: `eo_trace`
- Backend API and Frontend:
  - Host: `127.0.0.1`
  - Port: `3309`
  - Database: `eudr_ts`
  - User: `root`

Ports:

- Laravel Master: `http://127.0.0.1:8001`
- Laravel Backend API: `http://127.0.0.1:8000`
- Vue Frontend: `http://127.0.0.1:5173`

The backend health check uses `/api/user`; `401 Unauthenticated` means the API is running and waiting for login.

Start everything:

```powershell
.\start-dev.ps1
```

Stop everything:

```powershell
.\stop-dev.ps1
```

If database settings change, update `.env` for Laravel Master and `backend/.env` for the Backend API, then rerun `.\start-dev.ps1`.
