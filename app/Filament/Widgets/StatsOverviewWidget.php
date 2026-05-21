<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    // Widget memakan full width
    protected int|string|array $columnSpan = 'full';

    // -------------------------------------------------------------------------

    protected function getStats(): array
    {
        [$start, $end] = $this->resolveDateRange();

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();

        // Periode sebelumnya untuk perbandingan tren
        $diffDays = $startDate->diffInDays($endDate) ?: 1;
        $prevStart = $startDate->copy()->subDays($diffDays)->startOfDay();
        $prevEnd = $startDate->copy()->subDay()->endOfDay();

        // ── User stats ────────────────────────────────────────────────────
        $totalUsers = User::whereNull('deleted_at')->count();
        $newUsers = User::whereNull('deleted_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $prevNewUsers = User::whereNull('deleted_at')
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->count();

        $verifiedUsers = User::whereNull('deleted_at')
            ->whereNotNull('email_verified_at')
            ->count();

        $unverifiedUsers = User::whereNull('deleted_at')
            ->whereNull('email_verified_at')
            ->count();

        // ── Role & Permission stats ───────────────────────────────────────
        $totalRoles = Role::count();
        $totalPerms = Permission::count();

        return [
            // Stat 1: Total user
            Stat::make('Total Pengguna', number_format($totalUsers))
                ->description('Seluruh pengguna terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            // Stat 2: Pengguna baru dalam periode
            Stat::make('Pengguna Baru', number_format($newUsers))
                ->description($this->trendLabel($newUsers, $prevNewUsers))
                ->descriptionIcon($newUsers >= $prevNewUsers ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($newUsers >= $prevNewUsers ? 'success' : 'warning')
                ->chart($this->sparklineData($startDate, $endDate)),

            // Stat 3: Status verifikasi
            Stat::make('Email Terverifikasi', number_format($verifiedUsers))
                ->description("{$unverifiedUsers} belum terverifikasi")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($unverifiedUsers > 0 ? 'warning' : 'success'),

            // Stat 4: Role & Permission
            Stat::make('Role Aktif', number_format($totalRoles))
                ->description("{$totalPerms} permission terdaftar")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Ambil start_date & end_date dari filter, fallback ke bulan ini.
     */
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

    /**
     * Label tren: "+5 dari periode sebelumnya" atau "Sama dengan sebelumnya"
     */
    protected function trendLabel(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? "↑ {$current} baru" : 'Belum ada data sebelumnya';
        }

        $diff = $current - $previous;
        $percent = round(abs($diff / $previous) * 100);

        if ($diff > 0) {
            return "↑ {$percent}% dari periode sebelumnya";
        } elseif ($diff < 0) {
            return "↓ {$percent}% dari periode sebelumnya";
        }

        return 'Sama dengan periode sebelumnya';
    }

    /**
     * Data sparkline: jumlah user baru per hari dalam periode.
     */
    protected function sparklineData(Carbon $start, Carbon $end): array
    {
        $data = [];
        $current = $start->copy();

        // Batasi maksimal 14 titik agar sparkline tetap bersih
        $days = min((int) $start->diffInDays($end) + 1, 14);
        $step = max(1, (int) ceil($start->diffInDays($end) / 14));

        for ($i = 0; $i < $days; $i++) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->addDays($step - 1)->endOfDay();

            $data[] = User::whereNull('deleted_at')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

            $current->addDays($step);
        }

        return $data;
    }
}
