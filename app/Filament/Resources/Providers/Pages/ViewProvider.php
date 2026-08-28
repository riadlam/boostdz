<?php

namespace App\Filament\Resources\Providers\Pages;

use App\Filament\Resources\Providers\ProviderResource;
use App\Services\BuzzerPanel\BuzzerPanelClient;
use App\Services\Catalog\CatalogSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProvider extends ViewRecord
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncCatalog')
                ->label('Sync catalog')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function (): void {
                    $log = app(CatalogSyncService::class)->sync($this->record);

                    Notification::make()
                        ->title('Catalog sync completed')
                        ->body("Status: {$log->status}")
                        ->success()
                        ->send();
                }),
            Action::make('syncBalance')
                ->label('Sync balance')
                ->icon('heroicon-o-banknotes')
                ->action(function (): void {
                    $client = BuzzerPanelClient::fromProvider($this->record);
                    $profile = $client->profile();

                    $this->record->update([
                        'cached_balance' => $profile['balance'] ?? $profile['funds'] ?? null,
                        'balance_synced_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Balance synced')
                        ->success()
                        ->send();
                }),
        ];
    }
}
