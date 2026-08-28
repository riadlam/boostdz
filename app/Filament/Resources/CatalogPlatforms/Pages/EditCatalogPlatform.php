<?php

namespace App\Filament\Resources\CatalogPlatforms\Pages;

use App\Filament\Resources\CatalogPlatforms\CatalogPlatformResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCatalogPlatform extends EditRecord
{
    protected static string $resource = CatalogPlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
