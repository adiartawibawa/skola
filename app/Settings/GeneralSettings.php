<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public ?string $site_tagline;

    public ?string $site_logo;

    public ?string $site_favicon;

    public string $timezone;

    public string $language;

    public string $date_format;

    public string $primary_color;

    public bool $is_maintenance;

    public string $maintenance_message;

    public static function group(): string
    {
        return 'general';
    }
}
