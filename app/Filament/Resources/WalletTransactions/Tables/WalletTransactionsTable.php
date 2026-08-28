<?php

namespace App\Filament\Resources\WalletTransactions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('amount_dzd')->label('Amount')->numeric(decimalPlaces: 2)->suffix(' DA'),
                TextColumn::make('balance_after')->label('Balance after')->numeric(decimalPlaces: 2),
                TextColumn::make('description')->limit(40),
                TextColumn::make('created_at')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
