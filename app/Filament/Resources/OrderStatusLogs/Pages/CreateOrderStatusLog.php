<?php

namespace App\Filament\Resources\OrderStatusLogs\Pages;

use App\Filament\Resources\OrderStatusLogs\OrderStatusLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderStatusLog extends CreateRecord
{
    protected static string $resource = OrderStatusLogResource::class;
}
