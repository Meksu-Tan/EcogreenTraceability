<?php
declare(strict_types=1);

namespace Modules\TsTransfer\Policies;

use App\Models\User;

class TransferPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'senior-staff', 'senior-supervisor', 'superintendent', 'supervisor']);
    }

    public function approve(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'senior-supervisor', 'superintendent', 'supervisor']);
    }

    public function reject(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'senior-supervisor', 'superintendent', 'supervisor']);
    }

    public function cancel(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
