<?php

namespace App\Filament\Resources\ProviderServices\Pages;

use App\Filament\Resources\ProviderServices\ProviderServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProviderService extends EditRecord
{
    protected static string $resource = ProviderServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
