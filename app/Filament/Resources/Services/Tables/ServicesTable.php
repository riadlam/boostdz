<?php

namespace App\Filament\Resources\Services\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('platform')->badge()->searchable(),
                TextColumn::make('catalogCategory.name')->label('Category'),
                TextColumn::make('name')->limit(40)->searchable(),
                TextColumn::make('sell_rate_dzd')->label('Sell rate')->numeric(decimalPlaces: 0),
                IconColumn::make('is_active')->boolean(),
                IconColumn::make('is_hot')->boolean(),
                TextColumn::make('min')->numeric(),
                TextColumn::make('max')->numeric(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
                SelectFilter::make('platform')
                    ->options(fn () => \App\Models\CatalogPlatform::query()->orderBy('sort_order')->pluck('name', 'slug')->all()),
                SelectFilter::make('catalog_category_id')
                    ->label('Category')
                    ->relationship('catalogCategory', 'name', fn (Builder $query) => $query->orderBy('sort_order')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
