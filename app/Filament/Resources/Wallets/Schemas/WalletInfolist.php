<?php

namespace App\Filament\Resources\Wallets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WalletInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Wallet')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.email')->label('User'),
                        TextEntry::make('currency'),
                        TextEntry::make('balance')->suffix(' DA'),
                        TextEntry::make('locked_balance')->label('Locked')->suffix(' DA'),
                        TextEntry::make('updated_at')->dateTime(),
                    ]),
            ]);
    }
}
