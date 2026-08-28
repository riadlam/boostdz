<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('service.name')->label('Service')->limit(35)->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('quantity')->numeric()->sortable(),
                TextColumn::make('charge_dzd')->label('Charge')->numeric(decimalPlaces: 0)->suffix(' DA')->sortable(),
                TextColumn::make('provider_order_id')->label('Provider ID')->toggleable(),
                TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $s) => [$s->value => str($s->name)->headline()])->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
