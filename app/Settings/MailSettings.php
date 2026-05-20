<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MailSettings extends Settings
{
    public string $mailer;

    public string $host;

    public int $port;

    public ?string $encryption;

    public ?string $username;

    public ?string $password;

    public string $from_address;

    public string $from_name;

    public static function group(): string
    {
        return 'mail';
    }

    /**
     * Terapkan pengaturan mail ke konfigurasi Laravel secara runtime.
     */
    public function applyToConfig(): void
    {
        config([
            'mail.default' => $this->mailer,
            'mail.mailers.smtp.host' => $this->host,
            'mail.mailers.smtp.port' => $this->port,
            'mail.mailers.smtp.encryption' => $this->encryption,
            'mail.mailers.smtp.username' => $this->username,
            'mail.mailers.smtp.password' => $this->password,
            'mail.from.address' => $this->from_address,
            'mail.from.name' => $this->from_name,
        ]);
    }
}
