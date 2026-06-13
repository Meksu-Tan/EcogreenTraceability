<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'material.view',
            'material.create',
            'material.update',
            'material.delete',
            'storage.view',
            'storage.create',
            'storage.update',
            'storage.delete',
            'supplier.view',
            'supplier.create',
            'supplier.update',
            'supplier.delete',
            'dashboard.view',
            'task-read',
            'task-update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'super-admin'        => Permission::all()->pluck('name')->toArray(),
            'admin'              => Permission::all()->pluck('name')->toArray(),
            'manager'            => Permission::all()->pluck('name')->toArray(),
            'superintendent'     => ['dashboard.view', 'material.view', 'storage.view', 'supplier.view',
                                     'material.create', 'material.update', 'storage.create', 'storage.update',
                                     'supplier.create', 'supplier.update', 'task-read', 'task-update'],
            'senior-supervisor'  => ['dashboard.view', 'material.view', 'storage.view', 'supplier.view',
                                     'material.create', 'material.update', 'storage.create', 'storage.update',
                                     'supplier.create', 'supplier.update', 'task-read', 'task-update'],
            'supervisor'         => ['dashboard.view', 'material.view', 'storage.view', 'supplier.view',
                                     'material.create', 'material.update', 'storage.create', 'storage.update',
                                     'supplier.create', 'supplier.update', 'task-read', 'task-update'],
            'senior-staff'       => ['dashboard.view', 'material.view', 'storage.view', 'supplier.view', 'task-read'],
            'staff'              => ['dashboard.view', 'material.view', 'storage.view', 'supplier.view', 'task-read'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@eods.local'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make(Config::get('app.admin_default_password')),
            ]
        );
        $admin->assignRole('super-admin');

        $this->command->info('Roles, Permissions, and Admin user seeded successfully!');
        $this->command->info('Admin login: admin@eods.local / ********');
    }
}
