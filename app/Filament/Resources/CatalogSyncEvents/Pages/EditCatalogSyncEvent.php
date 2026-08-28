<?php

namespace App\Filament\Resources\CatalogSyncEvents\Pages;

use App\Filament\Resources\CatalogSyncEvents\CatalogSyncEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCatalogSyncEvent extends EditRecord
{
    protected static string $resource = CatalogSyncEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
