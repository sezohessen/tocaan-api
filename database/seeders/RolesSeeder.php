<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    private const GUARD = 'api';

    /**
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'orders.view',
        'payments.view',
        'products.view',
        'products.manage',
    ];

    public function run(): void
    {
        Artisan::call('permission:cache-reset');

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        $admin = Role::findOrCreate('admin', self::GUARD);
        $admin->syncPermissions(self::PERMISSIONS);

        $manager = Role::findOrCreate('manager', self::GUARD);
        $manager->syncPermissions(['orders.view', 'payments.view', 'products.view']);
    }
}
