<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->badge(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('wallet.balance')->label('Balance')->numeric(decimalPlaces: 2)->suffix(' DA'),
                TextColumn::make('created_at')->dateTime('d M Y'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
