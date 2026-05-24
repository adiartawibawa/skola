<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Support\Str;

/**
 * GenerateClusterPermissions
 *
 * Generate satu permission untuk Filament Cluster.
 *
 * PENGGUNAAN:
 * -----------
 * # Nama class (suffix 'Cluster' akan di-strip otomatis):
 * php artisan shield:generate-cluster SettingsCluster
 * php artisan shield:generate-cluster UserManagementCluster
 * php artisan shield:generate-cluster Settings
 *
 * HASIL:
 * ------
 * SettingsCluster        → cluster_settings
 * UserManagementCluster  → cluster_user_management
 * Settings               → cluster_settings
 */
class GenerateClusterPermissions extends BaseShieldCommand
{
    protected $signature = 'shield:generate-cluster
                                {cluster     : Nama Cluster class, e.g. SettingsCluster}
                                {--guard=web  : Guard name}
                                {--dry-run   : Preview tanpa menyimpan}';

    protected $description = 'Generate permission cluster_* untuk satu Filament Cluster';

    public function handle(): int
    {
        $this->printHeader('Generate Cluster Permission');

        $input = (string) $this->argument('cluster');
        $guard = (string) $this->option('guard');
        $dryRun = (bool) $this->option('dry-run');
        $permName = $this->resolveClusterPermissionName($input);
        $permission = "cluster_{$permName}";

        $this->line("  <fg=cyan>Input      :</> {$input}");
        $this->line("  <fg=cyan>Permission :</> {$permission}");
        $this->line("  <fg=cyan>Guard      :</> {$guard}");
        $this->newLine();

        $status = $this->createPermission($permission, $guard, $dryRun);

        $this->printPermissionTable([[$permission, $guard, $status]], $dryRun);

        if ($status === self::STATUS_CREATED && ! $dryRun) {
            $this->flushCache();
            $this->line('  <fg=green>✅ Permission dibuat, cache di-flush.</>');
        } elseif ($status === self::STATUS_EXISTS) {
            $this->line('  Permission sudah ada.');
        }

        $this->newLine();
        $this->info('  ⚠  Ingat: Filament tetap menyembunyikan Cluster jika semua');
        $this->line('  resource di dalamnya tidak bisa diakses oleh role tersebut.');
        $this->line('  Pastikan resource di dalam cluster juga mendapat permission yang sesuai.');
        $this->newLine();
        $this->line('  <fg=gray>Pasang trait di Cluster Anda:</>');
        $this->line('  <fg=gray>  use HasShieldClusterAuthorization;</>');

        return self::SUCCESS;
    }

    /**
     * Strip suffix 'Cluster' lalu ubah ke snake_case.
     *
     * SettingsCluster       → settings
     * UserManagementCluster → user_management
     * Settings              → settings
     */
    private function resolveClusterPermissionName(string $input): string
    {
        return Str::of($input)
            ->replaceLast('Cluster', '')
            ->snake()
            ->toString();
    }
}
