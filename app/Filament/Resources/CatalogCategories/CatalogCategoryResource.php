<?php

namespace App\Filament\Resources\CatalogCategories;

use App\Filament\Resources\CatalogCategories\Pages\EditCatalogCategory;
use App\Filament\Resources\CatalogCategories\Pages\ListCatalogCategories;
use App\Filament\Resources\CatalogCategories\Schemas\CatalogCategoryForm;
use App\Filament\Resources\CatalogCategories\Tables\CatalogCategoriesTable;
use App\Models\CatalogCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CatalogCategoryResource extends Resource
{
    protected static ?string $model = CatalogCategory::class;

    protected static ?string $navigationLabel = 'Categories';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CatalogCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CatalogCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogCategories::route('/'),
            'edit' => EditCatalogCategory::route('/{record}/edit'),
        ];
    }
}
