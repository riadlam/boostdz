<?php

namespace App\Filament\Resources\OrderStatusLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderStatusLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Status log')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('order_id')->label('Order ID'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('source'),
                        TextEntry::make('start_count'),
                        TextEntry::make('remains'),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
            ]);
    }
}
