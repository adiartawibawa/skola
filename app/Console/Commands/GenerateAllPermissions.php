<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * GenerateAllPermissions
 *
 * Auto-scan app/Filament/ dan generate semua permissions dari trait yang ditemukan.
 *
 * PENGGUNAAN:
 * -----------
 * php artisan shield:generate-all
 * php artisan shield:generate-all --dry-run
 * php artisan shield:generate-all --guard=api
 *
 * CARA KERJA:
 * -----------
 * 1. Scan app/Filament/Resources/**   → cari HasShieldAuthorization
 *    → derive resource name dari $model property
 *    → generate view_any_*, view_*, create_*, update_*, delete_*, force_delete_*, restore_*
 *
 * 2. Scan app/Filament/Pages/**       → cari HasShieldPageAuthorization
 *    → derive page name dari class name (strip 'Page', snake_case)
 *    → generate page_*
 *
 * 3. Scan app/Filament/Widgets/**     → cari HasShieldWidgetAuthorization
 *    → derive widget name dari class name (strip 'Widget', snake_case)
 *    → generate widget_*
 *
 * OVERRIDE NAMA PERMISSION:
 * -------------------------
 * Jika class mengoverride getPermissionPageName() atau getPermissionWidgetName(),
 * command ini akan membaca return value dari method tersebut jika berupa string literal.
 * Untuk kasus kompleks, gunakan shield:generate-page / shield:generate-widget secara manual.
 */
class GenerateAllPermissions extends BaseShieldCommand
{
    protected $signature = 'shield:generate-all
                                {--guard=web : Guard name}
                                {--dry-run   : Preview tanpa menyimpan apapun}';

    protected $description = 'Auto-discover dan generate semua permissions dari trait Filament';

    protected array $resourceActions = [
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
        $this->printHeader('Generate All Permissions (Auto-Discovery)');

        $guard = (string) $this->option('guard');
        $dryRun = (bool) $this->option('dry-run');

        $resources = $this->discoverResources();
        $pages = $this->discoverPages();
        $widgets = $this->discoverWidgets();
        $clusters = $this->discoverClusters();

        $totalDiscovered = count($resources) + count($pages) + count($widgets) + count($clusters);

        if ($totalDiscovered === 0) {
            $this->warn('Tidak ada trait Shield ditemukan di app/Filament/.');
            $this->line('Pastikan trait HasShieldAuthorization / HasShieldPageAuthorization /');
            $this->line('HasShieldWidgetAuthorization / HasShieldClusterAuthorization sudah dipasang.');

            return self::SUCCESS;
        }

        $this->printDiscoverySummary($resources, $pages, $widgets, $clusters);
        $this->newLine();

        $totalCreated = 0;
        $totalCreated += $this->processResources($resources, $guard, $dryRun);
        $totalCreated += $this->processPages($pages, $guard, $dryRun);
        $totalCreated += $this->processWidgets($widgets, $guard, $dryRun);
        $totalCreated += $this->processClusters($clusters, $guard, $dryRun);

        $this->newLine();

        if ($dryRun) {
            $this->warn("Dry run selesai. {$totalCreated} permission akan dibuat saat dijalankan tanpa --dry-run.");
        } else {
            $this->info("✅ Selesai. {$totalCreated} permission baru dibuat.");
        }

        return self::SUCCESS;
    }

    // =========================================================================
    // Processing
    // =========================================================================

    private function processResources(array $resources, string $guard, bool $dryRun): int
    {
        if (empty($resources)) {
            return 0;
        }

        $this->line('<options=bold>📦 Resources</>');

        $rows = [];
        $created = 0;

        foreach ($resources as $resource) {
            foreach ($this->resourceActions as $action) {
                $name = "{$action}_{$resource}";
                $status = $this->createPermission($name, $guard, $dryRun);
                $rows[] = [$name, $guard, $status];

                if ($status === self::STATUS_CREATED) {
                    $created++;
                }
            }
        }

        $this->printPermissionTable($rows, $dryRun);

        if ($created > 0 && ! $dryRun) {
            $this->flushCache();
            $this->line("  <fg=green>✅ {$created} resource permission dibuat.</>");
        }

        return $created;
    }

