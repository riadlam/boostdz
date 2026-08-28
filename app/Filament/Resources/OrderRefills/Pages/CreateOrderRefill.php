<?php

namespace App\Filament\Resources\OrderRefills\Pages;

use App\Filament\Resources\OrderRefills\OrderRefillResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderRefill extends CreateRecord
{
    protected static string $resource = OrderRefillResource::class;
}
