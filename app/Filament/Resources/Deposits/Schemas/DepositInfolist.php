<?php

namespace App\Filament\Resources\Deposits\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class DepositInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Deposit')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('user.email')->label('User'),
                        TextEntry::make('amount_dzd')->label('Amount')->suffix(' DA'),
                        TextEntry::make('method')->badge(),
                        TextEntry::make('wired_amount_dzd')->label('Wired amount')->suffix(' DA'),
                        TextEntry::make('provider_reference')->label('Reference'),
                        TextEntry::make('admin_note')->columnSpanFull(),
                        TextEntry::make('reviewer.email')->label('Reviewed by'),
                        TextEntry::make('reviewed_at')->dateTime(),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
                Section::make('Proof')
                    ->schema([
                        ImageEntry::make('proof')
                            ->label('Receipt')
                            ->getStateUsing(fn ($record) => $record->proof_path ? Storage::disk('public')->url($record->proof_path) : null)
                            ->visible(fn ($record) => filled($record->proof_path)),
                    ]),
            ]);
    }
}
