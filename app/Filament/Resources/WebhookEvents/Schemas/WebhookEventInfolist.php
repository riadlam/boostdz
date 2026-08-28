<?php

namespace App\Filament\Resources\WebhookEvents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WebhookEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Webhook event')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('event')->badge(),
                        TextEntry::make('provider.name')->label('Provider'),
                        TextEntry::make('provider_order_id'),
                        TextEntry::make('signature_valid')->badge(),
                        TextEntry::make('processing_error')->columnSpanFull(),
                        TextEntry::make('payload')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))->columnSpanFull(),
                        TextEntry::make('processed_at')->dateTime(),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
            ]);
    }
}
