<?php

namespace App\Filament\Resources\Shield\UserResource\Pages;

use App\Filament\Resources\Shield\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['password'] ?? false) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }
}
