<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── Section 1: Informasi Pribadi ──────────────────────────
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

                // ── Section 2: Kredensial ─────────────────────────────────
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

                                Toggle::make('is_email_verified')
                                    ->label('Email Terverifikasi')
                                    ->helperText(
                                        fn ($record) => $record?->email_verified_at
                                            ? 'Terverifikasi pada: '.$record->email_verified_at->translatedFormat('d F Y, H:i')
                                            : 'Email belum terverifikasi.'
                                    )
                                    ->default(
                                        fn ($record) => filled($record?->email_verified_at)
                                    )
                                    ->live()
                                    ->dehydrated(false)
                                    ->onIcon('heroicon-m-check-badge')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->onColor('success')
                                    ->offColor('danger'),
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

                // ── Section 3: Hak Akses ──────────────────────────────────
                Section::make('Hak Akses')
                    ->description('Tentukan role pengguna. Permission akan mengikuti role yang dipilih.')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        Select::make('roles')
                            ->label('Role')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->searchable()
                            ->placeholder('Pilih role...')
                            ->helperText('User dapat memiliki lebih dari satu role.')
                            ->columnSpanFull()
                            // Tampilkan badge warna per role
                            ->getOptionLabelFromRecordUsing(
                                fn (Role $record) => $record->name
                            )
                            // Cegah menghapus role super-admin dari user lain
                            // jika user yang login bukan super-admin
                            ->disableOptionWhen(function (string $value): bool {
                                $role = Role::find($value);

                                if (! $role) {
                                    return false;
                                }

                                // Hanya super-admin yang bisa assign/remove role super-admin
                                if ($role->name === 'super-admin') {
                                    return ! auth()->user()?->hasRole('super-admin');
                                }

                                return false;
                            }),
                    ]),

            ]);

    }
}
