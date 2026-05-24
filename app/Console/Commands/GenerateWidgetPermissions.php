<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;

/**
 * GenerateWidgetPermissions
 *
 * Generate satu permission untuk Filament Widget.
 *
 * PENGGUNAAN:
 * -----------
 * # Nama class (suffix 'Widget' akan di-strip otomatis):
 * php artisan shield:generate-widget StatsOverviewWidget
 * php artisan shield:generate-widget TeacherChart
 * php artisan shield:generate-widget StudentSummaryWidget
 *
 * HASIL:
 * ------
 * StatsOverviewWidget  → widget_stats_overview
 * TeacherChartWidget   → widget_teacher_chart
 * StudentSummaryWidget → widget_student_summary
 * TeacherChart         → widget_teacher_chart
 *
 * KONVENSI:
 * ---------
 * Nama permission harus konsisten dengan nilai yang dikembalikan oleh
 * HasShieldWidgetAuthorization::getPermissionWidgetName() di class Widget Anda.
 */
class GenerateWidgetPermissions extends BaseShieldCommand
{
    protected $signature = 'shield:generate-widget
                                {widget      : Nama Widget class, e.g. StatsOverviewWidget}
                                {--guard=web  : Guard name}
                                {--dry-run   : Preview tanpa menyimpan}';

    protected $description = 'Generate permission widget_* untuk satu Filament Widget';

    public function handle(): int
    {
        $this->printHeader('Generate Widget Permission');

        $input = (string) $this->argument('widget');
        $guard = (string) $this->option('guard');
        $dryRun = (bool) $this->option('dry-run');
        $permName = $this->resolveWidgetPermissionName($input);
        $permission = "widget_{$permName}";

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
        $this->line('  <fg=gray>Pasang trait di Widget Anda:</>');
        $this->line('  <fg=gray>  use HasShieldWidgetAuthorization;</>');

        return self::SUCCESS;
    }

    /**
     * Strip suffix 'Widget' lalu ubah ke snake_case.
     *
     * StatsOverviewWidget → stats_overview
     * TeacherChartWidget  → teacher_chart
     * TeacherChart        → teacher_chart  (tidak ada Widget suffix, tetap benar)
     */
    private function resolveWidgetPermissionName(string $input): string
    {
        return Str::of($input)
            ->replaceLast('Widget', '')
            ->snake()
            ->toString();
    }
}
