<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Permission;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => $this->record->name === 'super-admin'),
        ];
    }

    /**
     * Setelah role diupdate, sync permission yang dipilih.
     */
    protected function afterSave(): void
    {
        $this->syncPermissions();
    }

    protected function syncPermissions(): void
    {
        $selectedIds = $this->collectSelectedPermissionIds();

        // syncPermissions akan hapus yang lama dan pasang yang baru
        $this->record->syncPermissions(
            Permission::whereIn('id', $selectedIds)->get()
        );
    }

    protected function collectSelectedPermissionIds(): array
    {
        $data = $this->data;
        $ids = [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permissions_') && is_array($value)) {
                $ids = array_merge($ids, $value);
            }
        }

        return array_unique(array_filter($ids));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
