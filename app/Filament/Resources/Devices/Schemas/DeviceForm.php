<?php

namespace App\Filament\Resources\Devices\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->required(),
                TextInput::make('device_uid')
                    ->required(),
                TextInput::make('label')
                    ->default(null),
                DateTimePicker::make('last_seen_at'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
