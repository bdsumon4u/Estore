<?php

namespace App\Filament\Resources\Shield\UserResource\Pages;

use App\Filament\Resources\Shield\UserResource;
use App\Models\User;
use Exception;
use Filament\Events\Auth\Registered;
use Filament\Facades\Filament;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['password'] ?? false) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): User
    {
        $record = $this->getModel()::firstOrCreate(
            Arr::only($data, ['email']),
            Arr::except($data, ['email']),
        );

        if (
            static::getResource()::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            if ($record->wasRecentlyCreated) {
                $record->branches()->syncWithoutDetaching($tenant);

                event(new Registered($record));

                $this->sendEmailVerificationNotification($record);

                return $record;
            }

            if ($record->branches()->where('owner_id', '!=', Filament::getTenant()->owner_id)->doesntExist()) {
                $record->branches()->syncWithoutDetaching($tenant);

                Notification::make()
                    ->title('User already exists!')
                    ->body('The existing user has been added to the branch instead of creating a new user.')
                    ->send();

                throw new Halt;
            } else {
                Notification::make()
                    ->title('The user belongs to another company!')
                    ->danger()
                    ->send();

                throw new Halt;
            }
        }

        return $record;
    }

    protected function sendEmailVerificationNotification(Model $user): void
    {
        if (! $user instanceof MustVerifyEmail) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            return;
        }

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

        $notification = new VerifyEmail();
        $notification->url = Filament::getVerifyEmailUrl($user);

        $user->notify($notification);
    }
}
