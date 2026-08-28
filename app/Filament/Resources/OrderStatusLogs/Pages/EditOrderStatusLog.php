<?php

namespace App\Filament\Resources\OrderStatusLogs\Pages;

use App\Filament\Resources\OrderStatusLogs\OrderStatusLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOrderStatusLog extends EditRecord
{
    protected static string $resource = OrderStatusLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
