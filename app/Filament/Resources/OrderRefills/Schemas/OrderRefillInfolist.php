<?php

namespace App\Filament\Resources\OrderRefills\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderRefillInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Refill')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('order.id')->label('Order ID'),
                        TextEntry::make('order.user.email')->label('User'),
                        TextEntry::make('provider_refill_id')->label('Provider refill ID'),
                        TextEntry::make('requested_at')->dateTime(),
                        TextEntry::make('completed_at')->dateTime(),
                    ]),
            ]);
    }
}
