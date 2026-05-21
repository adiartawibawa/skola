<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * RolePermissionSeeder
 *
 * Seeder ini membuat role dan permission awal untuk aplikasi.
 *
 * JALANKAN:
 * php artisan db:seed --class=RolePermissionSeeder
 *
 * ATAU tambahkan di DatabaseSeeder:
 * $this->call(RolePermissionSeeder::class);
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * =========================================================================
     * Konfigurasi — ubah sesuai kebutuhan
     * =========================================================================
     */

    /** Resources dan actions yang akan di-generate */
    protected array $resources = [
        'users'       => ['view_any', 'view', 'create', 'update', 'delete', 'force_delete', 'restore'],
        'roles'       => ['view_any', 'view', 'create', 'update', 'delete'],
        'permissions' => ['view_any', 'view', 'create', 'update', 'delete'],
        // Tambahkan resource lain di sini
    ];

    /** Definisi roles dan permission yang mereka dapatkan */
    protected array $roles = [
        'super-admin' => '*',         // semua permission
        'admin'       => [            // permission spesifik
            'view_any_users', 'view_users', 'create_users', 'update_users',
            'view_any_roles', 'view_roles',
            'view_any_permissions', 'view_permissions',
        ],
        'editor'      => [
            // Tambahkan permission untuk role editor
        ],
    ];

    // =========================================================================

    public function run(): void
    {
        $this->command->info('🛡️  Seeding roles dan permissions...');

        // Flush cache dulu
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Buat semua permissions
        $allPermissions = $this->createPermissions();

        // 2. Buat roles dan assign permissions
        $this->createRoles($allPermissions);

        // 3. Buat super-admin user (opsional)
        $this->createSuperAdminUser();

        $this->command->info('✅ Seeding selesai!');
    }

    protected function createPermissions(): \Illuminate\Support\Collection
    {
        $this->command->info('Creating permissions...');

        foreach ($this->resources as $resource => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name'       => "{$action}_{$resource}",
                    'guard_name' => 'web',
                ]);
            }
        }

        return Permission::all();
    }

    protected function createRoles(\Illuminate\Support\Collection $allPermissions): void
    {
        $this->command->info('Creating roles...');

        foreach ($this->roles as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);

            if ($permissions === '*') {
                // Assign semua permission
                $role->syncPermissions($allPermissions);
                $this->command->line("  → {$roleName}: semua permission");
            } elseif (! empty($permissions)) {
                $role->syncPermissions(
                    Permission::whereIn('name', $permissions)->get()
                );
                $this->command->line("  → {$roleName}: " . count($permissions) . ' permission');
            } else {
                $this->command->line("  → {$roleName}: tidak ada permission");
            }
        }
    }

    protected function createSuperAdminUser(): void
    {
        // Ubah data ini atau hapus method ini jika tidak diperlukan
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'), // ⚠️ Ganti di production!
            ]
        );

        $superAdmin->assignRole('super-admin');

        $this->command->info("  → Super admin user: {$superAdmin->email}");
    }
}
