<?php

namespace App\Filament\Resources\Wallets\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('currency'),
                TextColumn::make('balance')->numeric(decimalPlaces: 2)->suffix(' DA'),
                TextColumn::make('locked_balance')->label('Locked')->numeric(decimalPlaces: 2),
                TextColumn::make('updated_at')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
