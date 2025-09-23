<?php

namespace App\Filament\Resources\LicenseTokens\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class LicenseTokenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->required(),
                Select::make('device_id')
                    ->relationship('device', 'id')
                    ->required(),
                Textarea::make('token')
                    ->required()
                    ->columnSpanFull(),
                DateTimePicker::make('expires_at')
                    ->required(),
                DateTimePicker::make('revoked_at'),
                DateTimePicker::make('last_refreshed_at'),
            ]);
    }
}
