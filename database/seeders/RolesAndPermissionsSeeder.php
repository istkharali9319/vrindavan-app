<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates every role and permission and syncs the matrix from App\Enums\Role::permissions().
 *
 * Safe to run repeatedly; it is the way to apply permission matrix changes.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        foreach (Role::cases() as $role) {
            RoleModel::findOrCreate($role->value, 'web')->syncPermissions(
                array_map(fn (Permission $permission): string => $permission->value, $role->permissions())
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
