<?php

namespace App\Filament\Pages;

use App\Models\StorefrontPlatformCard;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ManageLandingPlatformCards extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Platform card pricing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Fulfillment';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Platform card pricing';

    protected static ?string $slug = 'manage-landing-platform-cards';

    protected string $view = 'filament.pages.manage-landing-platform-cards';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => StorefrontPlatformCard::query())
            ->heading('Landing platform cards')
            ->description('Edit starting price and review count inline. Changes appear on the home page platform section.')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('platform_slug')
                    ->label('Platform')
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString())
                    ->searchable()
                    ->sortable(),
                TextInputColumn::make('starting_price_dzd')
                    ->label('Starting price')
                    ->type('number')
                    ->rules(['required', 'integer', 'min:0'])
                    ->suffix('DA'),
                TextInputColumn::make('review_count_display')
                    ->label('Review count')
                    ->rules(['required', 'string', 'max:32'])
                    ->placeholder('235+'),
                ToggleColumn::make('is_published')
                    ->label('Published'),
            ]);
    }
}
