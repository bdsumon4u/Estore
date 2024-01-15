<?php

namespace App\Filament\Pages\Tenancy;

namespace App\Filament\Pages\Tenancy;

use Filament\Pages\Tenancy\EditTenantProfile;

class EditBranchProfile extends EditTenantProfile
{
    use FormWizard;

    public static function getLabel(): string
    {
        return 'Branch profile';
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            // $this->getSaveFormAction(),
        ];
    }
}
