<?php

namespace App\Filament\Resources\OrderRefills\Pages;

use App\Filament\Resources\OrderRefills\OrderRefillResource;
use Filament\Resources\Pages\ListRecords;

class ListOrderRefills extends ListRecords
{
    protected static string $resource = OrderRefillResource::class;
}
