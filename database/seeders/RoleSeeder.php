<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SimpleRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roleClass = config('rakshak.models.role', \Roshify\LaravelRakshak\Models\Role::class);
        $permissionClass = config('rakshak.models.permission', \Roshify\LaravelRakshak\Models\Permission::class);

        Schema::disableForeignKeyConstraints();
        $roleClass::truncate();
        // Note: don't truncate permissions if they are shared across seeds
        Schema::enableForeignKeyConstraints();

        // Create roles
        $roles = [
            'super-admin' => 'Full access to everything',
            'manager'     => 'Manager-level access',
            'viewer'      => 'Read-only access',
        ];

        foreach ($roles as $slug => $desc) {
            $role = $roleClass::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucwords(str_replace('-', ' ', $slug)), 'description' => $desc]
            );

            // Example: assign permissions by slug (adjust to your permission naming)
            if ($slug === 'super-admin') {
                // Super-admin: attach all permissions (you may instead use config 'super_admin' to bypass checks)
                $allPermissions = $permissionClass::all()->pluck('id')->toArray();
                if (!empty($allPermissions)) {
                    $role->permissions()->sync($allPermissions);
                }
            } elseif ($slug === 'manager') {
                // Manager: common permissions (assumes these slugs exist)
                $permSlugs = ['clients.read', 'clients.update', 'documents.read', 'reports.read'];
                $permIds = $permissionClass::whereIn('slug', $permSlugs)->pluck('id')->toArray();
                if (!empty($permIds)) {
                    $role->permissions()->sync($permIds);
                }
            } else { // viewer
                $permSlugs = ['clients.read', 'documents.read', 'reports.read'];
                $permIds = $permissionClass::whereIn('slug', $permSlugs)->pluck('id')->toArray();
                if (!empty($permIds)) {
                    $role->permissions()->sync($permIds);
                }
            }
        }
    }
}
