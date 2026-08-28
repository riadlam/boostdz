<?php

namespace App\Filament\Resources\ExchangeRates\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExchangeRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fetched_at', 'desc')
            ->columns([
                TextColumn::make('from_currency')->label('From'),
                TextColumn::make('to_currency')->label('To'),
                TextColumn::make('rate')->numeric(decimalPlaces: 6),
                TextColumn::make('source'),
                TextColumn::make('fetched_at')->dateTime('d M Y H:i'),
            ]);
    }
}