    private function processPages(array $pages, string $guard, bool $dryRun): int
    {
        if (empty($pages)) {
            return 0;
        }

        $this->newLine();
        $this->line('<options=bold>📄 Pages</>');

        $rows = [];
        $created = 0;

        foreach ($pages as $pageName) {
            $name = "page_{$pageName}";
            $status = $this->createPermission($name, $guard, $dryRun);
            $rows[] = [$name, $guard, $status];

            if ($status === self::STATUS_CREATED) {
                $created++;
            }
        }

        $this->printPermissionTable($rows, $dryRun);

        if ($created > 0 && ! $dryRun) {
            $this->flushCache();
            $this->line("  <fg=green>✅ {$created} page permission dibuat.</>");
        }

        return $created;
    }

    private function processWidgets(array $widgets, string $guard, bool $dryRun): int
    {
        if (empty($widgets)) {
            return 0;
        }

        $this->newLine();
        $this->line('<options=bold>🧩 Widgets</>');

        $rows = [];
        $created = 0;

        foreach ($widgets as $widgetName) {
            $name = "widget_{$widgetName}";
            $status = $this->createPermission($name, $guard, $dryRun);
            $rows[] = [$name, $guard, $status];

            if ($status === self::STATUS_CREATED) {
                $created++;
            }
        }

        $this->printPermissionTable($rows, $dryRun);

        if ($created > 0 && ! $dryRun) {
            $this->flushCache();
            $this->line("  <fg=green>✅ {$created} widget permission dibuat.</>");
        }

        return $created;
    }

    private function processClusters(array $clusters, string $guard, bool $dryRun): int
    {
        if (empty($clusters)) {
            return 0;
        }

        $this->newLine();
        $this->line('<options=bold>🗂  Clusters</>');

        $rows = [];
        $created = 0;

        foreach ($clusters as $clusterName) {
            $name = "cluster_{$clusterName}";
            $status = $this->createPermission($name, $guard, $dryRun);
            $rows[] = [$name, $guard, $status];

            if ($status === self::STATUS_CREATED) {
                $created++;
            }
        }

        $this->printPermissionTable($rows, $dryRun);

        if ($created > 0 && ! $dryRun) {
            $this->flushCache();
            $this->line("  <fg=green>✅ {$created} cluster permission dibuat.</>");
        }

        return $created;
    }

    // =========================================================================
    // Discovery
    // =========================================================================

    /**
     * Scan app/Filament/Resources/ untuk class yang memakai HasShieldAuthorization.
     * Return array nama resource, e.g. ['users', 'posts', 'blog_posts']
     *
     * @return string[]
     */
    private function discoverResources(): array
    {
        $dir = app_path('Filament/Resources');

        if (! is_dir($dir)) {
            return [];
        }

        $resources = [];

        foreach ($this->phpFiles($dir) as $file) {
            $content = file_get_contents($file->getPathname());

            if (! str_contains($content, 'HasShieldAuthorization')) {
                continue;
            }

            $resource = $this->extractResourceName($content);

            if ($resource !== null && ! in_array($resource, $resources, true)) {
                $resources[] = $resource;
            }
        }

        sort($resources);

        return $resources;
    }

    /**
     * Scan app/Filament/Pages/ untuk class yang memakai HasShieldPageAuthorization.
     * Return array nama page permission (tanpa prefix 'page_').
     *
     * @return string[]
     */
    private function discoverPages(): array
    {
        $dir = app_path('Filament/Pages');

        if (! is_dir($dir)) {
            return [];
        }

        $pages = [];

        foreach ($this->phpFiles($dir) as $file) {
            $content = file_get_contents($file->getPathname());

            if (! str_contains($content, 'HasShieldPageAuthorization')) {
                continue;
            }

            $name = $this->extractOverriddenName($content, 'getPermissionPageName')
                ?? $this->classNameToPagePermission($content);

            if ($name !== null && ! in_array($name, $pages, true)) {
                $pages[] = $name;
            }
        }

        sort($pages);

        return $pages;
    }

    /**
     * Scan app/Filament/Widgets/ untuk class yang memakai HasShieldWidgetAuthorization.
     * Return array nama widget permission (tanpa prefix 'widget_').
     *
     * @return string[]
     */
    private function discoverWidgets(): array
    {
        $dir = app_path('Filament/Widgets');

        if (! is_dir($dir)) {
            return [];
        }

        $widgets = [];

        foreach ($this->phpFiles($dir) as $file) {
            $content = file_get_contents($file->getPathname());

            if (! str_contains($content, 'HasShieldWidgetAuthorization')) {
                continue;
            }

            $name = $this->extractOverriddenName($content, 'getPermissionWidgetName')
                ?? $this->classNameToWidgetPermission($content);

            if ($name !== null && ! in_array($name, $widgets, true)) {
                $widgets[] = $name;
            }
        }

        sort($widgets);

        return $widgets;
    }

