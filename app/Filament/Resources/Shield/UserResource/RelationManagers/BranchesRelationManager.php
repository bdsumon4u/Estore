<?php

namespace App\Filament\Resources\Shield\UserResource\RelationManagers;

use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->listWithLineBreaks()
                    ->bulleted(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where(
                        'owner_id', Filament::getTenant()->owner_id
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->hidden(fn () => $this->getOwnerRecord()->is(Filament::auth()->user()) || $this->getOwnerRecord()->id == Filament::getTenant()->owner_id)
                    ->action(fn (Model $record) => $record->users()->detach(Filament::auth()->user()))
                    ->successNotificationTitle('Removed')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-x-mark')
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('remove')
                        ->hidden(fn () => $this->getOwnerRecord()->is(Filament::auth()->user()) || $this->getOwnerRecord()->id == Filament::getTenant()->owner_id)
                        ->action(fn (Collection $records) => $this->getOwnerRecord()->branches()->detach($records))
                        ->successNotificationTitle('Removed')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-x-mark')
                        ->label('Remove selected')
                        ->color('danger'),
                ]),
            ]);
    }
}
