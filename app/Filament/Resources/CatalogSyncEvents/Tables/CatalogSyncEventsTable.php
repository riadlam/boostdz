<?php

namespace App\Filament\Resources\CatalogSyncEvents\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CatalogSyncEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('event_type')->badge(),
                TextColumn::make('provider.name')->label('Provider'),
                TextColumn::make('external_id'),
                TextColumn::make('status')->badge(),
                TextColumn::make('old_value')->limit(25),
                TextColumn::make('new_value')->limit(25),
                TextColumn::make('created_at')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
