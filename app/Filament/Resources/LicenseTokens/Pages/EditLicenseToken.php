<?php

namespace App\Filament\Resources\LicenseTokens\Pages;

use App\Filament\Resources\LicenseTokens\LicenseTokenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLicenseToken extends EditRecord
{
    protected static string $resource = LicenseTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
