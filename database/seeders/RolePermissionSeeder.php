<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'clients.view',
            'clients.manage',
            'devices.view',
            'devices.manage',
            'captures.view',
            'captures.upload',
            'findings.view',
            'findings.edit',
            'reports.view',
            'reports.edit',
            'reports.issue',
            'rules.manage',
            'settings.manage',
            'users.manage',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Administrador: todo (via Gate::before). Se asignan igualmente por claridad.
        Role::findOrCreate('admin')->givePermissionTo(Permission::all());

        // Ingeniero: opera el sistema, no administra usuarios/configuración.
        Role::findOrCreate('engineer')->givePermissionTo([
            'clients.view', 'clients.manage',
            'devices.view', 'devices.manage',
            'captures.view', 'captures.upload',
            'findings.view', 'findings.edit',
            'reports.view', 'reports.edit', 'reports.issue',
        ]);

        // Lectura: solo consulta.
        Role::findOrCreate('reader')->givePermissionTo([
            'clients.view',
            'devices.view',
            'captures.view',
            'findings.view',
            'reports.view',
        ]);
    }
}
