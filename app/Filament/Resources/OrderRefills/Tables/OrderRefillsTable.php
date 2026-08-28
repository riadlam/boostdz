<?php

namespace App\Filament\Resources\OrderRefills\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderRefillsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('order.id')->label('Order')->sortable(),
                TextColumn::make('order.user.email')->label('User'),
                TextColumn::make('status')->badge(),
                TextColumn::make('provider_refill_id')->label('Provider refill ID'),
                TextColumn::make('requested_at')->dateTime('d M Y H:i'),
                TextColumn::make('completed_at')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
