<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => $this->record->id === auth()->id()),
            ForceDeleteAction::make()
                ->hidden(fn () => $this->record->id === auth()->id()),
            RestoreAction::make(),
        ];
    }

    /**
     * Hash password jika diisi, skip jika kosong.
     */
    protected function mutateFormDataBeforeSave(array $data): array
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
            ? ($this->record->email_verified_at ?? now())
            : null;

        return $data;
    }

    /**
     * Sync roles setelah record disimpan.
     * Select dengan ->relationship() sudah handle ini,
     * tapi kita tambahkan guard ekstra untuk kasus edge case.
     */
    protected function afterSave(): void
    {
        $roles = $this->data['roles'] ?? [];

        // Proteksi: jika user mengedit dirinya sendiri,
        // pastikan minimal tetap punya satu role
        if ($this->record->id === auth()->id() && empty($roles)) {
            Notification::make()
                ->title('Peringatan')
                ->body('Anda tidak dapat menghapus semua role dari akun Anda sendiri.')
                ->warning()
                ->send();

            return;
        }

        $this->record->syncRoles($roles);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
