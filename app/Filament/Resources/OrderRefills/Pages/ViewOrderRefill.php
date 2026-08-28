<?php

namespace App\Filament\Resources\OrderRefills\Pages;

use App\Filament\Resources\OrderRefills\OrderRefillResource;
use App\Services\Orders\RefillService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrderRefill extends ViewRecord
{
    protected static string $resource = OrderRefillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncStatus')
                ->label('Sync status')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn () => filled($this->record->provider_refill_id))
                ->action(function (): void {
                    app(RefillService::class)->syncStatus($this->record);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Refill status synced')
                        ->success()
                        ->send();
                }),
        ];
    }
}
