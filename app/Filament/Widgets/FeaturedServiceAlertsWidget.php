<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ManageFeaturedServices;
use App\Models\CatalogCategory;
use App\Services\Catalog\FeaturedServiceHealth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FeaturedServiceAlertsWidget extends TableWidget
{
    protected static ?string $heading = 'Storefront default issues';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return app(FeaturedServiceHealth::class)->issueCount() > 0;
    }

    public function table(Table $table): Table
    {
        $health = app(FeaturedServiceHealth::class);

        return $table
            ->query(fn (): Builder => $health->issuesQuery())
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('platform.name')->label('Platform'),
                TextColumn::make('name')->label('Category'),
                TextColumn::make('featuredService.name')
                    ->label('Featured service')
                    ->placeholder('—'),
                TextColumn::make('issue')
                    ->label('Issue')
                    ->state(fn (CatalogCategory $record): string => $health->describeIssue($record))
                    ->color('danger')
                    ->icon('heroicon-m-exclamation-triangle'),
            ])
            ->recordUrl(fn (): string => ManageFeaturedServices::getUrl())
            ->emptyStateHeading('All storefront defaults are healthy');
    }
}
