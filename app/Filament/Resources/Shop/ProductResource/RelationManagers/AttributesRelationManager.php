<?php

namespace App\Filament\Resources\Shop\ProductResource\RelationManagers;

use App\Filament\Resources\Shop\AttributeResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class AttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'attributes';

    protected function attributes(): Collection
    {
        return AttributeResource::getEloquentQuery()->with('options')->get();
    }

    public function form(Form $form): Form
    {
        $attributes = $this->attributes();

        return $form
            ->schema([
                Forms\Components\Select::make('attribute_id')
                    ->label('')
                    ->placeholder('Select an attribute...')
                    ->options($attributes->pluck('name', 'id'))
                    ->live()
                    ->searchable()
                    ->afterStateUpdated(fn (Forms\Components\Select $component) => $component
                        ->getContainer()
                        ->getComponent('dynamicFields')
                        ->getChildComponentContainer()
                        ->fill()
                    )
                    ->disabledOn('edit'),

                Forms\Components\Group::make(function (Forms\Get $get) use ($attributes, $form): array {
                    if (! $attribute = $attributes->firstWhere('id', $get('attribute_id'))) {
                        return [];
                    }

                    if ($attribute->hasTextOption()) {
                        if ($attribute->type === 'datepicker') {
                            return [
                                Forms\Components\DatePicker::make('option_value')
                                    ->placeholder('Select a date...')
                                    ->label('')
                                    ->native(false),
                            ];
                        }

                        return [
                            Forms\Components\TextInput::make('option_value')
                                ->placeholder('Enter a value...')
                                ->label('')
                                ->numeric($attribute->type === 'number'),
                        ];
                    }

                    if ($attribute->hasMultipleOptions()) {
                        return [
                            Forms\Components\CheckboxList::make('value')
                                ->options($attribute->options->pluck('value', 'id'))
                                ->formatStateUsing(function () use ($attribute) {
                                    return $attribute->variations()->where('product_id', $this->getOwnerRecord()->id)->get([])->map->pivot->pluck('option_id')->toArray();
                                })
                                ->label('')
                                ->searchable()
                                ->searchPrompt('Search for an option...')
                                ->columns(2),
                        ];
                    }

                    return [];
                })->key('dynamicFields'),
            ]);
    }

    public function table(Table $table): Table
    {
        $attributes = $this->attributes();

        return $table
            ->modifyQueryUsing(function ($query) {
                $query->with(['attribute', 'option']);
            })
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('attribute.name'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Option'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form(fn (Form $form) => $this->form($form))
                    ->action(function (Tables\Actions\Action $action, array $data) use ($attributes) {
                        if (!$attribute = $attributes->firstWhere('id', $data['attribute_id'])) {
                            return null;
                        }

                        if ($attribute->hasTextOption()) {
                            $this->getOwnerRecord()->attributes()->create($data);

                            return $action->success();
                        }

                        $data = collect($data['value'])->mapWithKeys(fn ($option) => [$option => ['product_id' => $this->getOwnerRecord()->id]])->toArray();

                        $attribute->variations()->wherePivot('product_id', $this->getOwnerRecord()->id)->sync($data);

                        return $action->success();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (Tables\Actions\EditAction $action, Model $record, array $data) {
                        if ($record->attribute->hasTextOption()) {
                            return $record->update($data);
                        }

                        $data = collect($data['value'])->mapWithKeys(fn ($option) => [$option => ['product_id' => $record->product_id]])->toArray();

                        $record->attribute->variations()->wherePivot('product_id', $this->getOwnerRecord()->id)->sync($data);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
