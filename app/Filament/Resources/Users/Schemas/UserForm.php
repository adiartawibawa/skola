<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pribadi')
                    ->description('Detail informasi profil pengguna')
                    ->collapsible()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->label('Foto Profil')
                            ->collection('avatars')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->placeholder('Masukkan nama lengkap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Kredensial Pengguna')
                    ->description('Informasi akun dan verifikasi pengguna')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Alamat Email')
                                    ->placeholder('nama@contoh.com')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                DateTimePicker::make('email_verified_at')
                                    ->label('Terverifikasi Pada')
                                    ->nullable(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('password')
                                    ->label('Kata Sandi')
                                    ->placeholder(fn ($record) => $record ? 'Kosongkan jika tidak diubah' : 'Masukkan kata sandi')
                                    ->password()
                                    ->revealable()
                                    ->required(fn ($record) => $record === null)
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                                    ->autocomplete('new-password')
                                    ->helperText(fn ($record) => $record ? 'Isi hanya jika ingin mengubah kata sandi' : null),

                                TextInput::make('password_confirmation')
                                    ->label('Konfirmasi Kata Sandi')
                                    ->placeholder('Ulangi kata sandi')
                                    ->password()
                                    ->revealable()
                                    ->required(fn ($record) => $record === null)
                                    ->same('password')
                                    ->dehydrated(false)
                                    ->autocomplete('new-password'),
                            ]),
                    ]),
            ]);

    }
}
