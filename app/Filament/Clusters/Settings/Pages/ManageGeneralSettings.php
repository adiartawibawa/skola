<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageGeneralSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string $settings = GeneralSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Umum';

    protected static ?string $title = 'Pengaturan Umum';

    protected static ?int $navigationSort = 1;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Situs')
                    ->description('Informasi dasar yang mewakili identitas aplikasi Anda')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('site_name')
                                    ->label('Nama Situs')
                                    ->placeholder('Nama aplikasi Anda')
                                    ->required()
                                    ->maxLength(100),

                                TextInput::make('site_tagline')
                                    ->label('Tagline')
                                    ->placeholder('Slogan singkat aplikasi Anda')
                                    ->maxLength(160),
                            ]),

                        Grid::make(2)
                            ->schema([
                                FileUpload::make('site_logo')
                                    ->label('Logo Situs')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('settings/logos')
                                    ->visibility('public')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp'])
                                    ->helperText('Rekomendasi: PNG/SVG transparan, maks. 2MB'),

                                FileUpload::make('site_favicon')
                                    ->label('Favicon')
                                    ->image()
                                    ->directory('settings/favicons')
                                    ->visibility('public')
                                    ->maxSize(512)
                                    ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'])
                                    ->helperText('Rekomendasi: PNG 32×32px atau 64×64px, maks. 512KB'),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Tema Aplikasi')
                    ->description('Warna utama yang digunakan di seluruh tampilan admin panel')
                    ->collapsible()
                    ->schema([
                        Select::make('primary_color')
                            ->label('Warna Utama')
                            ->options(self::colorOptions())
                            ->allowHtml()
                            ->required()
                            ->native(false)
                            ->columnSpanFull()
                            ->helperText('Perubahan warna akan diterapkan setelah halaman di-refresh'),
                    ])->columnSpanFull(),

                Section::make('Lokalisasi')
                    ->description('Pengaturan bahasa, zona waktu, dan format tanggal')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('language')
                                    ->label('Bahasa')
                                    ->options([
                                        'id' => '🇮🇩 Bahasa Indonesia',
                                        'en' => '🇺🇸 English',
                                    ])
                                    ->required()
                                    ->native(false),

                                Select::make('timezone')
                                    ->label('Zona Waktu')
                                    ->options(
                                        collect(timezone_identifiers_list())
                                            ->mapWithKeys(fn ($tz) => [$tz => $tz])
                                            ->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->native(false),

                                Select::make('date_format')
                                    ->label('Format Tanggal')
                                    ->options([
                                        'd M Y' => '31 Jan 2026',
                                        'd/m/Y' => '31/01/2026',
                                        'Y-m-d' => '2026-01-31',
                                        'd-m-Y' => '31-01-2026',
                                        'M d, Y' => 'Jan 31, 2026',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Mode Pemeliharaan')
                    ->description('Aktifkan untuk menampilkan halaman maintenance kepada pengunjung')
                    ->collapsible()
                    ->schema([
                        Toggle::make('is_maintenance')
                            ->label('Aktifkan Mode Pemeliharaan')
                            ->helperText('Pengguna yang sedang login sebagai admin tetap dapat mengakses situs')
                            ->live(),

                        Textarea::make('maintenance_message')
                            ->label('Pesan Pemeliharaan')
                            ->placeholder('Sistem sedang dalam pemeliharaan...')
                            ->rows(3)
                            ->maxLength(500)
                            ->visible(fn ($get) => $get('is_maintenance')),
                    ])->columnSpanFull(),

            ]);
    }

    /**
     * Daftar warna Tailwind dengan swatch HTML untuk Select.
     * Nilai yang disimpan = nama warna (key), diterjemahkan ke
     * Filament Color constant di AdminPanelProvider.
     */
    private static function colorOptions(): array
    {
        $colors = [
            'slate' => ['Slate',   '#64748b'],
            'gray' => ['Gray',    '#6b7280'],
            'zinc' => ['Zinc',    '#71717a'],
            'red' => ['Red',     '#ef4444'],
            'orange' => ['Orange',  '#f97316'],
            'amber' => ['Amber',   '#f59e0b'],
            'yellow' => ['Yellow',  '#eab308'],
            'lime' => ['Lime',    '#84cc16'],
            'green' => ['Green',   '#22c55e'],
            'emerald' => ['Emerald', '#10b981'],
            'teal' => ['Teal',    '#14b8a6'],
            'cyan' => ['Cyan',    '#06b6d4'],
            'sky' => ['Sky',     '#0ea5e9'],
            'blue' => ['Blue',    '#3b82f6'],
            'indigo' => ['Indigo',  '#6366f1'],
            'violet' => ['Violet',  '#8b5cf6'],
            'purple' => ['Purple',  '#a855f7'],
            'fuchsia' => ['Fuchsia', '#d946ef'],
            'pink' => ['Pink',    '#ec4899'],
            'rose' => ['Rose',    '#f43f5e'],
        ];

        return collect($colors)
            ->mapWithKeys(fn ($item, $key) => [
                $key => sprintf(
                    '<span style="display:flex;align-items:center;gap:10px"><span style="width:18px;height:18px;border-radius:50%%;background:%s;display:inline-block;flex-shrink:0;border:1px solid rgba(0,0,0,.1)"></span><span>%s</span></span>',
                    $item[1],
                    $item[0]
                ),
            ])
            ->toArray();

    }
}
