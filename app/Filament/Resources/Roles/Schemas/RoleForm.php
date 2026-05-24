<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleForm
{
    /**
     * Urutan aksi resource — lebih panjang duluan agar tidak salah strip prefix.
     * Contoh: "force_delete" harus dicek sebelum "delete".
     */
    protected static array $resourceActions = [
        'view_any',
        'force_delete',
        'restore',
        'view',
        'create',
        'update',
        'delete',
    ];

    // =========================================================================
    // Entry Point
    // =========================================================================

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Role Information')
                ->schema([
                    TextInput::make('name')
                        ->label('Role Name')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(125)
                        ->live(onBlur: true)
                        ->helperText('Huruf kecil, tanpa spasi. Contoh: admin, content-editor')
                        ->dehydrateStateUsing(fn ($state) => Str::lower(Str::slug($state, '_'))),

                    TextInput::make('guard_name')
                        ->label('Guard')
                        ->default('web')
                        ->required()
                        ->maxLength(125),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Permissions')
                ->description('Assign permissions ke role ini berdasarkan tipe.')
                ->schema([static::buildPermissionTabs()])
                ->collapsible()
                ->columnSpanFull(),
        ]);
    }

    // =========================================================================
    // Tabs Builder
    // =========================================================================

    protected static function buildPermissionTabs(): Component
    {
        $all = Permission::orderBy('name')->get();

        // Kategorikan berdasarkan prefix konvensi shield:generate-*
        $pages = $all->filter(fn ($p) => str_starts_with($p->name, 'page_'));
        $widgets = $all->filter(fn ($p) => str_starts_with($p->name, 'widget_'));
        $clusters = $all->filter(fn ($p) => str_starts_with($p->name, 'cluster_'));
        $resources = $all->filter(
            fn ($p) => ! str_starts_with($p->name, 'page_')
                    && ! str_starts_with($p->name, 'widget_')
                    && ! str_starts_with($p->name, 'cluster_')
        );

        // Group resource permissions by resource name
        $groupedResources = $resources->groupBy(
            fn ($p) => static::extractResourceName($p->name)
        )->sortKeys();

        return Tabs::make('permission_tabs')
            ->tabs([
                Tab::make('Resources')
                    ->icon('heroicon-o-rectangle-stack')
                    ->badge($resources->count())
                    ->schema(static::buildResourceTab($groupedResources)),

                Tab::make('Pages')
                    ->icon('heroicon-o-document-text')
                    ->badge($pages->count())
                    ->schema(static::buildFlatTab($pages, 'pages', 'page_')),

                Tab::make('Widgets')
                    ->icon('heroicon-o-squares-2x2')
                    ->badge($widgets->count())
                    ->schema(static::buildFlatTab($widgets, 'widgets', 'widget_')),

                Tab::make('Clusters')
                    ->icon('heroicon-o-square-3-stack-3d')
                    ->badge($clusters->count())
                    ->schema(static::buildFlatTab($clusters, 'clusters', 'cluster_')),
            ])
            ->columnSpanFull();
    }

    // =========================================================================
    // Tab: Resources — satu Fieldset per resource dengan Select All toggle
    // =========================================================================

    protected static function buildResourceTab(Collection $groupedResources): array
    {
        if ($groupedResources->isEmpty()) {
            return [static::emptyState('Belum ada resource permission. Jalankan php artisan shield:generate --all --with-policy --register')];
        }

        $components = [];

        foreach ($groupedResources as $resource => $groupPermissions) {
            $fieldKey = "permissions_{$resource}";
            $toggleKey = "_all_{$resource}";

            // Urutkan option sesuai urutan aksi standar agar tampil konsisten
            $options = static::sortedOptions($groupPermissions);

            $components[] = Fieldset::make(Str::headline($resource))
                ->schema([
                    // ── Select All toggle ──────────────────────────────────
                    Toggle::make($toggleKey)
                        ->label('Pilih Semua')
                        ->onColor('success')
                        ->offColor('gray')
                        ->inline(false)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(
                            function ($component, $record) use ($groupPermissions) {
                                if (! $record) {
                                    $component->state(false);

                                    return;
                                }
                                $total = $groupPermissions->count();
                                $selected = $record->permissions
                                    ->whereIn('id', $groupPermissions->pluck('id'))
                                    ->count();
                                $component->state($total > 0 && $selected === $total);
                            }
                        )
                        ->afterStateUpdated(
                            function (bool $state, Set $set) use ($groupPermissions, $fieldKey) {
                                $set($fieldKey, $state
                                    ? $groupPermissions->pluck('id')->map(fn ($id) => (string) $id)->toArray()
                                    : []
                                );
                            }
                        ),

                    // ── Checkbox matrix ────────────────────────────────────
                    CheckboxList::make($fieldKey)
                        ->label('')
                        ->options($options)
                        ->columns(4)
                        ->gridDirection('row')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(
                            function ($component, $record) use ($groupPermissions) {
                                if (! $record) {
                                    return;
                                }
                                $selected = $record->permissions
                                    ->whereIn('id', $groupPermissions->pluck('id'))
                                    ->pluck('id')
                                    ->map(fn ($id) => (string) $id)
                                    ->toArray();
                                $component->state($selected);
                            }
                        )
                        ->afterStateUpdated(
                            function ($state, Set $set) use ($groupPermissions, $toggleKey) {
                                // Sinkronisasi toggle: aktif jika semua sudah dipilih
                                $allSelected = count($state ?? []) === $groupPermissions->count();
                                $set($toggleKey, $allSelected);
                            }
                        )
                        ->columnSpanFull(),
                ]);
        }

        return $components;
    }

    // =========================================================================
    // Tab: Pages & Widgets — checklist sederhana dengan label yang bersih
    // =========================================================================

    protected static function buildFlatTab(
        Collection $permissions,
        string $groupKey,
        string $stripPrefix
    ): array {
        if ($permissions->isEmpty()) {
            return [static::emptyState("Belum ada {$groupKey} permission. Tambahkan via php artisan shield:generate.")];
        }

        $fieldKey = "permissions_{$groupKey}";
        $toggleKey = "_all_{$groupKey}";
        $allIds = $permissions->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        // Buat label yang bersih: "page_dashboard_page" → "Dashboard Page"
        $options = $permissions->mapWithKeys(function ($p) use ($stripPrefix) {
            $label = Str::headline(str_replace($stripPrefix, '', $p->name));

            return [(string) $p->id => $label];
        })->toArray();

        return [
            Toggle::make($toggleKey)
                ->label('Pilih Semua')
                ->onColor('success')
                ->offColor('gray')
                ->inline(false)
                ->live()
                ->dehydrated(false)
                ->afterStateHydrated(
                    function ($component, $record) use ($permissions) {
                        if (! $record) {
                            $component->state(false);

                            return;
                        }
                        $total = $permissions->count();
                        $selected = $record->permissions
                            ->whereIn('id', $permissions->pluck('id'))
                            ->count();
                        $component->state($total > 0 && $selected === $total);
                    }
                )
                ->afterStateUpdated(
                    function (bool $state, Set $set) use ($allIds, $fieldKey) {
                        $set($fieldKey, $state ? $allIds : []);
                    }
                ),

            CheckboxList::make($fieldKey)
                ->label('')
                ->options($options)
                ->columns(3)
                ->gridDirection('row')
                ->live()
                ->dehydrated(false)
                ->afterStateHydrated(
                    function ($component, $record) use ($permissions) {
                        if (! $record) {
                            return;
                        }
                        $selected = $record->permissions
                            ->whereIn('id', $permissions->pluck('id'))
                            ->pluck('id')
                            ->map(fn ($id) => (string) $id)
                            ->toArray();
                        $component->state($selected);
                    }
                )
                ->afterStateUpdated(
                    function ($state, Set $set) use ($permissions, $toggleKey) {
                        $allSelected = count($state ?? []) === $permissions->count();
                        $set($toggleKey, $allSelected);
                    }
                )
                ->columnSpanFull(),
        ];
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Ekstrak nama resource dari nama permission.
     * "view_any_blog_posts" → "blog_posts"
     * "force_delete_users"  → "users"
     */
    protected static function extractResourceName(string $permissionName): string
    {
        foreach (static::$resourceActions as $action) {
            $prefix = $action.'_';
            if (str_starts_with($permissionName, $prefix)) {
                return substr($permissionName, strlen($prefix));
            }
        }

        return 'general';
    }

    /**
     * Urutkan options sesuai urutan aksi standar agar tampil konsisten di UI.
     * view_any → view → create → update → delete → force_delete → restore
     */
    protected static function sortedOptions(Collection $permissions): array
    {
        $order = array_flip(static::$resourceActions);

        return $permissions
            ->sortBy(function ($p) use ($order) {
                foreach (array_keys($order) as $action) {
                    if (str_starts_with($p->name, $action.'_')) {
                        return $order[$action];
                    }
                }

                return 99;
            })
            ->mapWithKeys(fn ($p) => [(string) $p->id => $p->name])
            ->toArray();
    }

    /**
     * Placeholder saat tidak ada permission dalam kategori tersebut.
     */
    protected static function emptyState(string $message): Component
    {
        return Group::make([
            Placeholder::make('_empty')
                ->label('')
                ->content($message),
        ]);
    }
}
