<?php

namespace App\Filament\Resources\Deposits\Tables;

use App\Enums\DepositStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('amount_dzd')->label('Amount')->numeric(decimalPlaces: 0)->suffix(' DA'),
                TextColumn::make('method')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(DepositStatus::cases())->mapWithKeys(fn (DepositStatus $s) => [$s->value => str($s->name)->headline()])->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
