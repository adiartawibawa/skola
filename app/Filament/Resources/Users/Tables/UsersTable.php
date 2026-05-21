<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('')
                    ->collection('avatars')
                    ->conversion('thumb')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => self::generateAvatarUrl($record->name))
                    ->width(40),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('Alamat Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email disalin!')
                    ->icon('heroicon-m-envelope'),

                TextColumn::make('email_verified_at')
                    ->label('Status Verifikasi')
                    ->sortable()
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->email_verified_at ? 'Terverifikasi' : 'Belum Terverifikasi')
                    ->color(fn ($state) => $state === 'Terverifikasi' ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->striped()
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('Belum ada pengguna')
            ->emptyStateDescription('Mulai dengan menambahkan pengguna baru.');

    }

    private static function generateAvatarUrl(string $name): string
    {
        $initials = collect(explode(' ', $name))
            ->map(fn ($word) => strtoupper($word[0]))
            ->take(2)
            ->join('');

        return 'https://ui-avatars.com/api/?name='.urlencode($initials).'&color=7F9CF5&background=EBF4FF';
    }
}
