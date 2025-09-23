<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->required(),
                Select::make('subscription_id')
                    ->relationship('subscription', 'id')
                    ->default(null),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('IDR'),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'awaiting_confirmation' => 'Awaiting confirmation',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
        ])
                    ->default('pending')
                    ->required(),
                Select::make('payment_method')
                    ->options(['manual_bank' => 'Manual bank', 'manual_qris' => 'Manual qris', 'other' => 'Other'])
                    ->default('manual_bank')
                    ->required(),
                DateTimePicker::make('due_at')
                    ->required(),
                DateTimePicker::make('paid_at'),
                Textarea::make('note')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
