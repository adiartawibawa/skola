<?php

namespace App\Filament\Traits;

use Illuminate\Support\Str;

/**
 * HasShieldAuthorization
 *
 * Trait untuk otorisasi Filament Resource menggunakan Spatie permission.
 * Super-admin bypass ditangani oleh Gate::before() di AppServiceProvider.
 *
 * PENGGUNAAN:
 * -----------
 * class PostResource extends Resource
 * {
 *     use HasShieldAuthorization;
 *     // → cek: view_any_posts, create_posts, update_posts, ...
 * }
 *
 * OVERRIDE NAMA RESOURCE:
 * -----------------------
 * Override method (bukan property) untuk menghindari konflik PHP trait:
 *
 * class PostResource extends Resource
 * {
 *     use HasShieldAuthorization;
 *
 *     protected static function getPermissionResourceName(): string
 *     {
 *         return 'articles'; // → cek: view_any_articles, create_articles, ...
 *     }
 * }
 *
 * GENERATE PERMISSION:
 * --------------------
 * php artisan shield:generate-resource Post
 */
trait HasShieldAuthorization
{
    /**
     * Derive nama resource dari model class.
     * BlogPost → blog_posts, User → users
     *
     * Override method ini jika nama permission berbeda dari nama model.
     */
    protected static function getPermissionResourceName(): string
    {
        return Str::snake(Str::plural(class_basename(static::getModel())));
    }

    // =========================================================================
    // Filament Authorization Methods
    // =========================================================================

    public static function canViewAny(): bool
    {
        return static::checkPermission('view_any');
    }

    public static function canView(mixed $record): bool
    {
        return static::checkPermission('view');
    }

    public static function canCreate(): bool
    {
        return static::checkPermission('create');
    }

    public static function canEdit(mixed $record): bool
    {
        return static::checkPermission('update');
    }

    public static function canDelete(mixed $record): bool
    {
        return static::checkPermission('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::checkPermission('delete');
    }

    public static function canForceDelete(mixed $record): bool
    {
        return static::checkPermission('force_delete');
    }

    public static function canForceDeleteAny(): bool
    {
        return static::checkPermission('force_delete');
    }

    public static function canRestore(mixed $record): bool
    {
        return static::checkPermission('restore');
    }

    public static function canRestoreAny(): bool
    {
        return static::checkPermission('restore');
    }

    // =========================================================================
    // Internal
    // =========================================================================

    protected static function checkPermission(string $ability): bool
    {
        return auth()->user()?->can(
            "{$ability}_".static::getPermissionResourceName()
        ) ?? false;
    }
}
