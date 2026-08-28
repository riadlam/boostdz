<?php

namespace App\Filament\Resources\PaymentSubmissions\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Submission')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('user.email')->label('User'),
                        TextEntry::make('service.name')->label('Service'),
                        TextEntry::make('amount_dzd')->label('Amount')->suffix(' DA'),
                        TextEntry::make('payment_method')->badge(),
                        TextEntry::make('payer_reference')->label('Reference'),
                        TextEntry::make('quantity'),
                        TextEntry::make('link')->columnSpanFull()->copyable(),
                        TextEntry::make('admin_note')->columnSpanFull(),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('reviewed_at')->dateTime(),
                    ]),
                Section::make('Receipt')
                    ->schema([
                        ImageEntry::make('proof')
                            ->label('Proof of payment')
                            ->getStateUsing(fn ($record) => $record->proofPublicUrl())
                            ->visible(fn ($record) => filled($record->proof_path)),
                    ]),
            ]);
    }
}
