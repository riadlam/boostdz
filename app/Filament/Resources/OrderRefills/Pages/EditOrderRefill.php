<?php

namespace App\Filament\Resources\OrderRefills\Pages;

use App\Filament\Resources\OrderRefills\OrderRefillResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOrderRefill extends EditRecord
{
    protected static string $resource = OrderRefillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
