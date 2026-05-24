<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Permission;

/**
 * PermissionPolicy
 *
 * Auto-generated oleh shield:generate-resource pada 2026-05-24 13:52
 * Resource  : permissions
 * Permission: view_any_permissions, view_permissions, create_permissions, update_permissions, delete_permissions, force_delete_permissions, restore_permissions
 *
 * Untuk override perilaku tertentu, definisikan method di sini.
 * Semua method tersedia melalui ShieldPolicy (base class).
 */
class PermissionPolicy extends ShieldPolicy
{
    //
}