<?php

namespace App\Support;

use App\Settings\GeneralSettings;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AppSettings
{
    private static ?GeneralSettings $settings = null;

    public static function instance(): ?GeneralSettings
    {
        if (self::$settings !== null) {
            return self::$settings;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return null;
            }

            return self::$settings = app(GeneralSettings::class);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function siteName(): string
    {
        return self::instance()?->site_name
            ?? config('app.name');
    }

    public static function primaryColor(): string
    {
        $valid = [
            'slate',
            'gray',
            'zinc',
            'neutral',
            'stone',
            'red',
            'orange',
            'amber',
            'yellow',
            'lime',
            'green',
            'emerald',
            'teal',
            'cyan',
            'sky',
            'blue',
            'indigo',
            'violet',
            'purple',
            'fuchsia',
            'pink',
            'rose',
        ];

        $color = self::instance()?->primary_color ?? 'red';

        return in_array($color, $valid)
            ? $color
            : 'red';
    }

    public static function filamentColor(): array
    {
        return match (self::primaryColor()) {
            'slate' => Color::Slate,
            'gray' => Color::Gray,
            'zinc' => Color::Zinc,
            'neutral' => Color::Neutral,
            'stone' => Color::Stone,
            'red' => Color::Red,
            'orange' => Color::Orange,
            'amber' => Color::Amber,
            'yellow' => Color::Yellow,
            'lime' => Color::Lime,
            'green' => Color::Green,
            'emerald' => Color::Emerald,
            'teal' => Color::Teal,
            'cyan' => Color::Cyan,
            'sky' => Color::Sky,
            'blue' => Color::Blue,
            'indigo' => Color::Indigo,
            'violet' => Color::Violet,
            'purple' => Color::Purple,
            'fuchsia' => Color::Fuchsia,
            'pink' => Color::Pink,
            'rose' => Color::Rose,
            default => Color::Red,
        };
    }

    public static function logo(): ?string
    {
        $path = self::instance()?->site_logo;

        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    public static function favicon(): ?string
    {
        $path = self::instance()?->site_favicon;

        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }
}
