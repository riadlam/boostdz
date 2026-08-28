<?php

namespace App\Filament\Resources\CatalogCategories\Pages;

use App\Filament\Resources\CatalogCategories\CatalogCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogCategories extends ListRecords
{
    protected static string $resource = CatalogCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
