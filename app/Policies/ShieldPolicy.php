<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

/**
 * ShieldPolicy
 *
 * Base class untuk semua Policy yang didelegasikan ke Spatie permission.
 * Super-admin bypass ditangani oleh Gate::before() di AppServiceProvider — tidak di sini.
 *
 * PENGGUNAAN:
 * -----------
 * 1. Generate via command (direkomendasikan):
 *    php artisan shield:generate-resource Post --with-policy --register
 *
 * 2. Atau buat manual — cukup extend, tidak perlu tulis ulang method apapun:
 *
 *    class PostPolicy extends ShieldPolicy
 *    {
 *        // Semua method sudah tersedia: viewAny, view, create, update,
 *        // delete, forceDelete, restore, deleteAny, forceDeleteAny, restoreAny
 *    }
 *
 * NAMA RESOURCE:
 * --------------
 * Diturunkan otomatis dari nama Policy class:
 *   UserPolicy     → users
 *   BlogPostPolicy → blog_posts
 *   ArticlePolicy  → articles
 *
 * Override jika perlu:
 *   protected function getResourceName(): string { return 'posts'; }
 *
 * OVERRIDE SATU METHOD:
 * ---------------------
 * class PostPolicy extends ShieldPolicy
 * {
 *     // Hanya pemilik yang bisa mengedit, meski punya permission update_posts
 *     public function update($user, $model): bool
 *     {
 *         return $user->id === $model->created_by
 *             && parent::update($user, $model);
 *     }
 * }
 */
abstract class ShieldPolicy
{
    use HandlesAuthorization;

    // =========================================================================
    // Resource Name Resolution
    // =========================================================================

    /**
     * Derive nama resource dari nama Policy class.
     * PostPolicy     → posts
     * BlogPostPolicy → blog_posts
     *
     * Override di Policy turunan jika nama resource berbeda.
     */
    protected function getResourceName(): string
    {
        $policyClass = class_basename(static::class);
        $modelName = Str::replaceLast('Policy', '', $policyClass);

        return Str::snake(Str::plural($modelName));
    }

    /**
     * Cek permission Spatie: {ability}_{resource}
     */
    protected function check(mixed $user, string $ability): bool
    {
        return $user->can("{$ability}_{$this->getResourceName()}");
    }

    // =========================================================================
    // Standard Policy Methods
    // =========================================================================

    public function viewAny(mixed $user): bool
    {
        return $this->check($user, 'view_any');
    }

    public function view(mixed $user, mixed $model): bool
    {
        return $this->check($user, 'view');
    }

    public function create(mixed $user): bool
    {
        return $this->check($user, 'create');
    }

    public function update(mixed $user, mixed $model): bool
    {
        return $this->check($user, 'update');
    }

    public function delete(mixed $user, mixed $model): bool
    {
        return $this->check($user, 'delete');
    }

    public function forceDelete(mixed $user, mixed $model): bool
    {
        return $this->check($user, 'force_delete');
    }

    public function restore(mixed $user, mixed $model): bool
    {
        return $this->check($user, 'restore');
    }

    // =========================================================================
    // Bulk variants (dipakai Filament bulk actions)
    // =========================================================================

    public function deleteAny(mixed $user): bool
    {
        return $this->check($user, 'delete');
    }

    public function forceDeleteAny(mixed $user): bool
    {
        return $this->check($user, 'force_delete');
    }

    public function restoreAny(mixed $user): bool
    {
        return $this->check($user, 'restore');
    }
}
