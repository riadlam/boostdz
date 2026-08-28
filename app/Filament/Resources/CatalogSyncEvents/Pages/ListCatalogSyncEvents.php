<?php

namespace App\Filament\Resources\CatalogSyncEvents\Pages;

use App\Filament\Resources\CatalogSyncEvents\CatalogSyncEventResource;
use Filament\Resources\Pages\ListRecords;

class ListCatalogSyncEvents extends ListRecords
{
    protected static string $resource = CatalogSyncEventResource::class;
}
