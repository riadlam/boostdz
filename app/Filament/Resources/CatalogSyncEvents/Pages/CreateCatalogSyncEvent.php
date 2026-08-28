<?php

namespace App\Filament\Resources\CatalogSyncEvents\Pages;

use App\Filament\Resources\CatalogSyncEvents\CatalogSyncEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCatalogSyncEvent extends CreateRecord
{
    protected static string $resource = CatalogSyncEventResource::class;
}
