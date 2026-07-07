<?php

declare(strict_types=1);

namespace Modules\TsPackage\Policies;

use App\Models\User;

class PackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin', 'manager', 'superintendent', 'senior-supervisor', 'supervisor', 'senior-staff', 'staff']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('task-update');
    }

    public function delete(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin', 'manager', 'superintendent', 'senior-supervisor', 'supervisor']);
    }
}
