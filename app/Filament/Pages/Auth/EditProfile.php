<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as PagesEditProfile;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Validation\Rules\Password;

class EditProfile extends PagesEditProfile
{
    protected static string $layout = 'filament-panels::components.layout.index';

    // protected string $view = 'filament.pages.edit-profile';

    public function getMaxContentWidth(): Width|string|null
    {
        return parent::getMaxContentWidth();
    }

    // -------------------------------------------------------------------------
    // Form
    // -------------------------------------------------------------------------

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Section 1: Informasi Profil ───────────────────────────────
            Section::make('Informasi Profil')
                ->description('Perbarui nama, email, dan foto profil Anda.')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    // Avatar — menggunakan Spatie MediaLibrary
                    SpatieMediaLibraryFileUpload::make('avatar')
                        ->label('Foto Profil')
                        ->collection('avatars')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->circleCropper()
                        ->maxSize(2048) // 2MB
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->helperText('Format: JPG, PNG, WebP. Maks 2MB.')
                        ->columnSpanFull(),

                    $this->getNameFormComponent()
                        ->label('Nama Lengkap'),

                    $this->getEmailFormComponent()
                        ->label('Alamat Email'),
                ])
                ->columns(2),

            // ── Section 2: Keamanan Akun ──────────────────────────────────
            Section::make('Keamanan Akun')
                ->description('Biarkan kosong jika tidak ingin mengubah password.')
                ->icon('heroicon-o-lock-closed')
                ->schema([
                    TextInput::make('current_password')
                        ->label('Password Saat Ini')
                        ->password()
                        ->revealable()
                        ->autocomplete('current-password')
                        ->dehydrated(false)
                        ->rule('current_password')
                        ->requiredWith('password')
                        ->helperText('Wajib diisi jika ingin mengganti password.'),

                    Grid::make(2)->schema([
                        $this->getPasswordFormComponent()
                            ->label('Password Baru')
                            ->rule(Password::defaults())
                            ->nullable(),

                        $this->getPasswordConfirmationFormComponent()
                            ->label('Konfirmasi Password Baru'),
                    ]),
                ])
                ->columns(1)
                ->collapsible(),

            // ── Section 3: Informasi Akun (read-only) ─────────────────────
            Section::make('Informasi Akun')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    TextInput::make('roles_display')
                        ->label('Role')
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn () => auth()->user()?->getRoleNames()->implode(', ') ?: '—'),

                    TextInput::make('created_at_display')
                        ->label('Bergabung Sejak')
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn () => auth()->user()?->created_at?->translatedFormat('d F Y') ?: '—'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

        ]);
    }

    // -------------------------------------------------------------------------
    // Mutate data sebelum fill ke form
    // -------------------------------------------------------------------------

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Hapus password dari data yang di-fill ke form (jangan tampilkan hash)
        unset($data['password']);

        return $data;
    }

    // -------------------------------------------------------------------------
    // Handle save
    // -------------------------------------------------------------------------

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Hapus field helper yang tidak perlu disimpan
        unset($data['current_password']);

        // Jika password baru kosong, jangan update password
        if (blank($data['password'] ?? null)) {
            unset($data['password'], $data['password_confirmation']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Profil berhasil diperbarui')
            ->success()
            ->send();
    }

    // -------------------------------------------------------------------------
    // Navigasi
    // -------------------------------------------------------------------------

    // public static function getNavigationLabel(): string
    // {
    //     return 'Profil Saya';
    // }

    // Tampilkan di sidebar (opsional — hapus jika hanya mau via user menu)
    public static function shouldRegisterNavigation(): bool
    {
        return false; // set true jika ingin tampil di sidebar
    }
}
