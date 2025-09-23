<?php

namespace App\Filament\Resources\LicenseTokens\Pages;

use App\Filament\Resources\LicenseTokens\LicenseTokenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLicenseTokens extends ListRecords
{
    protected static string $resource = LicenseTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
