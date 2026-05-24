<?php

namespace App\Filament\Clusters\Settings;

use App\Filament\Traits\HasShieldClusterAuthorization;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SettingsCluster extends Cluster
{
    use HasShieldClusterAuthorization;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $title = 'Settings';

    protected static ?int $navigationSort = 10;
}
