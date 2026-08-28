<?php

namespace App\Filament\Resources\ProviderServices\Pages;

use App\Filament\Resources\ProviderServices\ProviderServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProviderServices extends ListRecords
{
    protected static string $resource = ProviderServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
