<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FeaturedServiceAlertsWidget;
use App\Filament\Widgets\FulfillmentStatsOverview;
use App\Filament\Widgets\PendingReviewsTable;
use App\Filament\Widgets\RecentOrdersTable;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $title = 'Overview';

    public function getWidgets(): array
    {
        return [
            FulfillmentStatsOverview::class,
            FeaturedServiceAlertsWidget::class,
            RecentOrdersTable::class,
            PendingReviewsTable::class,
        ];
    }
}
