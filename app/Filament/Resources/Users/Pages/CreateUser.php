<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Hash password sebelum disimpan.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ---------------------------------------------------------
        // Skip empty password
        // ---------------------------------------------------------

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // ---------------------------------------------------------
        // Email verification
        // ---------------------------------------------------------

        $isVerified = $this->data['is_email_verified'] ?? false;

        $data['email_verified_at'] = $isVerified
            ? now()
            : null;

        return $data;
    }

    /**
     * Select dengan ->relationship() sudah otomatis sync role.
     * Method ini sebagai fallback jika relationship tidak bekerja.
     */
    // protected function afterCreate(): void
    // {
    //     $roles = $this->data['roles'] ?? [];

    //     if (! empty($roles)) {
    //         $this->record->syncRoles($roles);
    //     }
    // }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