    /**
     * Scan app/Filament/Clusters/ untuk class yang memakai HasShieldClusterAuthorization.
     * Return array nama cluster permission (tanpa prefix 'cluster_').
     *
     * @return string[]
     */
    private function discoverClusters(): array
    {
        $dir = app_path('Filament/Clusters');

        if (! is_dir($dir)) {
            return [];
        }

        $clusters = [];

        foreach ($this->phpFiles($dir) as $file) {
            $content = file_get_contents($file->getPathname());

            if (! str_contains($content, 'HasShieldClusterAuthorization')) {
                continue;
            }

            $name = $this->extractOverriddenName($content, 'getPermissionClusterName')
                ?? $this->classNameToClusterPermission($content);

            if ($name !== null && ! in_array($name, $clusters, true)) {
                $clusters[] = $name;
            }
        }

        sort($clusters);

        return $clusters;
    }

    // =========================================================================
    // Extraction Helpers
    // =========================================================================

    /**
     * Ekstrak nama resource dari properti $model di Resource file.
     *
     * protected static ?string $model = User::class;
     * protected static ?string $model = \App\Models\BlogPost::class;
     * → 'users', 'blog_posts'
     */
    private function extractResourceName(string $content): ?string
    {
        // Match: $model = ModelName::class  atau  $model = \Full\Path\ModelName::class
        if (! preg_match(
            '/protected\s+static\s+\?string\s+\$model\s*=\s*([\\\\A-Za-z0-9]+)::class/',
            $content,
            $matches
        )) {
            return null;
        }

        $modelName = class_basename(trim($matches[1], '\\'));

        return Str::snake(Str::plural($modelName));
    }

    /**
     * Jika class mengoverride method permission name, baca return value-nya.
     * Hanya mendukung single-line string literal:
     *
     *   protected static function getPermissionPageName(): string
     *   {
     *       return 'reports';
     *   }
     */
    private function extractOverriddenName(string $content, string $methodName): ?string
    {
        $pattern = '/function\s+'.$methodName.'\s*\(\s*\)[^{]*\{[^}]*return\s+[\'"]([^\'"]+)[\'"]/s';

        if (preg_match($pattern, $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Derive page permission name dari nama class.
     * ReportsPage → reports, Dashboard → dashboard
     */
    private function classNameToPagePermission(string $content): ?string
    {
        if (! preg_match('/class\s+([A-Za-z0-9_]+)\s+/', $content, $matches)) {
            return null;
        }

        return Str::of($matches[1])
            ->replaceLast('Page', '')
            ->snake()
            ->toString();
    }

    /**
     * Derive widget permission name dari nama class.
     * StatsOverviewWidget → stats_overview
     */
    private function classNameToWidgetPermission(string $content): ?string
    {
        if (! preg_match('/class\s+([A-Za-z0-9_]+)\s+/', $content, $matches)) {
            return null;
        }

        return Str::of($matches[1])
            ->replaceLast('Widget', '')
            ->snake()
            ->toString();
    }

    /**
     * Derive cluster permission name dari nama class.
     * SettingsCluster → settings
     */
    private function classNameToClusterPermission(string $content): ?string
    {
        if (! preg_match('/class\s+([A-Za-z0-9_]+)\s+/', $content, $matches)) {
            return null;
        }

        return Str::of($matches[1])
            ->replaceLast('Cluster', '')
            ->snake()
            ->toString();
    }

    // =========================================================================
    // Filesystem
    // =========================================================================

    /**
     * Recursive iterator untuk semua .php file dalam direktori.
     *
     * @return iterable<SplFileInfo>
     */
    private function phpFiles(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }

    // =========================================================================
    // Output
    // =========================================================================

    private function printDiscoverySummary(
        array $resources,
        array $pages,
        array $widgets,
        array $clusters
    ): void {
        $this->line('<options=bold>🔍 Discovery Result</>');

        $this->table(
            ['Type', 'Discovered', 'Permissions'],
            [
                ['Resources', implode(', ', $resources) ?: '—', count($resources) * count($this->resourceActions)],
                ['Pages',     implode(', ', $pages) ?: '—', count($pages)],
                ['Widgets',   implode(', ', $widgets) ?: '—', count($widgets)],
                ['Clusters',  implode(', ', $clusters) ?: '—', count($clusters)],
            ]
        );
    }
}
