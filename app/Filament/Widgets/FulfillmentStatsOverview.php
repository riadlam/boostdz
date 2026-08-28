<?php

namespace App\Filament\Widgets;

use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentSubmissionStatus;
use App\Filament\Resources\Deposits\DepositResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\PaymentSubmissions\PaymentSubmissionResource;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\PaymentSubmission;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FulfillmentStatsOverview extends StatsOverviewWidget
{
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

        return [
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
