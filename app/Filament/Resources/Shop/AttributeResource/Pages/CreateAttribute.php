<?php

namespace App\Filament\Resources\Shop\AttributeResource\Pages;

use App\Filament\Resources\Shop\AttributeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttribute extends CreateRecord
{
    protected static string $resource = AttributeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach (['searchable', 'filterable'] as $is) {
            $data['is_' . $is] = in_array($is, $data['is']);
        }

        unset($data['is']);

        return $data;
    }
}
