<?php

namespace App\Filament\Resources\CatalogSyncEvents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CatalogSyncEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('event_type')->badge(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('provider.name')->label('Provider'),
                        TextEntry::make('external_id'),
                        TextEntry::make('old_value')->columnSpanFull(),
                        TextEntry::make('new_value')->columnSpanFull(),
                        TextEntry::make('notified_at')->dateTime(),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
            ]);
    }
}
