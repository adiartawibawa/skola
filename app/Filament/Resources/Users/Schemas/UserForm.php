<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pribadi')
                    ->description('Detail informasi profile pengguna')
                    ->collapsible()
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->collection('avatars'),
                    ]),
                Section::make('Kredensial Pengguna')
                    ->description('Informasi kredensial pengguna')
                    ->collapsible()
                    ->schema([
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required(),
                        DateTimePicker::make('email_verified_at'),
                        TextInput::make('password')
                            ->password()
                            ->required(),
                    ]),
            ]);
    }
}
