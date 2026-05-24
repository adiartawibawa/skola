<?php

namespace App\Filament\Traits;

use Illuminate\Support\Str;

/**
 * HasShieldWidgetAuthorization
 *
 * Trait untuk melindungi visibilitas Filament Widget menggunakan Spatie permission.
 * Super-admin bypass ditangani oleh Gate::before() di AppServiceProvider.
 *
 * PENGGUNAAN:
 * -----------
 * class StatsOverviewWidget extends BaseWidget
 * {
 *     use HasShieldWidgetAuthorization;
 *     // → cek permission: "widget_stats_overview"
 * }
 *
 * class TeacherChartWidget extends BaseWidget
 * {
 *     use HasShieldWidgetAuthorization;
 *     // → cek permission: "widget_teacher_chart"
 * }
 *
 * KONVENSI NAMING:
 * ----------------
 * Suffix 'Widget' selalu di-strip sebelum di-snake_case:
 *   StatsOverviewWidget  → widget_stats_overview
 *   TeacherChartWidget   → widget_teacher_chart
 *   StudentSummaryWidget → widget_student_summary
 *   StatsOverview        → widget_stats_overview  (tanpa suffix pun benar)
 *
 * OVERRIDE NAMA PERMISSION:
 * -------------------------
 * Override method (bukan property) untuk menghindari konflik PHP trait:
 *
 * class StatsOverviewWidget extends BaseWidget
 * {
 *     use HasShieldWidgetAuthorization;
 *
 *     protected static function getPermissionWidgetName(): string
 *     {
 *         return 'statistik'; // → cek: "widget_statistik"
 *     }
 * }
 *
 * GENERATE PERMISSION:
 * --------------------
 * php artisan shield:generate-widget StatsOverviewWidget
 * php artisan shield:generate-widget TeacherChart
 */
trait HasShieldWidgetAuthorization
{
    /**
     * Derive nama permission dari nama class.
     * Suffix 'Widget' di-strip, lalu di-snake_case.
     *
     * StatsOverviewWidget → stats_overview
     * TeacherChartWidget  → teacher_chart
     * StatsOverview       → stats_overview
     *
     * Override method ini jika nama permission berbeda.
     */
    protected static function getPermissionWidgetName(): string
    {
        return Str::of(class_basename(static::class))
            ->replaceLast('Widget', '')
            ->snake()
            ->toString();
    }

    /**
     * Dipanggil Filament untuk menentukan apakah widget ditampilkan.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can(
            'widget_'.static::getPermissionWidgetName()
        ) ?? false;
    }
}
