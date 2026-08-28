<?php

namespace App\Filament\Resources\ProviderSyncLogs\Pages;

use App\Filament\Resources\ProviderSyncLogs\ProviderSyncLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProviderSyncLogs extends ListRecords
{
    protected static string $resource = ProviderSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
