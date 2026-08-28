<?php

namespace App\Filament\Resources\OrderStatusLogs\Pages;

use App\Filament\Resources\OrderStatusLogs\OrderStatusLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderStatusLogs extends ListRecords
{
    protected static string $resource = OrderStatusLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
