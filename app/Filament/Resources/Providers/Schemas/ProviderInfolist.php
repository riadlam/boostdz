<?php

namespace App\Filament\Resources\Providers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProviderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Provider')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('slug'),
                        TextEntry::make('api_url')->label('API URL')->columnSpanFull(),
                        TextEntry::make('api_key')->label('API key')->formatStateUsing(fn (?string $state) => $state ? str_repeat('•', 12).substr($state, -4) : '—'),
                        TextEntry::make('cached_balance')->label('Balance'),
                        TextEntry::make('currency'),
                        TextEntry::make('is_active')->badge(),
                        TextEntry::make('balance_synced_at')->dateTime(),
                    ]),
            ]);
    }
}
