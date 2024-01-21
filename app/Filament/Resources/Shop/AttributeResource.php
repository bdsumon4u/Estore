<?php

namespace App\Filament\Resources\Shop;

use App\Filament\Resources\Shop\AttributeResource\Pages;
use App\Filament\Resources\Shop\AttributeResource\RelationManagers;
use App\Filament\Resources\Support\Traits\BelongsToOwner;
use App\Models\Shop\Attribute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class AttributeResource extends Resource
{
    use BelongsToOwner;

    protected static ?string $model = Attribute::class;

    protected static ?string $navigationGroup = 'Shop';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationParentItem = 'Products';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\TextInput::make('name')
                            ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state)))
                                ->required(),
                            Forms\Components\TextInput::make('slug')
                            ->required(),
                            Forms\Components\Textarea::make('description')
                            ->columnSpan(2),
                            Forms\Components\CheckboxList::make('is')
                                ->label('')
                                ->options([
                                    'searchable' => 'Searchable',
                                    'filterable' => 'Filterable',
                                ])
                                ->descriptions([
                                    'searchable' => 'You can use this attribute to search and filter product.',
                                    'filterable' => 'You can use this attribute as a filter on your front store.',
                                ])
                                ->formatStateUsing(function (string $operation) use (&$form) {
                                    $state = [];

                                    foreach (['searchable', 'filterable'] as $is) {
                                        if ($form->getRecord()?->{'is_' . $is}) {
                                            $state[] = $is;
                                        }
                                    }

                                    return $state;
                                })
                                ->columns(2)
                                ->columnSpan(2),
                        ])
                        ->columns(2),
                ])
                    ->columnSpan(['lg' => 2]),
                Forms\Components\Group::make([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Select::make('type')
                            ->options(Attribute::typesFields())
                                ->required()
                                ->native(false),
                            Forms\Components\Toggle::make('is_enabled')
                            ->default(true)
                                ->label('Enabled'),
                        ]),
                ]),
            ])
            ->columns(['lg' => 3]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->sortable(),
                Tables\Columns\TextColumn::make('type')
                ->formatStateUsing(function (Attribute $record) {
                    return $record->getTypeFormattedAttribute();
                })
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_enabled')
                ->label('Enabled')
                ->sortable(),
                Tables\Columns\ToggleColumn::make('is_searchable')
                ->label('Searchable')
                ->sortable(),
                Tables\Columns\ToggleColumn::make('is_filterable')
                ->label('Filterable')
                ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributes::route('/'),
            'create' => Pages\CreateAttribute::route('/create'),
            'edit' => Pages\EditAttribute::route('/{record}/edit'),
        ];
    }
}
