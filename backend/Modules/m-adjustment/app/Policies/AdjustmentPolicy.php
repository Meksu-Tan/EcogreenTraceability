<?php

declare(strict_types=1);

namespace Modules\Adjustment\Policies;

use App\Models\User;

class AdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin', 'manager', 'superintendent', 'senior-supervisor', 'supervisor', 'senior-staff', 'staff']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'senior-staff', 'senior-supervisor', 'superintendent', 'supervisor']);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'senior-supervisor', 'superintendent', 'supervisor']);
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
