<?php

namespace App\Filament\Resources\Permissions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PermissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Permission Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Permission Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(125)
                            ->helperText('Konvensi: {aksi}_{resource}. Contoh: view_any_users, create_posts, delete_comments')
                            ->live(onBlur: true)
                            ->dehydrateStateUsing(fn ($state) => Str::lower($state))
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                $set(
                                    'usage_preview',
                                    $state
                                        ? '$user->can(\''.Str::lower($state).'\')'
                                        : '—'
                                );
                            }),

                        TextInput::make('guard_name')
                            ->label('Guard')
                            ->default('web')
                            ->required()
                            ->maxLength(125),

                        // hanya preview
                        TextInput::make('usage_preview')
                            ->label('Cara Pemakaian')
                            ->disabled()
                            ->dehydrated(false)
                            ->default('—')
                            ->helperText('Contoh pemanggilan permission ini di kode.')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
