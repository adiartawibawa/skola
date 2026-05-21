<?php

namespace App\Filament\Traits;

use Illuminate\Support\Str;

/**
 * HasShieldAuthorization
 *
 * Trait ini menggantikan Filament Shield untuk memeriksa permission.
 *
 * CARA PENGGUNAAN:
 * ----------------
 * 1. Pasang trait ini di Resource Anda:
 *
 *    class UserResource extends Resource
 *    {
 *        use HasShieldAuthorization;
 *
 *        // Opsional: override nama resource jika berbeda dari model
 *        // protected static ?string $permissionResource = 'users';
 *    }
 *
 * 2. Generate permission dengan artisan command:
 *    php artisan shield:generate
 *
 * KONVENSI PENAMAAN PERMISSION:
 * -----------------------------
 * - view_any_{resource}    → bisa lihat list
 * - view_{resource}        → bisa lihat detail
 * - create_{resource}      → bisa create
 * - update_{resource}      → bisa edit
 * - delete_{resource}      → bisa delete (soft delete)
 * - force_delete_{resource}→ bisa force delete
 * - restore_{resource}     → bisa restore
 *
 * SUPER-ADMIN BYPASS:
 * -------------------
 * User dengan role 'super-admin' selalu mendapatkan akses penuh.
 * Ubah nama role di $superAdminRole jika berbeda.
 */
trait HasShieldAuthorization
{
    /**
     * Nama role super admin yang bypass semua permission check.
     * Override di Resource jika perlu.
     */
    protected static string $superAdminRole = 'super-admin';

    /**
     * Override ini di Resource jika nama permission berbeda dari nama model.
     * Contoh: jika model = BlogPost, set 'blog_posts'
     */
    // protected static ?string $permissionResource = null;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Nama resource untuk permission (lowercase + snake_case dari nama model).
     * Contoh: UserResource → 'users', BlogPostResource → 'blog_posts'
     */
    protected static function getPermissionResourceName(): string
    {
        // Jika ada override manual, gunakan itu
        if (isset(static::$permissionResource) && static::$permissionResource !== null) {
            return static::$permissionResource;
        }

        // Derive dari nama model
        $modelClass = static::getModel();
        $modelName = class_basename($modelClass);

        return Str::snake(Str::plural($modelName));
    }

    /**
     * Cek apakah user adalah super-admin (bypass semua permission).
     */
    protected static function isSuperAdmin(): bool
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole(static::$superAdminRole);
    }

    /**
     * Cek satu permission, dengan super-admin bypass.
     */
    protected static function checkPermission(string $ability): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $permission = "{$ability}_".static::getPermissionResourceName();

        return $user->can($permission);
    }

    // -------------------------------------------------------------------------
    // Filament Authorization Methods
    // -------------------------------------------------------------------------

    public static function canViewAny(): bool
    {
        return static::checkPermission('view_any');
    }

    public static function canView($record): bool
    {
        return static::checkPermission('view');
    }

    public static function canCreate(): bool
    {
        return static::checkPermission('create');
    }

    public static function canEdit($record): bool
    {
        return static::checkPermission('update');
    }

    public static function canDelete($record): bool
    {
        return static::checkPermission('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::checkPermission('delete');
    }

    public static function canForceDelete($record): bool
    {
        return static::checkPermission('force_delete');
    }

    public static function canForceDeleteAny(): bool
    {
        return static::checkPermission('force_delete');
    }

    public static function canRestore($record): bool
    {
        return static::checkPermission('restore');
    }

    public static function canRestoreAny(): bool
    {
        return static::checkPermission('restore');
    }
}
