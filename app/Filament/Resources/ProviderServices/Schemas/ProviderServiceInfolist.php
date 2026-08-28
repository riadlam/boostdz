<?php

namespace App\Filament\Resources\ProviderServices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProviderServiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Provider service')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('external_id'),
                        TextEntry::make('provider.name')->label('Provider'),
                        TextEntry::make('name')->columnSpanFull(),
                        TextEntry::make('type'),
                        TextEntry::make('rate_idr')->label('Rate IDR'),
                        TextEntry::make('min'),
                        TextEntry::make('max'),
                        TextEntry::make('synced_at')->dateTime(),
                    ]),
            ]);
    }
}
