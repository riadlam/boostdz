<?php

namespace App\Filament\Resources\WebhookEvents\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WebhookEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('provider.name')->label('Provider'),
                TextColumn::make('event')->badge(),
                TextColumn::make('provider_order_id')->label('Provider order'),
                IconColumn::make('signature_valid')->boolean(),
                TextColumn::make('processed_at')->dateTime('d M Y H:i'),
                TextColumn::make('created_at')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
