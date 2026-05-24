<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * UserPolicy
 *
 * Auto-generated oleh shield:generate-resource pada 2026-05-24 13:51
 * Resource  : users
 * Permission: view_any_users, view_users, create_users, update_users, delete_users, force_delete_users, restore_users
 *
 * Untuk override perilaku tertentu, definisikan method di sini.
 * Semua method tersedia melalui ShieldPolicy (base class).
 */
class UserPolicy extends ShieldPolicy
{
    //
}