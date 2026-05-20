<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Settings\SocialSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSocialSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static string $settings = SocialSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Media Sosial';

    protected static ?string $title = 'Pengaturan Media Sosial';

    protected static ?int $navigationSort = 3;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tautan Media Sosial')
                    ->description('Tambahkan tautan profil media sosial yang akan ditampilkan di situs Anda')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('facebook_url')
                                    ->label('Facebook')
                                    ->placeholder('https://facebook.com/namahalaman')
                                    ->url()
                                    ->prefixIcon('heroicon-o-link')
                                    ->maxLength(255),

                                TextInput::make('instagram_url')
                                    ->label('Instagram')
                                    ->placeholder('https://instagram.com/namaakun')
                                    ->url()
                                    ->prefixIcon('heroicon-o-link')
                                    ->maxLength(255),

                                TextInput::make('twitter_url')
                                    ->label('X (Twitter)')
                                    ->placeholder('https://x.com/namaakun')
                                    ->url()
                                    ->prefixIcon('heroicon-o-link')
                                    ->maxLength(255),

                                TextInput::make('youtube_url')
                                    ->label('YouTube')
                                    ->placeholder('https://youtube.com/@namachannel')
                                    ->url()
                                    ->prefixIcon('heroicon-o-link')
                                    ->maxLength(255),

                                TextInput::make('linkedin_url')
                                    ->label('LinkedIn')
                                    ->placeholder('https://linkedin.com/company/namaakun')
                                    ->url()
                                    ->prefixIcon('heroicon-o-link')
                                    ->maxLength(255),

                                TextInput::make('tiktok_url')
                                    ->label('TikTok')
                                    ->placeholder('https://tiktok.com/@namaakun')
                                    ->url()
                                    ->prefixIcon('heroicon-o-link')
                                    ->maxLength(255),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Kontak Pesan')
                    ->description('Nomor atau akun untuk komunikasi langsung')
                    ->collapsible()
                    ->schema([
                        TextInput::make('whatsapp_number')
                            ->label('Nomor WhatsApp')
                            ->placeholder('6281234567890')
                            ->tel()
                            ->helperText('Format internasional tanpa tanda + (contoh: 6281234567890)')
                            ->prefix('+')
                            ->maxLength(20),
                    ])->columnSpanFull(),

            ]);
    }
}
