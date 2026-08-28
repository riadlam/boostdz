<?php

namespace App\Filament\Resources\PaymentSubmissions\Pages;

use App\Enums\PaymentSubmissionStatus;
use App\Filament\Resources\PaymentSubmissions\PaymentSubmissionResource;
use App\Services\Payments\PaymentSubmissionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentSubmission extends ViewRecord
{
    protected static string $resource = PaymentSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->isPending())
                ->requiresConfirmation()
                ->action(function (): void {
                    app(PaymentSubmissionService::class)->accept($this->record);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Payment approved')
                        ->success()
                        ->send();
                }),
            Action::make('decline')
                ->label('Decline')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->isPending())
                ->form([
                    Textarea::make('admin_note')->label('Reason')->rows(3),
                ])
                ->action(function (array $data): void {
                    app(PaymentSubmissionService::class)->decline($this->record, $data['admin_note'] ?? null);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Payment declined')
                        ->warning()
                        ->send();
                }),
        ];
    }
}
