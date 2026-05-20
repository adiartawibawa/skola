<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', config('app.name', 'My Application'));
        $this->migrator->add('general.site_tagline', null);
        $this->migrator->add('general.site_logo', null);
        $this->migrator->add('general.site_favicon', null);
        $this->migrator->add('general.timezone', 'Asia/Jakarta');
        $this->migrator->add('general.language', 'id');
        $this->migrator->add('general.date_format', 'd M Y');
        $this->migrator->add('general.primary_color', 'red');
        $this->migrator->add('general.is_maintenance', false);
        $this->migrator->add('general.maintenance_message', 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.');

    }
};
