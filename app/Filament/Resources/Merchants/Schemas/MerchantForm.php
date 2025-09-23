<?php

namespace App\Filament\Resources\Merchants\Schemas;

use App\Models\Merchant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MerchantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('contact_name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),

                TextInput::make('whatsapp')
                    ->tel()
                    ->maxLength(20),

                Select::make('status')
                    ->options([
                        Merchant::STATUS_ACTIVE => 'Active',
                        Merchant::STATUS_SUSPENDED => 'Suspended',
                    ])
                    ->required()
                    ->default(Merchant::STATUS_ACTIVE),

                Toggle::make('trial_used')
                    ->label('Trial Used')
                    ->helperText('Whether this merchant has already used their trial period'),
            ])
            ->columns(2);
    }
}
