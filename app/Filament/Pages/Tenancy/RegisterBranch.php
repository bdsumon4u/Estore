<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Branch;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Pages\Concerns;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Panel;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

use function Filament\authorize;

/**
 * @property Form $form
 */
class RegisterBranch extends SimplePage
{
    use Concerns\HasRoutes;
    use FormWizard;
    use InteractsWithFormActions;

    protected ?string $maxWidth = '3xl';

    /**
     * @var view-string
     */
    protected static string $view = 'filament-panels::pages.tenancy.register-tenant';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public ?Model $tenant = null;

    public static function getLabel(): string
    {
        return 'Register branch';
    }

    public static function routes(Panel $panel): void
    {
        $slug = static::getSlug();

        Route::get("/{$slug}", static::class)
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name('registration');
    }

    /**
     * @return string | array<string>
     */
    public static function getRouteMiddleware(Panel $panel): string|array
    {
        return [
            ...(static::isEmailVerificationRequired($panel) ? [static::getEmailVerifiedMiddleware($panel)] : []),
            ...Arr::wrap(static::$routeMiddleware),
        ];
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 404);

        $this->form->fill();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        return $data;
    }

    public function register(): void
    {
        try {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $this->tenant = $this->handleRegistration($data);

            $this->form->model($this->tenant)->saveRelationships();

            $this->callHook('afterRegister');
        } catch (Halt $exception) {
            return;
        }

        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl);
        }
    }

    protected function handleRegistration(array $data): Branch
    {
        return DB::transaction(function () use (&$data) {
            /** @var User */
            $owner = Filament::auth()->user();
            $branch = Branch::create($data + [
                'owner_id' => $owner->id,
            ]);
            $branch->users()->attach($owner);

            $session_team_id = getPermissionsTeamId();
            // set actual new team_id to package instance
            setPermissionsTeamId($branch);
            // get the admin user and assign roles/permissions on new team model
            $owner->assignRole(Utils::getSuperAdminName());
            // restore session team_id to package instance using temporary value stored above
            setPermissionsTeamId($session_team_id);

            return $branch;
        });
    }

    protected function getRedirectUrl(): ?string
    {
        return Filament::getUrl($this->tenant);
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->model($this->getModel())
                    ->statePath('data'),
            ),
        ];
    }

    public function getModel(): string
    {
        return Filament::getTenantModel();
    }

    public function getTitle(): string|Htmlable
    {
        return static::getLabel();
    }

    public static function getSlug(): string
    {
        return static::$slug ?? 'new';
    }

    public function hasLogo(): bool
    {
        return true;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            // $this->getRegisterFormAction(),
        ];
    }

    public function getRegisterFormAction(): Action
    {
        return Action::make('register')
            ->label(static::getLabel())
            ->submit('register');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public static function canView(): bool
    {
        /** @var User */
        $user = Filament::auth()->user();

        return $user->branches()->where('owner_id', '!=', $user->id)->doesntExist();

        try {
            return authorize('create', Filament::getTenantModel())->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }
}
