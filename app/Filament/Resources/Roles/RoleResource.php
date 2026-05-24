<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    // -------------------------------------------------------------------------
    // Authorization — sesuaikan dengan kebutuhan Anda
    // -------------------------------------------------------------------------

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_roles') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_roles') ?? false;
    }

    public static function canEdit($record): bool
    {
        // Mencegah edit role 'super-admin' secara tidak sengaja
        if ($record->name === 'super-admin') {
            return false;
        }

        return auth()->user()?->can('update_roles') ?? false;
    }

    public static function canDelete($record): bool
    {
        if ($record->name === 'super-admin') {
            return false;
        }

        return auth()->user()?->can('delete_roles') ?? false;
    }
}
