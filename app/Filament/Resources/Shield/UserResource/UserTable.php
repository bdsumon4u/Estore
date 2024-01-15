<?php

namespace App\Filament\Resources\Shield\UserResource;

use Filament\Actions\MountableAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait UserTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->listWithLineBreaks()
                    ->bulleted(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->hidden(fn (Model $record) => $record->is(Filament::auth()->user()) || $record->id == Filament::getTenant()->owner_id)
                    ->action(fn (Model $record) => Filament::getTenant()->users()->detach($record))
                    ->successNotificationTitle('Removed')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-x-mark')
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('remove')
                        ->action(function (Tables\Actions\BulkAction $action, Collection $records) {
                            if ($records->contains(fn ($user) => $user->id === Filament::getTenant()->owner_id)) {
                                return static::fail('Nobody can remove branch owner.', $action);
                            }

                            if ($records->contains(fn ($user) => $user->is(Filament::auth()->user()))) {
                                return static::fail('You can\'t remove yourself.', $action);
                            }

                            Filament::getTenant()->users()->detach($records);
                        })
                        ->successNotificationTitle('Removed')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-x-mark')
                        ->label('Remove selected')
                        ->color('danger'),
                ]),
            ]);
    }

    private static function fail(string $message, MountableAction $action): void
    {
        $action->failureNotification(
            Notification::make()
                ->title($message)
                ->body('You can remove yourself from the branch by DELETING the branch.')
                ->danger(),
        )->failure();
    }
}
