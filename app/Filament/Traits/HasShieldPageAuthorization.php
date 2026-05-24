<?php

namespace App\Filament\Traits;

use Illuminate\Support\Str;

/**
 * HasShieldPageAuthorization
 *
 * Trait untuk melindungi akses Filament Page menggunakan Spatie permission.
 * Super-admin bypass ditangani oleh Gate::before() di AppServiceProvider.
 *
 * PENGGUNAAN:
 * -----------
 * class DashboardPage extends Page
 * {
 *     use HasShieldPageAuthorization;
 *     // → cek permission: "page_dashboard"
 * }
 *
 * class ReportsPage extends Page
 * {
 *     use HasShieldPageAuthorization;
 *     // → cek permission: "page_reports"
 * }
 *
 * KONVENSI NAMING:
 * ----------------
 * Suffix 'Page' selalu di-strip sebelum di-snake_case:
 *   Dashboard     → page_dashboard
 *   DashboardPage → page_dashboard
 *   ReportsPage   → page_reports
 *   SettingsPage  → page_settings
 *
 * OVERRIDE NAMA PERMISSION:
 * -------------------------
 * Override method (bukan property) untuk menghindari konflik PHP trait:
 *
 * class ReportsPage extends Page
 * {
 *     use HasShieldPageAuthorization;
 *
 *     protected static function getPermissionPageName(): string
 *     {
 *         return 'laporan'; // → cek: "page_laporan"
 *     }
 * }
 *
 * GENERATE PERMISSION:
 * --------------------
 * php artisan shield:generate-page Dashboard
 * php artisan shield:generate-page ReportsPage
 */
trait HasShieldPageAuthorization
{
    /**
     * Derive nama permission dari nama class.
     * Suffix 'Page' di-strip, lalu di-snake_case.
     *
     * DashboardPage → dashboard
     * ReportsPage   → reports
     * Dashboard     → dashboard
     *
     * Override method ini jika nama permission berbeda.
     */
    protected static function getPermissionPageName(): string
    {
        return Str::of(class_basename(static::class))
            ->replaceLast('Page', '')
            ->snake()
            ->toString();
    }

    /**
     * Dipanggil Filament untuk menentukan apakah page dapat diakses.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can(
            'page_'.static::getPermissionPageName()
        ) ?? false;
    }
}
