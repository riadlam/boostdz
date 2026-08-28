<?php

namespace App\Filament\Resources\ProviderServices\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProviderServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('synced_at', 'desc')
            ->columns([
                TextColumn::make('external_id')->label('External ID')->sortable(),
                TextColumn::make('provider.name')->label('Provider'),
                TextColumn::make('name')->limit(40)->searchable(),
                TextColumn::make('rate_idr')->label('Rate IDR')->numeric(),
                TextColumn::make('min')->numeric(),
                TextColumn::make('max')->numeric(),
                TextColumn::make('synced_at')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
