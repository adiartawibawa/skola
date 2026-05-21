<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(125)
                            ->live(onBlur: true)
                            ->helperText('Huruf kecil, tanpa spasi (gunakan - atau _). Contoh: admin, content-editor')
                            ->dehydrateStateUsing(fn ($state) => Str::lower(Str::slug($state, '_'))),

                        TextInput::make('guard_name')
                            ->label('Guard')
                            ->default('web')
                            ->required()
                            ->maxLength(125),
                    ])
                    ->columnSpanFull(),

                Section::make('Permissions')
                    ->description('Pilih permission yang dimiliki role ini.')
                    ->schema([
                        static::buildPermissionMatrix(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Membangun matrix permission yang dikelompokkan per resource/grup.
     * Sangat mudah dikustomisasi — ubah grouping logic di sini.
     */
    protected static function buildPermissionMatrix(): Component
    {
        // Ambil semua permission dan kelompokkan berdasarkan prefiks resource
        // Konvensi penamaan: {action}_{resource} — contoh: view_any_users, create_users
        $permissions = Permission::orderBy('name')->get();

        // Kelompokkan: "view_any_users" → group "users"
        $grouped = $permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            // Hapus prefix aksi yang umum
            $actions = ['view', 'any', 'create', 'update', 'delete', 'force', 'restore'];
            $resourceParts = array_filter($parts, fn ($p) => ! in_array($p, $actions));

            return implode('_', $resourceParts) ?: 'general';
        });

        $components = [];

        foreach ($grouped as $group => $groupPermissions) {
            $components[] = Fieldset::make(Str::headline($group))
                ->schema([
                    CheckboxList::make("permissions_{$group}")
                        ->label('')
                        ->options(
                            $groupPermissions->pluck('name', 'id')->toArray()
                        )
                        ->columns(3)
                        ->gridDirection('row')
                        // Hydrate: ambil permission yang sudah dimiliki role
                        ->afterStateHydrated(function ($component, $state, $record) use ($groupPermissions) {
                            if (! $record) {
                                return;
                            }

                            $selected = $record->permissions
                                ->whereIn('id', $groupPermissions->pluck('id'))
                                ->pluck('id')
                                ->toArray();

                            $component->state($selected);
                        })
                        // Dehydrate: jangan simpan ke fillable langsung
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]);
        }

        // Gunakan hidden field untuk aggregate semua permission ID yang dipilih
        $components[] = Hidden::make('_permission_ids')
            ->dehydrated(false);

        return Group::make($components);
    }
}
