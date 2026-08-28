<?php

namespace App\Filament\Resources\CatalogPlatforms;

use App\Filament\Resources\CatalogPlatforms\Pages\EditCatalogPlatform;
use App\Filament\Resources\CatalogPlatforms\Pages\ListCatalogPlatforms;
use App\Filament\Resources\CatalogPlatforms\Schemas\CatalogPlatformForm;
use App\Filament\Resources\CatalogPlatforms\Tables\CatalogPlatformsTable;
use App\Models\CatalogPlatform;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CatalogPlatformResource extends Resource
{
    protected static ?string $model = CatalogPlatform::class;

    protected static ?string $navigationLabel = 'Platforms';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CatalogPlatformForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CatalogPlatformsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogPlatforms::route('/'),
            'edit' => EditCatalogPlatform::route('/{record}/edit'),
        ];
    }
}
