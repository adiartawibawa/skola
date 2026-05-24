<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

/**
 * BaseShieldCommand
 *
 * Shared logic untuk semua shield:generate-* commands.
 * Extend class ini — jangan gunakan langsung.
 */
abstract class BaseShieldCommand extends Command
{
    protected const STATUS_CREATED = 'created';

    protected const STATUS_EXISTS = 'exists';

    // =========================================================================
    // Permission Creation
    // =========================================================================

    /**
     * Buat satu permission. Return STATUS_CREATED atau STATUS_EXISTS.
     * Saat dry-run, tidak menyentuh database.
     */
    protected function createPermission(string $name, string $guard, bool $dryRun): string
    {
        $exists = Permission::where('name', $name)
            ->where('guard_name', $guard)
            ->exists();

        if ($exists) {
            return self::STATUS_EXISTS;
        }

        if (! $dryRun) {
            Permission::create(['name' => $name, 'guard_name' => $guard]);
        }

        return self::STATUS_CREATED;
    }

    /**
     * Flush Spatie permission cache.
     */
    protected function flushCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // =========================================================================
    // Output Helpers
    // =========================================================================

    protected function printHeader(string $title): void
    {
        $this->newLine();
        $this->line("<fg=cyan;options=bold>🛡  {$title}</>");
        $this->line('<fg=gray>────────────────────────────────────────────────────</>');

        if ($this->hasOption('dry-run') && $this->option('dry-run')) {
            $this->warn('⚠  DRY RUN — tidak ada yang disimpan ke database.');
        }

        $this->newLine();
    }

    /**
     * Render tabel hasil permission.
     *
     * @param  array<int, array{0: string, 1: string, 2: string}>  $rows
     */
    protected function printPermissionTable(array $rows, bool $dryRun): void
    {
        $formatted = array_map(function (array $row) use ($dryRun): array {
            [$name, $guard, $status] = $row;

            $label = match ($status) {
                self::STATUS_CREATED => $dryRun
                    ? '<fg=blue>◌ will create</>'
                    : '<fg=green>✓ created</>',
                self::STATUS_EXISTS => '<fg=yellow>⏭  exists</>',
                default => $status,
            };

            return [$name, $guard, $label];
        }, $rows);

        $this->table(['Permission', 'Guard', 'Status'], $formatted);
    }
}
