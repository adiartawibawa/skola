<?php

namespace App\Filament\Resources\Permissions\Tables;

use App\Models\Permission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class PermissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Permission')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Permission name copied!')
                    ->fontFamily('mono'),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('roles_count')
                    ->label('Roles')
                    ->counts('roles')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options(fn () => Permission::distinct()->pluck('guard_name', 'guard_name')),

                // Filter berdasarkan prefix (resource) dari nama permission
                Filter::make('resource')
                    ->form([
                        TextInput::make('resource_name')
                            ->label('Filter by Resource')
                            ->placeholder('users, posts, ...'),
                    ])
                    ->query(function ($query, array $data) {
                        if (! empty($data['resource_name'])) {
                            $query->where('name', 'like', "%{$data['resource_name']}%");
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->groups([
                Group::make('guard_name')->label('Guard'),
            ]);
    }
}
