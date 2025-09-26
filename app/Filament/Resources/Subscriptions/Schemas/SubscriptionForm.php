<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->required(),
                Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->required(),
                DateTimePicker::make('start_at')
                    ->required()
                    ->timezone('Asia/Makassar'),

                DateTimePicker::make('end_at')
                    ->timezone('Asia/Makassar'),
                Select::make('status')
                    ->options([
            'active' => 'Active',
            'expired' => 'Expired',
            'pending' => 'Pending',
            'cancelled' => 'Cancelled',
        ])
                    ->default('pending')
                    ->required(),
                Toggle::make('is_trial')
                    ->required(),
                DateTimePicker::make('trial_started_at')
                    ->timezone('Asia/Makassar'),

                DateTimePicker::make('trial_end_at')
                    ->timezone('Asia/Makassar'),
                Select::make('current_invoice_id')
                    ->relationship('currentInvoice', 'id')
                    ->default(null),
            ]);
    }
}
