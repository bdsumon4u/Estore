<?php

namespace App\Filament\Resources\Shop;

use App\Filament\Resources\Shop\CategoryResource\Pages;
use App\Filament\Resources\Shop\CategoryResource\RelationManagers;
use App\Filament\Resources\Support\Related;
use App\Filament\Resources\Support\Traits\BelongsToOwner;
use App\Models\Shop\Category;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    use BelongsToOwner;

    protected static ?string $model = Category::class;

    protected static ?string $slug = 'shop/categories';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationGroup = 'Shop';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationParentItem = 'Products';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                            $rule->where('owner_id', Filament::getTenant()->owner_id);
                                        })
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                            return $operation === 'create' ? $set('slug', Str::slug($state)) : null;
                                        }),

                                    Forms\Components\TextInput::make('slug')
                                        ->dehydrated()
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                            $rule->where('owner_id', Filament::getTenant()->owner_id);
                                        }),
                                ]),

                            Forms\Components\Select::make('parent_id')
                                ->label('Parent')
                                ->relationship('parent')
                                ->options(static function (Select $component): ?array {
                                    $query = static::getEloquentQuery();

                                    if ($record = $component->getRecord()) {
                                        $query->where('id', '!=', $record->id);
                                    }

                                    return $query
                                        ->get(['id', 'name_path'])
                                        ->mapWithKeys(static fn (Category $record) => [
                                            $record->id => $record->name_path,
                                        ])
                                        ->toArray();
                                })
                                ->searchable()
                                ->placeholder('Select parent category')
                                ->preload(), // for search to work

                            Forms\Components\Toggle::make('is_enabled')
                                ->label('Visible to customers.')
                                ->default(true),

                            Forms\Components\MarkdownEditor::make('description')
                                ->label('Description'),
                        ]),
                ])
                    ->columnSpan(['lg' => 2]),
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Thumbnail')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('thumbnail')
                                ->hiddenLabel()
                                ->image(),
                        ]),
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Placeholder::make('created_at')
                                ->label('Created at')
                                ->content(fn (Category $record): ?string => $record->created_at?->diffForHumans()),

                            Forms\Components\Placeholder::make('updated_at')
                                ->label('Last modified at')
                                ->content(fn (Category $record): ?string => $record->updated_at?->diffForHumans()),
                        ])
                        ->hiddenOn('create'),
                ]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->label('')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_path')
                    ->label('Parent')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_enabled')
                    ->label('Visibility')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->groupedBulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('Make visible')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($records) => $records->each->update(['is_enabled' => true])),
                Tables\Actions\BulkAction::make('Disable')
                    ->icon('heroicon-o-x-circle')
                    ->action(fn ($records) => $records->each->update(['is_enabled' => false])),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ...Related::belongsToMany(static::getModel(), [
                RelationManagers\ProductsRelationManager::class,
            ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = Category::tree();

        if (
            static::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            static::scopeEloquentQueryToTenant($query, $tenant);
        }

        return $query;
    }
}
