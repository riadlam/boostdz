<?php

namespace App\Filament\Resources\WalletTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WalletTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('user.email')->label('User'),
                        TextEntry::make('amount_dzd')->suffix(' DA'),
                        TextEntry::make('balance_before')->suffix(' DA'),
                        TextEntry::make('balance_after')->suffix(' DA'),
                        TextEntry::make('description')->columnSpanFull(),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
            ]);
    }
}
