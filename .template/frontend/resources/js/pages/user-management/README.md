# pages/user-management — CRUD Template
READ-ONLY reference. Template paling sering digunakan.

## Isi
- `index.vue` — halaman list: search, filter, table, pagination, action buttons
- `[id].vue` — halaman detail: tabs, section panels

## Pattern Utama
- Load data di `onMounted` via store action
- Update via store action (bukan axios langsung)
- Filter sebagai computed dari store state
- Action buttons: lihat detail, edit, hapus (soft delete)

## Cara Adaptasi
1. Copy struktur template
2. Ganti nama (User → Apprentice, dll)
3. Sesuaikan columns dengan schema sprint aktif
4. Gunakan Ecogreen theme tokens
