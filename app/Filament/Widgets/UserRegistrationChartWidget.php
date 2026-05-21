<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class UserRegistrationChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Registrasi Pengguna';

    protected ?string $description = 'Jumlah pengguna baru per hari dalam periode yang dipilih';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    // Tipe chart: 'line', 'bar', 'pie', 'doughnut', 'radar', 'polarArea'
    // protected static string $color = 'primary';

    // -------------------------------------------------------------------------

    protected function getData(): array
    {
        [$start, $end] = $this->resolveDateRange();

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();

        // Generate semua tanggal dalam rentang
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        // Grouping: jika periode > 60 hari, group per minggu
        $groupByWeek = $startDate->diffInDays($endDate) > 60;

        if ($groupByWeek) {
            return $this->buildWeeklyData($startDate, $endDate);
        }

        return $this->buildDailyData($period);
    }

    protected function buildDailyData(CarbonPeriod $period): array
    {
        $labels = [];
        $values = [];

        // Ambil semua user baru dalam periode sekaligus (satu query)
        $startDate = $period->getStartDate();
        $endDate = $period->getEndDate();

        $registrations = User::whereNull('deleted_at')
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        foreach ($period as $date) {
            $labels[] = $date->translatedFormat('d M');
            $values[] = $registrations[$date->toDateString()] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pengguna Baru',
                    'data' => $values,
                    'fill' => true,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'tension' => 0.3,
                    'pointRadius' => 3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function buildWeeklyData(Carbon $startDate, Carbon $endDate): array
    {
        $labels = [];
        $values = [];
        $current = $startDate->copy()->startOfWeek();

        while ($current->lte($endDate)) {
            $weekEnd = $current->copy()->endOfWeek()->min($endDate);

            $count = User::whereNull('deleted_at')
                ->whereBetween('created_at', [$current->startOfDay(), $weekEnd->endOfDay()])
                ->count();

            $labels[] = $current->translatedFormat('d M').' – '.$weekEnd->translatedFormat('d M');
            $values[] = $count;

            $current->addWeek();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pengguna Baru (per minggu)',
                    'data' => $values,
                    'fill' => true,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    // -------------------------------------------------------------------------

    protected function resolveDateRange(): array
    {
        $period = $this->filters['period'] ?? 'this_month';

        if ($period !== 'custom') {
            return Dashboard::resolvePeriod($period);
        }

        return [
            $this->filters['start_date'] ?? Carbon::now()->startOfMonth()->toDateString(),
            $this->filters['end_date'] ?? today()->toDateString(),
        ];
    }
}
