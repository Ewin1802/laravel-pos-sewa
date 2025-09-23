<?php

namespace App\Filament\Resources\PaymentConfirmations;

use App\Filament\Resources\PaymentConfirmations\Pages\CreatePaymentConfirmation;
use App\Filament\Resources\PaymentConfirmations\Pages\EditPaymentConfirmation;
use App\Filament\Resources\PaymentConfirmations\Pages\ListPaymentConfirmations;
use App\Filament\Resources\PaymentConfirmations\Pages\ViewPaymentConfirmation;
use App\Filament\Resources\PaymentConfirmations\Schemas\PaymentConfirmationForm;
use App\Filament\Resources\PaymentConfirmations\Tables\PaymentConfirmationsTable;
use App\Models\PaymentConfirmation;
use App\Policies\PaymentConfirmationPolicy;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use App\Enums\NavigationGroup;
use UnitEnum;

class PaymentConfirmationResource extends Resource
{
    protected static ?string $model = PaymentConfirmation::class;

    protected static ?string $policy = PaymentConfirmationPolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Payment Confirmations';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Payments;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PaymentConfirmationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentConfirmationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentConfirmations::route('/'),
            'create' => CreatePaymentConfirmation::route('/create'),
            'view' => ViewPaymentConfirmation::route('/{record}'),
            'edit' => EditPaymentConfirmation::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', PaymentConfirmation::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', PaymentConfirmation::class);
    }

    public static function canEdit($record): bool
    {
        return Gate::allows('update', $record);
    }

    public static function canDelete($record): bool
    {
        return Gate::allows('delete', $record);
    }
}
