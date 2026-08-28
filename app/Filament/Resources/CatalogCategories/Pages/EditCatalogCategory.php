<?php

namespace App\Filament\Resources\CatalogCategories\Pages;

use App\Filament\Resources\CatalogCategories\CatalogCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCatalogCategory extends EditRecord
{
    protected static string $resource = CatalogCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
