<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Settings\MailSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;

class ManageMailSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string $settings = MailSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Email';

    protected static ?string $title = 'Pengaturan Email';

    protected static ?int $navigationSort = 2;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konfigurasi SMTP')
                    ->description('Pengaturan server pengirim email')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('mailer')
                                    ->label('Driver Mail')
                                    ->options([
                                        'smtp' => 'SMTP',
                                        'mailgun' => 'Mailgun',
                                        'ses' => 'Amazon SES',
                                        'sendmail' => 'Sendmail',
                                        'log' => 'Log (Testing)',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->live(),

                                Select::make('encryption')
                                    ->label('Enkripsi')
                                    ->options([
                                        'tls' => 'TLS',
                                        'ssl' => 'SSL',
                                        '' => 'Tidak Ada',
                                    ])
                                    ->native(false)
                                    ->default('tls'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('host')
                                    ->label('SMTP Host')
                                    ->placeholder('smtp.example.com')
                                    ->required()
                                    ->visible(fn ($get) => $get('mailer') === 'smtp'),

                                TextInput::make('port')
                                    ->label('Port')
                                    ->placeholder('587')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(65535)
                                    ->visible(fn ($get) => $get('mailer') === 'smtp'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('username')
                                    ->label('Username')
                                    ->placeholder('username@example.com')
                                    ->autocomplete('off'),

                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->autocomplete('new-password'),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Pengirim Default')
                    ->description('Identitas pengirim yang tampil pada semua email keluar')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('from_address')
                                    ->label('Alamat Email Pengirim')
                                    ->placeholder('noreply@example.com')
                                    ->email()
                                    ->required(),

                                TextInput::make('from_name')
                                    ->label('Nama Pengirim')
                                    ->placeholder('Nama Aplikasi')
                                    ->required()
                                    ->maxLength(100),
                            ]),
                    ])->columnSpanFull(),

            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label('Kirim Email Percobaan')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->form([
                    TextInput::make('recipient')
                        ->label('Kirim ke')
                        ->placeholder('email@tujuan.com')
                        ->email()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        /** @var MailSettings $settings */
                        $settings = app(MailSettings::class);
                        $settings->applyToConfig();

                        Mail::raw(
                            'Ini adalah email percobaan dari '.$settings->from_name.'. Konfigurasi email Anda berfungsi dengan baik!',
                            fn ($message) => $message
                                ->to($data['recipient'])
                                ->subject('Email Percobaan — '.$settings->from_name)
                        );

                        Notification::make()
                            ->title('Email percobaan berhasil dikirim')
                            ->body('Periksa kotak masuk '.$data['recipient'])
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal mengirim email percobaan')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
