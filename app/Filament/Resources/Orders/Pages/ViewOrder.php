<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\Orders\OrderService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncStatus')
                ->label('Sync from provider')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function (): void {
                    $order = $this->record;
                    app(OrderService::class)->syncStatus($order, force: true);
                    $this->refreshFormData(['status', 'start_count', 'remains', 'last_status_check_at', 'error_message']);

                    Notification::make()
                        ->title('Order status synced')
                        ->success()
                        ->send();
                }),
        ];
    }
}
