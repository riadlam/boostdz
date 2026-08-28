<?php

namespace App\Filament\Resources\ProviderSyncLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProviderSyncLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sync log')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('provider.name')->label('Provider'),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('records_synced'),
                        TextEntry::make('duration_ms'),
                        TextEntry::make('message')->columnSpanFull(),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
            ]);
    }
}
