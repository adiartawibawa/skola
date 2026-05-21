<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Permission;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /**
     * Setelah role dibuat, sync semua permission yang dipilih.
     * Kita kumpulkan semua ID dari field CheckboxList per-grup.
     */
    protected function afterCreate(): void
    {
        $this->syncPermissions();
    }

    protected function syncPermissions(): void
    {
        $selectedIds = $this->collectSelectedPermissionIds();

        if (! empty($selectedIds)) {
            $this->record->syncPermissions(
                Permission::whereIn('id', $selectedIds)->get()
            );
        }
    }

    /**
     * Kumpulkan semua UUID permission yang dipilih dari semua grup CheckboxList.
     * Field bernama "permissions_{group}" — dikumpulkan dari form data.
     */
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
