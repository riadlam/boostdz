<?php

namespace App\Filament\Resources\CatalogCategories\Pages;

use App\Filament\Resources\CatalogCategories\CatalogCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCatalogCategory extends CreateRecord
{
    protected static string $resource = CatalogCategoryResource::class;
}
