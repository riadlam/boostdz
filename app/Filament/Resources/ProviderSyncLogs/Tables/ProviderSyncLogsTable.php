<?php

namespace App\Filament\Resources\ProviderSyncLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProviderSyncLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('provider.name')->label('Provider'),
                TextColumn::make('status')->badge(),
                TextColumn::make('type')->badge(),
                TextColumn::make('records_synced')->label('Records'),
                TextColumn::make('duration_ms')->label('Duration (ms)'),
                TextColumn::make('created_at')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
