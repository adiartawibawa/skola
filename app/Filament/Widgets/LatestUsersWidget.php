<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Users\UserResource;
use App\Models\User as ModelsUser;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestUsersWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Pengguna Terbaru';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    // -------------------------------------------------------------------------

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('')
                    ->collection('avatars')
                    ->conversion('thumb')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => $this->generateAvatarUrl($record->name))
                    ->width(36),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->email),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(',')
                    ->color(fn (string $state): string => match ($state) {
                        'super-admin' => 'danger',
                        'admin' => 'warning',
                        default => 'primary',
                    })
                    ->placeholder('—'),

                TextColumn::make('email_verified_at')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->email_verified_at ? 'Verified' : 'Unverified')
                    ->color(fn ($state) => $state === 'Verified' ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->label('Bergabung')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('')
                    ->icon('heroicon-m-pencil-square')
                    ->tooltip('Edit')
                    ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    protected function getTableQuery(): Builder
    {
        [$start, $end] = $this->resolveDateRange();

        return ModelsUser::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay(),
            ])
            ->with('roles');
    }

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

    protected function generateAvatarUrl(string $name): string
    {
        $initials = collect(explode(' ', $name))
            ->map(fn ($word) => strtoupper($word[0]))
            ->take(2)
            ->join('');

        return 'https://ui-avatars.com/api/?name='.urlencode($initials).'&color=7F9CF5&background=EBF4FF';
    }
}
