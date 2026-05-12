<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // Material
            'material.view',
            'material.create',
            'material.update',
            'material.delete',
            // Storage
            'storage.view',
            'storage.create',
            'storage.update',
            'storage.delete',
            // Supplier
            'supplier.view',
            'supplier.create',
            'supplier.update',
            'supplier.delete',
            // Dashboard
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Define roles and their permissions
        $roles = [
            'super-admin'        => Permission::all()->pluck('name')->toArray(),
            'admin'              => Permission::all()->pluck('name')->toArray(),
            'manager'            => Permission::all()->pluck('name')->toArray(),
            'superintendent'     => ['dashboard.view', 'material.view', 'storage.view', 'supplier.view',
                                     'material.create', 'material.update', 'storage.create', 'storage.update',
                                     'supplier.create', 'supplier.update'],
            'senior-supervisor'  => ['dashboard.view', 'material.view', 'storage.view', 'supplier.view',
                                     'material.create', 'material.update', 'storage.create', 'storage.update',
                                     'supplier.create', 'supplier.update'],
            'supervisor'         => ['dashboard.view', 'material.view', 'storage.view', 'supplier.view',
                                     'material.create', 'material.update', 'storage.create', 'storage.update',
                                     'supplier.create', 'supplier.update'],
            'senior-staff'       => ['dashboard.view', 'material.view', 'storage.view', 'supplier.view'],
            'staff'              => ['dashboard.view', 'material.view', 'storage.view', 'supplier.view'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        // Create default admin user if not exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@eods.local'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('password123'),
            ]
        );
        $admin->assignRole('super-admin');

        $this->command->info('Roles, Permissions, and Admin user seeded successfully!');
        $this->command->info('Admin login: admin@eods.local / password123');
    }
}
