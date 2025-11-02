<?php

namespace App\Filament\Resources\LicenseTokens\Pages;

use App\Filament\Resources\LicenseTokens\LicenseTokenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLicenseTokens extends ListRecords
{
    protected static string $resource = LicenseTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['merchant', 'device', 'subscription.plan']);
    }
}
