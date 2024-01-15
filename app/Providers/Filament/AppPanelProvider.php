<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Tenancy\EditBranchProfile;
use App\Filament\Pages\Tenancy\RegisterBranch;
use App\Http\Middleware\SyncSpatiePermissionsWithFilamentTenants;
use App\Models\Branch;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Tables\Table;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->colors([
                'primary' => Color::Neutral,
            ])
            ->font('Poppins')
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenantMiddleware([
                SyncSpatiePermissionsWithFilamentTenants::class,
            ], isPersistent: true)
            ->tenant(Branch::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterBranch::class)
            ->tenantProfile(EditBranchProfile::class)
            ->spa();
    }

    public function boot(): void
    {
        Table::$defaultCurrency = 'bdt';
        Table::$defaultDateDisplayFormat = 'd-M-Y';
        Table::$defaultTimeDisplayFormat = 'h:i:s A';
        Table::$defaultDateTimeDisplayFormat = 'd-M-Y h:i:s A';

        Model::resolveRelationUsing('owner', function (Model $model): BelongsTo {
            return $model->belongsTo(User::class, 'owner_id');
        });
        Model::resolveRelationUsing(
            ($panel = Filament::getCurrentPanel())->getTenantOwnershipRelationshipName(),
            fn (Model $model): BelongsTo => $model->belongsTo(
                $tenantModel = $panel->getTenantModel(),
                app($tenantModel)->getForeignKey(),
            ),
        );
    }
}
