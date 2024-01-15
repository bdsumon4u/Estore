<?php

namespace App\Filament\Resources\Shield\UserResource;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

trait UserForm
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Personal Information')
                        ->description(function (string $operation) {
                            if ($operation === 'create') {
                                return 'If an user with the same email already exists, the existing user will be used instead of creating a new one.';
                            }
                        })
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->key('name')
                                ->autofocus()
                                ->required()
                                ->maxLength(255)
                                ->disabled(fn (Forms\Get $get) => $get('exists')),
                            Forms\Components\TextInput::make('email')
                                ->afterStateUpdated(function (string $operation, Forms\Set $set, ?string $state) use (&$form) {
                                    if ($operation !== 'create' || ! $user = User::firstWhere('email', $state)) {
                                        return $set('exists', false);
                                    }

                                    $form->fill($user->toArray());
                                    $set('exists', true);
                                })
                                ->email()
                                ->required()
                                // ->unique(static::getModel(), 'email', ignoreRecord: auth()->user()?->email)
                                ->maxLength(255)
                                ->debounce(),
                        ])
                        ->columns(['lg' => 1 + ($form->getOperation() === 'edit')]),
                    Forms\Components\Section::make('Change Password')
                        ->visibleOn('edit')
                        ->schema([
                            Forms\Components\TextInput::make('password')
                                ->label('New password')
                                ->password()
                                ->confirmed()
                                ->minLength(8),

                            Forms\Components\TextInput::make('password_confirmation')
                                ->password()
                                ->dehydrated(false),
                        ])
                        ->columns(['lg' => 2]),
                ])
                    ->columnSpan(['lg' => 2 + static::editingOwner($form)]),
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Authentication / Authorization')
                        ->description(function (string $operation) {
                            if ($operation === 'create') {
                                return 'If an user with the same email already exists, this section will be ignored.';
                            }
                        })
                        ->schema([
                            Forms\Components\Select::make('roles')
                                ->relationship('roles', 'name')
                                ->getOptionLabelFromRecordUsing(fn (Role $record) => Str::headline($record->name))
                                ->saveRelationshipsUsing(fn (User $user, $state) => $user->syncRoles(
                                    Role::query(fn ($query) => $query->whereBelongsTo(
                                        ($tenant = Filament::getTenant())->orWhereNull($tenant->getForeignKey())
                                    ))->findOrFail($state)
                                ))
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->native(false)
                                ->required(),
                            Forms\Components\TextInput::make('password')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->visibleOn('create'),
                        ])
                        ->hidden(fn () => static::editingOwner($form)),
                ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (Forms\Get $get) => $get('exists')),
            ])
            ->columns(3);
    }

    private static function editingOwner(Form $form): bool
    {
        return $form->getOperation() === 'edit' &&
            $form->getRecord()->id === Filament::getTenant()->owner_id;
    }
}
