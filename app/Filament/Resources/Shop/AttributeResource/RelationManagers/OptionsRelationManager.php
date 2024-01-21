<?php

namespace App\Filament\Resources\Shop\AttributeResource\RelationManagers;

use App\Models\Shop\Attribute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->visible(fn () => $this->getOwnerRecord()->type !== 'colorpicker')
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('key', Str::slug($state))),
                Forms\Components\ColorPicker::make('value')
                    ->required()
                    ->live(onBlur: true)
                    ->visible(fn () => $this->getOwnerRecord()->type === 'colorpicker')
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('key', Str::slug($state))),
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->columns([
                $this->getOwnerRecord()->type === 'colorpicker'
                    ? Tables\Columns\ColorColumn::make('value')
                    : Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('key'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (static::shouldSkipAuthorization()) {
            return true;
        }

        return in_array($ownerRecord->type, Attribute::fieldsWithOptions());
    }
}
