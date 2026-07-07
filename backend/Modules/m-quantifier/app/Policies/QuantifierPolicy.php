<?php

declare(strict_types=1);

namespace Modules\Quantifier\Policies;

use App\Models\User;

class QuantifierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin', 'manager', 'superintendent', 'senior-supervisor', 'supervisor', 'senior-staff', 'staff']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('task-update');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('task-update');
    }

    public function delete(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin', 'manager', 'superintendent', 'senior-supervisor', 'supervisor']);
    }
}
