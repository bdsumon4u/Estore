<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

trait FormWizard
{
    public function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                Wizard\Step::make('General Information')->schema([
                    TextInput::make('name')
                        ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                            $set('slug', Str::slug($state));
                        })
                        ->live(onBlur: true)
                        ->required(),
                    TextInput::make('slug')
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where(function ($query) {
                            return $query->where('owner_id', Filament::auth()->user()->id);
                        }))
                        ->required(),
                    TextInput::make('email')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->required(),
                    PhoneInput::make('phone')
                        ->disableIpLookUp()
                        ->disallowDropdown()
                        ->defaultCountry('bd')
                        ->initialCountry('bd')
                        ->onlyCountries(['bd'])
                        ->unique(ignoreRecord: true)
                        ->required(),
                ])->columns(2),
                Wizard\Step::make('Address Information')->schema([
                    Select::make('district_id')
                        ->relationship('district', titleAttribute: 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('thana_id')
                        ->relationship('thana', 'name', function (Forms\Get $get, $query) {
                            $query->where('district_id', $get('district_id'));
                        })
                        ->searchable()
                        ->required(),
                    TextInput::make('street_address')
                        ->columnSpan(2)
                        ->required(),
                ])->columns(2),
            ])
                ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                <x-filament::button
                    type="submit"
                >
                    Submit
                </x-filament::button>
            BLADE))),
        ]);
    }
}
