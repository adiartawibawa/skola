<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;

/**
 * GenerateResourcePermissions
 *
 * Generate Spatie permissions untuk satu model + optional Policy + auto-register.
 *
 * PENGGUNAAN:
 * -----------
 * php artisan shield:generate-resource User
 * php artisan shield:generate-resource User --with-policy --register
 * php artisan shield:generate-resource BlogPost --with-policy --register
 * php artisan shield:generate-resource User --dry-run
 * php artisan shield:generate-resource User --with-policy --force
 */
class GenerateResourcePermissions extends BaseShieldCommand
{
    protected $signature = 'shield:generate-resource
                                {model              : Nama model, e.g. User atau App\\Models\\BlogPost}
                                {--guard=web        : Guard name}
                                {--with-policy      : Generate file app/Policies/{Model}Policy.php}
                                {--register         : Auto-register Policy ke AuthServiceProvider/AppServiceProvider}
                                {--force            : Overwrite Policy yang sudah ada}
                                {--dry-run          : Preview tanpa menyimpan apapun}';

    protected $description = 'Generate resource permissions (+ Policy) untuk satu model Eloquent';

    protected array $defaultActions = [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
        'force_delete',
        'restore',
    ];

    // =========================================================================
    // Entry Point
    // =========================================================================

    public function handle(): int
    {
        $this->printHeader('Generate Resource Permissions');

        $modelClass = $this->resolveModelClass();

        if ($modelClass === null) {
            return self::FAILURE;
        }

        $modelName = class_basename($modelClass);
        $resource = Str::snake(Str::plural($modelName));
        $guard = (string) $this->option('guard');
        $dryRun = (bool) $this->option('dry-run');

        $this->line("  <fg=cyan>Model    :</> {$modelName}");
        $this->line("  <fg=cyan>Resource :</> {$resource}");
        $this->line("  <fg=cyan>Guard    :</> {$guard}");
        $this->newLine();

        // 1. Generate permissions
        $this->handlePermissions($resource, $guard, $dryRun);

        // 2. Generate Policy file
        if ($this->option('with-policy') || $this->option('register')) {
            $this->newLine();
            $this->handlePolicy($modelClass, $modelName, $resource, $dryRun);
        }

        // 3. Register Policy ke provider
        if ($this->option('register')) {
            $this->newLine();
            $this->handleRegistration($modelClass, $modelName, $dryRun);
        }

        return self::SUCCESS;
    }

    // =========================================================================
    // Step 1: Permissions
    // =========================================================================

    private function handlePermissions(string $resource, string $guard, bool $dryRun): void
    {
        $this->line('  <options=bold>📋 Permissions</>');

        $rows = [];
        $created = 0;

        foreach ($this->defaultActions as $action) {
            $name = "{$action}_{$resource}";
            $status = $this->createPermission($name, $guard, $dryRun);
            $rows[] = [$name, $guard, $status];

            if ($status === self::STATUS_CREATED) {
                $created++;
            }
        }

        $this->printPermissionTable($rows, $dryRun);

        if ($created === 0) {
            $this->line('  Semua permission sudah ada.');

            return;
        }

        if (! $dryRun) {
            $this->flushCache();
            $this->line("  <fg=green>✅ {$created} permission dibuat, cache di-flush.</>");
        }
    }

    // =========================================================================
    // Step 2: Policy
    // =========================================================================

    private function handlePolicy(
        string $modelClass,
        string $modelName,
        string $resource,
        bool $dryRun
    ): void {
        $this->line('  <options=bold>📄 Policy</>');

        $policyPath = app_path("Policies/{$modelName}Policy.php");

        if (file_exists($policyPath) && ! $this->option('force')) {
            $this->line("  <fg=yellow>⏭  {$modelName}Policy.php sudah ada. Gunakan --force untuk overwrite.</>");

            return;
        }

        $stub = $this->buildPolicyStub($modelName, $modelClass, $resource);

        if ($dryRun) {
            $this->line("  [dry-run] Akan membuat: app/Policies/{$modelName}Policy.php");

            return;
        }

        if (! is_dir(app_path('Policies'))) {
            mkdir(app_path('Policies'), 0755, true);
        }

        file_put_contents($policyPath, $stub);
        $this->line("  <fg=green>✅ app/Policies/{$modelName}Policy.php dibuat.</>");
    }

    private function buildPolicyStub(string $modelName, string $modelClass, string $resource): string
    {
        $now = now()->format('Y-m-d H:i');
        $actions = implode(', ', array_map(fn (string $a): string => "{$a}_{$resource}", $this->defaultActions));

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Policies;

        use {$modelClass};

        /**
         * {$modelName}Policy
         *
         * Auto-generated oleh shield:generate-resource pada {$now}
         * Resource  : {$resource}
         * Permission: {$actions}
         *
         * Untuk override perilaku tertentu, definisikan method di sini.
         * Semua method tersedia melalui ShieldPolicy (base class).
         */
        class {$modelName}Policy extends ShieldPolicy
        {
            //
        }
        PHP;
    }

