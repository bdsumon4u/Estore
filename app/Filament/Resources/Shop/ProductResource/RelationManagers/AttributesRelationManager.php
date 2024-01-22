<?php

namespace App\Filament\Resources\Shop\ProductResource\RelationManagers;

use App\Models\Shop\Attribute;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class AttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'attributes';

    protected function attributes(): Collection
    {
        $existing = $this->getOwnerRecord()->attributes()->get([])->map->pivot->map->option_id->filter();

        return Attribute::with(['options' /* => fn ($query) => $query->whereNotIn('id', $existing) */])
            ->where('owner_id', Filament::getTenant()->owner_id)->get();
    }

    public function form(Form $form): Form
    {
        $attributes = $this->attributes();

        return $form
            ->schema([
                Forms\Components\Select::make('attribute_id')
                    ->label('')
                    ->placeholder('Select an attribute...')
                    ->options($attributes->pluck('name', 'id')->toArray())
                    ->live()
                    ->searchable()
                    ->afterStateUpdated(fn (Forms\Components\Select $component) => $component
                        ->getContainer()
                        ->getComponent('dynamicFields')
                        ->getChildComponentContainer()
                        ->fill()
                    ),

                Forms\Components\Group::make(function (Forms\Get $get) use ($attributes, $form): array {
                    if (! $attribute = $attributes->firstWhere('id', $get('attribute_id'))) {
                        return [];
                    }

                    if ($attribute->hasTextOption()) {
                        if ($attribute->type === 'datepicker') {
                            return [
                                Forms\Components\DatePicker::make('value')
                                    ->placeholder('Select a date...')
                                    ->label('')
                                    ->native(false),
                            ];
                        }

                        return [
                            Forms\Components\TextInput::make('value')
                                ->placeholder('Enter a value...')
                                ->formatStateUsing(fn (Model $record) => dd($record, $form->getRecord()))
                                ->label('')
                                ->numeric($attribute->type === 'number'),
                        ];
                    }

                    if ($attribute->hasMultipleOptions()) {
                        return [
                            Forms\Components\CheckboxList::make('value')
                                ->options($attribute->options->pluck('value', 'id')->toArray())
                                ->formatStateUsing(fn () => $form
                                    ->getRecord()
                                    ->variations()
                                    ->wherePivot('product_id', $this->getOwnerRecord()->id)
                                    ->get(['option_id'])
                                    ->pluck('option_id')
                                    ->toArray()
                                )
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
            ->modifyQueryUsing(function (Builder $query) {
                // $query->with('variations');
            })
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('pivot.value')
                    ->label('Option'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                /*
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model) use ($attributes) {
                        $value = is_array($data['value']) ? $data['value'] : [$data['value']];

                        if (! $attribute = $attributes->firstWhere('id', $data['attribute_id'])) {
                            return null;
                        }

                        foreach ($value as $option_id) {
                            $this->getOwnerRecord()->attributes()->syncWithoutDetaching($data['attribute_id'], [
                                $attribute->hasTextOption() ? 'value' : 'option_id' => $option_id,
                            ]);
                        }

                        return $this->getOwnerRecord()->attributes()->firstWhere('attribute_id', $data['attribute_id']);
                    }),
                    */
                Tables\Actions\Action::make('Attach')
                    ->form(fn (Form $form) => $this->form($form))
                    ->modalHeading(fn () => 'Attach attribute')
                    ->modalWidth('xl')
                    ->action(function (Tables\Actions\Action $action, array $data) use ($attributes) {
                        $value = is_array($data['value']) ? $data['value'] : [$data['value']];

                        if (!$attribute = $attributes->firstWhere('id', $data['attribute_id'])) {
                            return null;
                        }

                        foreach ($value as $option) {
                            $this->getOwnerRecord()->attributes()->attach($data['attribute_id'], [
                                ($attribute->hasTextOption() ? 'option_value' : 'option_id') => $option,
                            ]);
                        }

                        $action->success();
                    })
                    ->successNotificationTitle('Attached'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
