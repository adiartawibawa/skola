<?php

namespace App\Filament\Pages;

use App\Filament\Traits\HasShieldPageAuthorization;
use App\Filament\Widgets\LatestUsersWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\UserRegistrationChartWidget;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;
    use HasShieldPageAuthorization;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $title = 'Dashboard';

    // -------------------------------------------------------------------------
    // Filter Form
    // -------------------------------------------------------------------------

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Grid::make(4)->schema([

                        // Periode cepat — mengisi start & end date otomatis
                        Select::make('period')
                            ->label('Periode')
                            ->placeholder('Pilih periode...')
                            ->options([
                                'today' => 'Hari Ini',
                                'yesterday' => 'Kemarin',
                                'this_week' => 'Minggu Ini',
                                'last_week' => 'Minggu Lalu',
                                'this_month' => 'Bulan Ini',
                                'last_month' => 'Bulan Lalu',
                                'this_year' => 'Tahun Ini',
                                'last_30_days' => '30 Hari Terakhir',
                                'last_90_days' => '90 Hari Terakhir',
                                'custom' => 'Kustom...',
                            ])
                            ->default('this_month')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                [$start, $end] = self::resolvePeriod($state);
                                $set('start_date', $start);
                                $set('end_date', $end);
                            }),

                        DatePicker::make('start_date')
                            ->label('Dari Tanggal')
                            ->default(Carbon::now()->startOfMonth())
                            ->maxDate(fn ($get) => $get('end_date') ?? today())
                            ->live(onBlur: true)
                            ->visible(fn ($get) => $get('period') === 'custom'),

                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->default(Carbon::now()->endOfDay())
                            ->minDate(fn ($get) => $get('start_date'))
                            ->maxDate(today())
                            ->live(onBlur: true)
                            ->visible(fn ($get) => $get('period') === 'custom'),

                    ]),
                ])
                ->extraAttributes(['class' => 'py-2'])
                ->columnSpanFull(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Widgets yang ditampilkan & urutannya
    // -------------------------------------------------------------------------

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            UserRegistrationChartWidget::class,
            LatestUsersWidget::class,
        ];
    }

    #[Override]
    public function getColumns(): int|array
    {
        return 2;
    }

    // -------------------------------------------------------------------------
    // Helper: resolve tanggal berdasarkan periode yang dipilih
    // -------------------------------------------------------------------------

    public static function resolvePeriod(?string $period): array
    {
        return match ($period) {
            'today' => [today()->toDateString(), today()->toDateString()],
            'yesterday' => [today()->subDay()->toDateString(), today()->subDay()->toDateString()],
            'this_week' => [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()],
            'last_week' => [Carbon::now()->subWeek()->startOfWeek()->toDateString(), Carbon::now()->subWeek()->endOfWeek()->toDateString()],
            'this_month' => [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->toDateString()],
            'last_month' => [Carbon::now()->subMonth()->startOfMonth()->toDateString(), Carbon::now()->subMonth()->endOfMonth()->toDateString()],
            'this_year' => [Carbon::now()->startOfYear()->toDateString(), Carbon::now()->endOfYear()->toDateString()],
            'last_30_days' => [today()->subDays(30)->toDateString(), today()->toDateString()],
            'last_90_days' => [today()->subDays(90)->toDateString(), today()->toDateString()],
            default => [Carbon::now()->startOfMonth()->toDateString(), today()->toDateString()],
        };
    }
}
