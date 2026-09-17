<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        $permission = Permission::firstOrCreate([
            'name' => 'invoice.settle',
            'guard_name' => $guard,
        ]);

        $role = Role::firstOrCreate([
            'name' => 'accounting-admin',
            'guard_name' => $guard,
        ]);

        $role->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        $role = Role::where('name', 'accounting-admin')->where('guard_name', $guard)->first();
        if ($role) {
            $role->delete();
        }

        $permission = Permission::where('name', 'invoice.settle')->where('guard_name', $guard)->first();
        if ($permission) {
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
