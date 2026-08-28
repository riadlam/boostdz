<?php

namespace App\Filament\Resources\PaymentSubmissions\Tables;

use App\Enums\PaymentSubmissionStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('service.name')->label('Service')->limit(30),
                TextColumn::make('amount_dzd')->label('Amount')->numeric(decimalPlaces: 0)->suffix(' DA'),
                TextColumn::make('status')->badge(),
                TextColumn::make('payment_method')->label('Method')->badge(),
                TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PaymentSubmissionStatus::cases())->mapWithKeys(fn (PaymentSubmissionStatus $s) => [$s->value => str($s->name)->headline()])->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
