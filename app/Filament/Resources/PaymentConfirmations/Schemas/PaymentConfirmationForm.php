<?php

namespace App\Filament\Resources\PaymentConfirmations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Auth;

class PaymentConfirmationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_id')
                    ->relationship('invoice', 'id')
                    ->required(),
                TextInput::make('submitted_by')
                    ->default(null),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('bank_name')
                    ->default(null),
                TextInput::make('reference_no')
                    ->default(null),
                // TextInput::make('evidence_path')
                //     ->default(null),
                FileUpload::make('evidence_path')
                    ->label('Payment Evidence')
                    ->image()
                    ->disk('public')
                    ->directory('evidence')
                    ->required()
                    ->enableDownload()
                    ->imagePreviewHeight('200'),
                Select::make('status')
                    ->options(['submitted' => 'Submitted', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                    ->default('submitted')
                    ->required(),
                // TextInput::make('reviewed_by')
                //     ->numeric()
                //     ->default(null),
                // reviewed_by otomatis diisi admin login, disembunyikan
                TextInput::make('reviewed_by')
                ->default(fn () => Auth::id())
                ->dehydrated(false)
                ->hidden(),

                // reviewed_at otomatis set sekarang saat approve/reject, disembunyikan
                DateTimePicker::make('reviewed_at'),
                // DateTimePicker::make('reviewed_at')
                //     ->default(now())
                //     ->dehydrated(false)
                //     ->hidden(),

                Textarea::make('admin_note')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
