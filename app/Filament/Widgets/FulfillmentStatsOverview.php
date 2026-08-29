<?php

namespace App\Filament\Widgets;

use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentSubmissionStatus;
use App\Filament\Pages\ManageFeaturedServices;
use App\Filament\Pages\ManageLandingPlatformCards;
use App\Filament\Pages\ManageLandingReviews;
use App\Filament\Resources\Deposits\DepositResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\PaymentSubmissions\PaymentSubmissionResource;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\PaymentSubmission;
use App\Models\StorefrontReviewsSettings;
use App\Models\Wallet;
use App\Services\Catalog\FeaturedServiceHealth;
use App\Services\Content\StorefrontPlatformCardsContent;
use App\Services\Content\StorefrontReviewsContent;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FulfillmentStatsOverview extends StatsOverviewWidget
{
    protected int | array | null $columns = 3;

    protected function getStats(): array
    {
        $pendingDeposits = Deposit::query()->where('status', DepositStatus::Pending)->count();
        $pendingPayments = PaymentSubmission::query()->where('status', PaymentSubmissionStatus::Pending)->count();
        $openOrders = Order::query()->whereIn('status', [
            OrderStatus::Pending,
            OrderStatus::Processing,
            OrderStatus::InProgress,
            OrderStatus::Partial,
        ])->count();
        $todayVolume = Order::query()
            ->whereDate('created_at', today())
            ->sum('charge_dzd');
        $walletBalance = Wallet::query()->sum('balance');
        $storefrontIssues = app(FeaturedServiceHealth::class)->issueCount();
        $reviewSettings = StorefrontReviewsSettings::current();
        $publishedReviews = app(StorefrontReviewsContent::class)->publishedCount();
        $platformCards = app(StorefrontPlatformCardsContent::class)->payload()['platforms'] ?? [];
        $lowestPlatformPrice = collect($platformCards)->min('starting_price_dzd');

        return [
            Stat::make('Platform cards', $lowestPlatformPrice ? number_format((int) $lowestPlatformPrice).' DA+' : 'Not set')
                ->description(count($platformCards).' live landing cards')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color($lowestPlatformPrice ? 'primary' : 'warning')
                ->url(ManageLandingPlatformCards::getUrl()),
            Stat::make('Published reviews', number_format($publishedReviews))
                ->description($reviewSettings->section_enabled ? 'Landing section live' : 'Landing section hidden')
                ->descriptionIcon($reviewSettings->section_enabled ? 'heroicon-m-chat-bubble-left-right' : 'heroicon-m-eye-slash')
                ->color($publishedReviews > 0 ? 'success' : 'warning')
                ->url(ManageLandingReviews::getUrl()),
            Stat::make('Storefront issues', number_format($storefrontIssues))
                ->description($storefrontIssues > 0 ? 'Featured defaults need attention' : 'All storefront defaults healthy')
                ->descriptionIcon($storefrontIssues > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($storefrontIssues > 0 ? 'danger' : 'success')
                ->url(ManageFeaturedServices::getUrl()),
            Stat::make('Pending deposits', number_format($pendingDeposits))
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($pendingDeposits > 0 ? 'warning' : 'success')
                ->url(DepositResource::getUrl('index', ['tableFilters' => ['status' => ['value' => DepositStatus::Pending->value]]])),
            Stat::make('Pending payments', number_format($pendingPayments))
                ->description('CCP receipts to verify')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color($pendingPayments > 0 ? 'warning' : 'success')
                ->url(PaymentSubmissionResource::getUrl('index', ['tableFilters' => ['status' => ['value' => PaymentSubmissionStatus::Pending->value]]])),
            Stat::make('Open orders', number_format($openOrders))
                ->description('In progress at provider')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary')
                ->url(OrderResource::getUrl('index')),
            Stat::make('Today volume', number_format((float) $todayVolume, 0).' DA')
                ->description('Total wallet balances: '.number_format((float) $walletBalance, 0).' DA')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
