<?php

namespace App\Filament\Resources\CatalogPlatforms\Pages;

use App\Filament\Resources\CatalogPlatforms\CatalogPlatformResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogPlatforms extends ListRecords
{
    protected static string $resource = CatalogPlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
