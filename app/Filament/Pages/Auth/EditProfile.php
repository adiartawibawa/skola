<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    protected static string $layout = 'filament-panels::components.layout.index';

    public function getTitle(): string
    {
        return 'Profil Saya';
    }

    public function getHeading(): string
    {
        return 'Profil Saya';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola informasi akun dan keamanan akun Anda.';
    }

    // -------------------------------------------------------------------------
    // Layout
    // -------------------------------------------------------------------------

    // public function getMaxContentWidth(): Width|string|null
    // {
    //     return Width::ScreenExtraLarge;
    // }

    // -------------------------------------------------------------------------
    // Form
    // -------------------------------------------------------------------------

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            // =================================================================
            // PROFILE HEADER
            // =================================================================

            Grid::make([
                'default' => 1,
                'lg' => 4,
            ])
                ->schema([

                    // ---------------------------------------------------------
                    // Avatar Card
                    // ---------------------------------------------------------

                    Section::make()
                        ->schema([

                            SpatieMediaLibraryFileUpload::make('avatar')
                                ->label('')
                                ->collection('avatars')
                                ->image()
                                ->avatar()
                                ->circleCropper()
                                ->imageEditor()
                                ->imageEditorAspectRatios([
                                    '1:1',
                                ])
                                ->maxSize(2048)
                                ->acceptedFileTypes([
                                    'image/jpeg',
                                    'image/png',
                                    'image/webp',
                                ])
                                ->helperText('Format JPG, PNG, atau WebP. Maksimal 2MB.')
                                ->alignCenter(),
                        ])
                        ->columnSpan([
                            'lg' => 1,
                        ])
                        ->compact(),

                    // ---------------------------------------------------------
                    // Informasi Profil
                    // ---------------------------------------------------------

                    Section::make('Informasi Profil')
                        ->description('Perbarui informasi akun dan alamat email Anda.')
                        ->icon(Heroicon::OutlinedUser)
                        ->schema([

                            Grid::make([
                                'default' => 1,
                                'md' => 2,
                            ])
                                ->schema([

                                    $this->getNameFormComponent()
                                        ->label('Nama Lengkap')
                                        ->placeholder('Masukkan nama lengkap'),

                                    $this->getEmailFormComponent()
                                        ->label('Alamat Email')
                                        ->placeholder('contoh@email.com'),

                                ]),

                        ])
                        ->columnSpan([
                            'lg' => 3,
                        ]),
                ]),

            // =================================================================
            // INFORMASI AKUN
            // =================================================================

            Section::make('Informasi Akun')
                ->description('Informasi dasar akun Anda.')
                ->icon('heroicon-o-information-circle')
                ->schema([

                    Grid::make([
                        'default' => 1,
                        'md' => 3,
                    ])
                        ->schema([

                            TextInput::make('roles_display')
                                ->label('Role')
                                ->readOnly()
                                ->dehydrated(false)
                                ->formatStateUsing(
                                    fn () => auth()->user()?->getRoleNames()->implode(', ') ?: '—'
                                ),

                            TextInput::make('joined_at_display')
                                ->label('Bergabung Sejak')
                                ->readOnly()
                                ->dehydrated(false)
                                ->formatStateUsing(
                                    fn () => auth()->user()?->created_at?->translatedFormat('d F Y') ?: '—'
                                ),

                            TextInput::make('email_verified_display')
                                ->label('Status Email')
                                ->readOnly()
                                ->dehydrated(false)
                                ->formatStateUsing(
                                    fn () => auth()->user()?->hasVerifiedEmail()
                                        ? 'Terverifikasi'
                                        : 'Belum Diverifikasi'
                                ),

                        ]),
                ])
                ->collapsible()
                ->collapsed(),

            // =================================================================
            // SECURITY
            // =================================================================

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

                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])
                        ->schema([

                            $this->getPasswordFormComponent()
                                ->label('Password Baru')
                                ->password()
                                ->revealable()
                                ->autocomplete('new-password')
                                ->rule(Password::defaults()),

                            $this->getPasswordConfirmationFormComponent()
                                ->label('Konfirmasi Password Baru')
                                ->password()
                                ->revealable()
                                ->autocomplete('new-password'),

                        ]),
                ])
                ->collapsible()
                ->collapsed(),

        ]);
    }

    // -------------------------------------------------------------------------
    // Fill Form
    // -------------------------------------------------------------------------

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['password']);

        return $data;
    }

    // -------------------------------------------------------------------------
    // Save Form
    // -------------------------------------------------------------------------

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['current_password']);

        if (blank($data['password'] ?? null)) {
            unset(
                $data['password'],
                $data['password_confirmation']
            );
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
    // Navigation
    // -------------------------------------------------------------------------

    public static function getNavigationLabel(): string
    {
        return 'Profil Saya';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
