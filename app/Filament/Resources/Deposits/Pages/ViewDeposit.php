<?php

namespace App\Filament\Resources\Deposits\Pages;

use App\Enums\DepositStatus;
use App\Filament\Resources\Deposits\DepositResource;
use App\Services\Deposits\DepositService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDeposit extends ViewRecord
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === DepositStatus::Pending)
                ->form([
                    Textarea::make('admin_note')->label('Note')->rows(2),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    app(DepositService::class)->approve($this->record, auth()->user(), $data['admin_note'] ?? null);
                    $this->record->refresh();

                    Notification::make()->title('Deposit approved')->success()->send();
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === DepositStatus::Pending)
                ->form([
                    Textarea::make('admin_note')->label('Reason')->rows(3)->required(),
                ])
                ->action(function (array $data): void {
                    app(DepositService::class)->reject($this->record, auth()->user(), $data['admin_note']);
                    $this->record->refresh();

                    Notification::make()->title('Deposit rejected')->warning()->send();
                }),
        ];
    }
}
