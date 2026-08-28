<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('user.email')->label('User'),
                        TextEntry::make('service.name')->label('Service'),
                        TextEntry::make('quantity'),
                        TextEntry::make('charge_dzd')->label('Charge')->suffix(' DA'),
                        TextEntry::make('link')->columnSpanFull()->copyable(),
                        TextEntry::make('provider_order_id')->label('Provider order ID'),
                        TextEntry::make('error_message')->label('Error')->columnSpanFull()->visible(fn ($record) => filled($record->error_message)),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('completed_at')->dateTime(),
                    ]),
                Section::make('Delivery')
                    ->schema([
                        TextEntry::make('start_count')->label('Start count'),
                        TextEntry::make('remains')->label('Remains'),
                        TextEntry::make('last_status_check_at')->dateTime(),
                    ])
                    ->columns(3),
            ]);
    }
}
