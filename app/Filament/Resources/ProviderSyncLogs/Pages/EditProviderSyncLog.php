<?php

namespace App\Filament\Resources\ProviderSyncLogs\Pages;

use App\Filament\Resources\ProviderSyncLogs\ProviderSyncLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProviderSyncLog extends EditRecord
{
    protected static string $resource = ProviderSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
