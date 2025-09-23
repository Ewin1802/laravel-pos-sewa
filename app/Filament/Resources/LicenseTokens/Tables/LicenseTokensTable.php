<?php

namespace App\Filament\Resources\LicenseTokens\Tables;

use App\Models\LicenseToken;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class LicenseTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('merchant.business_name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('device.device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscription.plan.name')
                    ->label('Plan')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (LicenseToken $record) {
                        if ($record->revoked_at) {
                            return 'revoked';
                        }
                        if ($record->expires_at && $record->expires_at->isPast()) {
                            return 'expired';
                        }
                        return 'active';
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'revoked',
                        'warning' => 'expired',
                    ]),
                TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('revoked_at')
                    ->label('Revoked At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_refreshed_at')
                    ->label('Last Refreshed')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'revoked' => 'Revoked',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $status = $data['value'] ?? null;

                        if ($status === 'active') {
                            return $query->whereNull('revoked_at')
                                ->where(function ($q) {
                                    $q->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', now());
                                });
                        }

                        if ($status === 'expired') {
                            return $query->whereNull('revoked_at')
                                ->where('expires_at', '<=', now());
                        }

                        if ($status === 'revoked') {
                            return $query->whereNotNull('revoked_at');
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->action(function (LicenseToken $record) {
                        $record->update([
                            'revoked_at' => now(),
                        ]);
                    })
                    ->visible(
                        fn(LicenseToken $record) =>
                        !$record->revoked_at && Gate::allows('revoke', $record)
                    )
                    ->requiresConfirmation(),
                Action::make('reissue')
                    ->label('Reissue')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (LicenseToken $record) {
                        // Create new token and revoke old one
                        $newToken = $record->replicate();
                        $newToken->token = Str::random(64);
                        $newToken->expires_at = now()->addDays(30);
                        $newToken->revoked_at = null;
                        $newToken->last_refreshed_at = now();
                        $newToken->save();

                        // Revoke old token
                        $record->update(['revoked_at' => now()]);
                    })
                    ->visible(
                        fn(LicenseToken $record) =>
                        Gate::allows('reissue', $record)
                    )
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
