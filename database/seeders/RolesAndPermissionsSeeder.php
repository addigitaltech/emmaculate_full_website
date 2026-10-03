<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            'view website content', 'manage website content', 'upload media', 'manage school settings',
            'manage students', 'manage teachers', 'manage academic structure', 'enter assigned results',
            'manage results', 'publish results', 'view own records', 'view linked student records',
            'manage fees', 'manage payments', 'verify payments', 'issue refunds', 'view audit logs', 'manage users and roles',
        ];
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $all = Permission::query()->where('guard_name', 'web')->get();
        $map = [
            'Super Admin' => $all->pluck('name')->all(),
            'Website Admin' => ['view website content', 'manage website content', 'upload media', 'manage school settings'],
            'Results Admin' => ['manage students', 'manage teachers', 'manage academic structure', 'manage results', 'publish results'],
            'Finance Admin' => ['manage fees', 'manage payments', 'verify payments', 'issue refunds', 'view audit logs'],
            'Teacher' => ['view own records', 'enter assigned results'],
            'Student' => ['view own records'],
            'Parent' => ['view linked student records'],
            'Content Editor' => ['view website content', 'manage website content', 'upload media'],
        ];
        foreach ($map as $name => $permissionNames) {
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($permissionNames);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
