<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;

/**
 * GeneratePagePermissions
 *
 * Generate satu permission untuk Filament Page.
 *
 * PENGGUNAAN:
 * -----------
 * # Nama class (suffix 'Page' akan di-strip otomatis):
 * php artisan shield:generate-page Dashboard
 * php artisan shield:generate-page DashboardPage
 * php artisan shield:generate-page ReportsPage
 *
 * HASIL:
 * ------
 * Dashboard      → page_dashboard
 * DashboardPage  → page_dashboard
 * ReportsPage    → page_reports
 * SettingsPage   → page_settings
 *
 * KONVENSI:
 * ---------
 * Nama permission harus konsisten dengan nilai yang dikembalikan oleh
 * HasShieldPageAuthorization::getPermissionPageName() di class Page Anda.
 */
class GeneratePagePermissions extends BaseShieldCommand
{
    protected $signature = 'shield:generate-page
                                {page       : Nama Page class, e.g. Dashboard atau DashboardPage}
                                {--guard=web : Guard name}
                                {--dry-run  : Preview tanpa menyimpan}';

    protected $description = 'Generate permission page_* untuk satu Filament Page';

    public function handle(): int
    {
        $this->printHeader('Generate Page Permission');

        $input = (string) $this->argument('page');
        $guard = (string) $this->option('guard');
        $dryRun = (bool) $this->option('dry-run');
        $permName = $this->resolvePagePermissionName($input);
        $permission = "page_{$permName}";

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
        $this->line('  <fg=gray>Pasang trait di Page Anda:</>');
        $this->line('  <fg=gray>  use HasShieldPageAuthorization;</>');

        return self::SUCCESS;
    }

    /**
     * Strip suffix 'Page' lalu ubah ke snake_case.
     *
     * DashboardPage → dashboard
     * Dashboard     → dashboard
     * ReportsPage   → reports
     */
    private function resolvePagePermissionName(string $input): string
    {
        return Str::of($input)
            ->replaceLast('Page', '')
            ->snake()
            ->toString();
    }
}
