<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

/**
 * GeneratePermissions
 *
 * Auto-generate Spatie permissions berdasarkan daftar resource yang terdaftar.
 *
 * PENGGUNAAN:
 * -----------
 * # Generate untuk semua resource yang sudah dikonfigurasi di command ini:
 * php artisan shield:generate
 *
 * # Preview tanpa menyimpan ke database:
 * php artisan shield:generate --dry-run
 *
 * # Generate hanya untuk resource tertentu:
 * php artisan shield:generate --resource=users
 */
class GeneratePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shield:generate
                                {--dry-run : Preview permission tanpa menyimpan}
                                {--resource= : Generate hanya untuk resource tertentu}
                                {--guard=web : Guard name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Spatie permissions untuk semua Filament resources';

    /**
     * =========================================================================
     * KONFIGURASI — Daftarkan resource Anda di sini
     * =========================================================================
     *
     * Format: 'nama_resource' => [aksi1, aksi2, ...]
     *
     * Atau gunakan $defaultActions untuk semua resource.
     */
    protected array $resources = [
        'users' => ['view_any', 'view', 'create', 'update', 'delete', 'force_delete', 'restore'],
        'roles' => ['view_any', 'view', 'create', 'update', 'delete'],
        'permissions' => ['view_any', 'view', 'create', 'update', 'delete'],
        // Tambahkan resource Anda di sini:
        // 'posts'    => ['view_any', 'view', 'create', 'update', 'delete'],
        // 'comments' => ['view_any', 'view', 'create', 'update', 'delete'],
    ];

    /**
     * Aksi default jika resource tidak mendefinisikan aksi sendiri.
     */
    protected array $defaultActions = [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $guard = $this->option('guard');
        $dryRun = $this->option('dry-run');
        $filter = $this->option('resource');

        $this->info('🛡️  Shield Permission Generator');
        $this->info('Guard: '.$guard);
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠  DRY RUN — tidak ada yang disimpan ke database.');
            $this->newLine();
        }

        $resources = $filter
            ? array_filter($this->resources, fn ($key) => $key === $filter, ARRAY_FILTER_USE_KEY)
            : $this->resources;

        if (empty($resources)) {
            $this->error("Resource '{$filter}' tidak ditemukan di konfigurasi.");

            return self::FAILURE;
        }

        $toCreate = [];
        $alreadyExists = [];

        foreach ($resources as $resource => $actions) {
            foreach ($actions as $action) {
                $name = "{$action}_{$resource}";

                if (Permission::where('name', $name)->where('guard_name', $guard)->exists()) {
                    $alreadyExists[] = $name;
                } else {
                    $toCreate[] = ['name' => $name, 'guard_name' => $guard];
                }
            }
        }

        // Tampilkan tabel preview
        $this->table(
            ['Permission', 'Guard', 'Status'],
            array_merge(
                array_map(fn ($p) => [$p['name'], $p['guard_name'], '✅ Will create'], $toCreate),
                array_map(fn ($n) => [$n, $guard, '⏭  Already exists'], $alreadyExists),
            )
        );

        $this->info(sprintf(
            'Total: %d akan dibuat, %d sudah ada.',
            count($toCreate),
            count($alreadyExists)
        ));

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry run selesai. Jalankan tanpa --dry-run untuk menyimpan.');

            return self::SUCCESS;
        }

        if (empty($toCreate)) {
            $this->info('Tidak ada permission baru untuk dibuat.');

            return self::SUCCESS;
        }

        if (! $this->confirm(sprintf('Buat %d permission baru?', count($toCreate)), true)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        // Simpan ke database
        $bar = $this->output->createProgressBar(count($toCreate));
        $bar->start();

        foreach ($toCreate as $data) {
            Permission::create($data);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Flush permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('✅ '.count($toCreate).' permission berhasil dibuat.');
        $this->info('🗑️  Permission cache telah di-flush.');

        return self::SUCCESS;

    }
}
