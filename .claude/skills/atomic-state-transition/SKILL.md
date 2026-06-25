---
name: atomic-state-transition
description: Use this skill whenever a task involves updating a status field, state change, or any transition between states. Prevents race conditions with compare-and-swap pattern.
user-invocable: true
---

# Atomic State Transition

## Kapan Digunakan
Aktifkan skill ini setiap kali ada task yang menyebut:
- "update status", "ubah status", "state change"
- Transisi antar state: `draft→active`, `pending→approved`, `submitted→done`
- Operasi yang bisa terjadi secara concurrent

## Mengapa Penting
Direct update (`$model->update(['status' => 'done'])`) tidak aman untuk concurrent access.
Dua request bersamaan bisa overwrite satu sama lain → data corrupt.

## Pattern Wajib: Compare-and-Swap

```php
// BENAR: atomic CAS — aman untuk concurrent access
$affected = Model::where('id', $id)
    ->where('status', 'approved')       // expected current state
    ->update(['status' => 'completed']); // new state

if ($affected === 0) {
    throw new \DomainException('Status sudah berubah — coba lagi.');
    // atau: return response()->json(['message' => 'Conflict'], 409)
}

// SALAH: non-atomic — race condition
$model->update(['status' => 'completed']); // JANGAN
$model->status = 'completed';              // JANGAN
$model->save();                            // JANGAN
```

## Untuk Bulk Operations

```php
// Tambahkan pessimistic lock untuk operasi yang concurrency-sensitive
$plan = AssessmentPlan::lockForUpdate()->findOrFail($id);

// Jika lock timeout → kembalikan 409 Conflict ke client
```

## State Machine Pattern

Definisikan valid transitions sebelum implementasi:

```
draft → submitted   (oleh user)
submitted → approved (oleh manager)
submitted → rejected (oleh manager)
approved → completed (oleh sistem)

TIDAK VALID:
draft → approved (skip submitted)
completed → draft (tidak bisa balik)
```

## HTTP Response untuk Conflict

```php
// Controller
if ($affected === 0) {
    return ApiResponse::error('Status sudah berubah. Refresh dan coba lagi.', 409);
}
```

## Skill Terkait
- `systematic-debugging` — jika ada race condition bug
- `writing-plans` — plan state machine sebelum implementasi
