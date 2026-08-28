<?php

namespace App\Filament\Resources\Providers\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('slug'),
                TextColumn::make('cached_balance')->label('Balance')->numeric(),
                TextColumn::make('currency'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('balance_synced_at')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