    // =========================================================================
    // Step 3: Registration
    // =========================================================================

    private function handleRegistration(string $modelClass, string $modelName, bool $dryRun): void
    {
        $this->line('  <options=bold>🔗 Provider Registration</>');

        [$providerPath, $providerType] = $this->resolveProviderPath();

        if ($providerPath === null) {
            $this->warn('  Provider tidak ditemukan. Daftarkan manual:');
            $this->printManualEntry($modelClass, $modelName);

            return;
        }

        $content = file_get_contents($providerPath);
        $modelFqn = '\\'.ltrim($modelClass, '\\');
        $policyFqn = "\\App\\Policies\\{$modelName}Policy";

        if (str_contains($content, "{$modelFqn}::class")) {
            $this->line("  <fg=yellow>⏭  {$modelName} sudah terdaftar.</>");

            return;
        }

        if ($dryRun) {
            $preview = $providerType === 'auth'
                ? "        {$modelFqn}::class => {$policyFqn}::class,"
                : "        Gate::policy({$modelFqn}::class, {$policyFqn}::class);";
            $this->line("  [dry-run] Akan menambahkan: <fg=gray>{$preview}</>");

            return;
        }

        $injected = $providerType === 'auth'
            ? $this->injectToPoliciesArray($content, $modelFqn, $policyFqn, $providerPath)
            : $this->injectToBootMethod($content, $modelFqn, $policyFqn, $providerPath);

        if ($injected) {
            $this->line("  <fg=green>✅ {$modelName}Policy didaftarkan di ".basename($providerPath).'.</>');
        } else {
            $this->warn('  Inject otomatis gagal. Daftarkan manual:');
            $this->printManualEntry($modelClass, $modelName);
        }
    }

    // =========================================================================
    // Internal Helpers
    // =========================================================================

    private function resolveModelClass(): ?string
    {
        $arg = (string) $this->argument('model');
        $class = str_contains($arg, '\\') ? $arg : "App\\Models\\{$arg}";

        if (! class_exists($class)) {
            $this->error("Model [{$class}] tidak ditemukan.");

            return null;
        }

        return $class;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveProviderPath(): array
    {
        $auth = app_path('Providers/AuthServiceProvider.php');
        $app = app_path('Providers/AppServiceProvider.php');

        if (file_exists($auth)) {
            return [$auth, 'auth'];
        }

        if (file_exists($app)) {
            $this->line('  <fg=yellow>AuthServiceProvider tidak ada, menggunakan AppServiceProvider (Laravel 11/12).</>');

            return [$app, 'app'];
        }

        return [null, null];
    }

    private function injectToPoliciesArray(
        string $content,
        string $modelFqn,
        string $policyFqn,
        string $path
    ): bool {
        $entry = "            {$modelFqn}::class => {$policyFqn}::class,";
        $pattern = '/(protected\s+(?:array\s+)?\$policies\s*=\s*\[)([\s\S]*?)(\s*\];)/';

        if (! preg_match($pattern, $content)) {
            return false;
        }

        $updated = preg_replace($pattern, "$1$2\n{$entry}\n    $3", $content, 1);
        file_put_contents($path, $updated);

        return true;
    }

    private function injectToBootMethod(
        string $content,
        string $modelFqn,
        string $policyFqn,
        string $path
    ): bool {
        $content = $this->ensureGateImport($content);
        $gateCall = "        Gate::policy({$modelFqn}::class, {$policyFqn}::class);";
        $pattern = '/(public\s+function\s+boot\s*\(\s*\)\s*:\s*void\s*\{)/';

        if (! preg_match($pattern, $content)) {
            return false;
        }

        $updated = preg_replace($pattern, "$1\n{$gateCall}", $content, 1);
        file_put_contents($path, $updated);

        return true;
    }

    private function ensureGateImport(string $content): string
    {
        $import = 'use Illuminate\\Support\\Facades\\Gate;';

        if (str_contains($content, $import)) {
            return $content;
        }

        // Tambahkan setelah blok use terakhir
        if (preg_match('/^(use [^;]+;)$/m', $content)) {
            return preg_replace('/((?:^use [^;]+;\n)+)/m', "$1{$import}\n", $content, 1);
        }

        // Fallback: setelah namespace
        return preg_replace('/(^namespace [^;]+;)/m', "$1\n\n{$import}", $content, 1);
    }

    private function printManualEntry(string $modelClass, string $modelName): void
    {
        $modelFqn = '\\'.ltrim($modelClass, '\\');
        $policyFqn = "\\App\\Policies\\{$modelName}Policy";
        $this->line("  <fg=gray>    {$modelFqn}::class => {$policyFqn}::class,</>");
    }
}
