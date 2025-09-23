<?php

namespace App\Filament\Resources\LicenseTokens;

use App\Enums\NavigationGroup;
use App\Filament\Resources\LicenseTokens\Pages\CreateLicenseToken;
use App\Filament\Resources\LicenseTokens\Pages\EditLicenseToken;
use App\Filament\Resources\LicenseTokens\Pages\ListLicenseTokens;
use App\Filament\Resources\LicenseTokens\Schemas\LicenseTokenForm;
use App\Filament\Resources\LicenseTokens\Tables\LicenseTokensTable;
use App\Models\LicenseToken;
use App\Policies\LicenseTokenPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class LicenseTokenResource extends Resource
{
    protected static ?string $model = LicenseToken::class;

    protected static ?string $policy = LicenseTokenPolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'License Tokens';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Licenses;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return LicenseTokenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicenseTokensTable::configure($table);
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
            'index' => ListLicenseTokens::route('/'),
            'create' => CreateLicenseToken::route('/create'),
            'edit' => EditLicenseToken::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', LicenseToken::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', LicenseToken::class);
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
