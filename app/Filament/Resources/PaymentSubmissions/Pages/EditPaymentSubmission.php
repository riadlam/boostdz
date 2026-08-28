<?php

namespace App\Filament\Resources\PaymentSubmissions\Pages;

use App\Filament\Resources\PaymentSubmissions\PaymentSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentSubmission extends EditRecord
{
    protected static string $resource = PaymentSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
