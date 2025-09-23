<?php

namespace App\Filament\Resources\Merchants\Tables;

use App\Models\Merchant;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MerchantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->searchable(),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => Merchant::STATUS_ACTIVE,
                        'danger' => Merchant::STATUS_SUSPENDED,
                    ]),

                IconColumn::make('trial_used')
                    ->boolean(),

                TextColumn::make('devices_count')
                    ->counts('devices')
                    ->label('Devices'),

                TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label('Subscriptions'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Merchant::STATUS_ACTIVE => 'Active',
                        Merchant::STATUS_SUSPENDED => 'Suspended',
                    ]),

                TernaryFilter::make('trial_used')
                    ->label('Trial Used')
                    ->placeholder('All merchants')
                    ->trueLabel('Trial used')
                    ->falseLabel('Trial available'),

                Filter::make('has_active_subscription')
                    ->query(fn(Builder $query): Builder => $query->whereHas('subscriptions', fn(Builder $query) => $query->where('status', 'active')))
                    ->label('Has Active Subscription'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Merchant $record): bool => $record->status === Merchant::STATUS_ACTIVE)
                    ->action(function (Merchant $record): void {
                        $record->update(['status' => Merchant::STATUS_SUSPENDED]);

                        Notification::make()
                            ->title('Merchant suspended successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('activate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Merchant $record): bool => $record->status === Merchant::STATUS_SUSPENDED)
                    ->action(function (Merchant $record): void {
                        $record->update(['status' => Merchant::STATUS_ACTIVE]);

                        Notification::make()
                            ->title('Merchant activated successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('suspend')
                        ->icon('heroicon-o-pause-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn(Merchant $record) => $record->update(['status' => Merchant::STATUS_SUSPENDED]));

                            Notification::make()
                                ->title('Merchants suspended successfully')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
