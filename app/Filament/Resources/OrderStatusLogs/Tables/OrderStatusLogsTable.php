<?php

namespace App\Filament\Resources\OrderStatusLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderStatusLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('order_id')->label('Order')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('start_count'),
                TextColumn::make('remains'),
                TextColumn::make('source'),
                TextColumn::make('created_at')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
