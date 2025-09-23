<?php

namespace App\Filament\Resources\PaymentConfirmations\Pages;

use App\Filament\Resources\PaymentConfirmations\PaymentConfirmationResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentConfirmation extends CreateRecord
{
    protected static string $resource = PaymentConfirmationResource::class;
}
