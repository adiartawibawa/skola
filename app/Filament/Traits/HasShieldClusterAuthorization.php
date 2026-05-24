<?php

namespace App\Filament\Traits;

use Illuminate\Support\Str;

/**
 * HasShieldClusterAuthorization
 *
 * Trait untuk mengontrol akses Filament Cluster menggunakan Spatie permission.
 * Super-admin bypass ditangani oleh Gate::before() di AppServiceProvider.
 *
 * PENGGUNAAN:
 * -----------
 * class SettingsCluster extends Cluster
 * {
 *     use HasShieldClusterAuthorization;
 *     // → cek permission: "cluster_settings"
 * }
 *
 * KONVENSI NAMING:
 * ----------------
 * Suffix 'Cluster' selalu di-strip sebelum di-snake_case:
 *   SettingsCluster     → cluster_settings
 *   UserManagement      → cluster_user_management  (tanpa suffix pun benar)
 *   UserManagementCluster → cluster_user_management
 *
 * PENTING — DOUBLE GATE:
 * ----------------------
 * Filament menyembunyikan Cluster jika SEMUA resource/page di dalamnya
 * tidak bisa diakses — meskipun canAccess() cluster sudah return true.
 * Pastikan minimal satu resource di dalam cluster juga bisa diakses oleh role
 * yang bersangkutan.
 *
 * OVERRIDE NAMA PERMISSION:
 * -------------------------
 * Gunakan method override (bukan property) untuk menghindari konflik PHP trait:
 *
 * class SettingsCluster extends Cluster
 * {
 *     use HasShieldClusterAuthorization;
 *
 *     protected static function getPermissionClusterName(): string
 *     {
 *         return 'pengaturan'; // → cek: "cluster_pengaturan"
 *     }
 * }
 *
 * GENERATE PERMISSION:
 * --------------------
 * php artisan shield:generate-cluster SettingsCluster
 */
trait HasShieldClusterAuthorization
{
    /**
     * Derive nama permission dari nama class.
     * Suffix 'Cluster' di-strip, lalu di-snake_case.
     *
     * SettingsCluster       → settings
     * UserManagementCluster → user_management
     * UserManagement        → user_management
     *
     * Override method ini jika nama permission berbeda.
     */
    protected static function getPermissionClusterName(): string
    {
        return Str::of(class_basename(static::class))
            ->replaceLast('Cluster', '')
            ->snake()
            ->toString();
    }

    /**
     * Dipanggil Filament untuk menentukan apakah Cluster dapat diakses.
     *
     * Catatan: Filament akan tetap menyembunyikan Cluster jika semua
     * resource/page di dalamnya tidak dapat diakses, terlepas dari
     * nilai yang dikembalikan method ini.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can(
            'cluster_'.static::getPermissionClusterName()
        ) ?? false;
    }
}
