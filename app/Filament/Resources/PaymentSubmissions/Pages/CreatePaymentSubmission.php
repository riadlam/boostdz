<?php

namespace App\Filament\Resources\PaymentSubmissions\Pages;

use App\Filament\Resources\PaymentSubmissions\PaymentSubmissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentSubmission extends CreateRecord
{
    protected static string $resource = PaymentSubmissionResource::class;
}
