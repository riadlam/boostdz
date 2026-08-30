<?php

use App\Http\Controllers\Api\V1\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Api\V1\Admin\ExchangeRateController;
use App\Http\Controllers\Api\V1\Admin\OpsController;
use App\Http\Controllers\Api\V1\Admin\ProviderController as AdminProviderController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\DepositController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\RefillController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\PaymentTelegramWebhookController;
use App\Http\Controllers\Api\V1\SofizPayController;
use App\Http\Controllers\Api\V1\SofizPayWebhookController;
use App\Http\Controllers\Api\V1\TelegramWebhookController;
use App\Http\Controllers\Api\V1\WalletCheckoutController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::post('telegram/webhook', TelegramWebhookController::class);
    Route::post('telegram/payment-webhook', PaymentTelegramWebhookController::class);

    Route::match(['get', 'post'], 'sofizpay/return', [SofizPayWebhookController::class, 'return'])
        ->middleware('throttle:30,1');
    Route::post('sofizpay/webhook', [SofizPayWebhookController::class, 'webhook'])
        ->middleware('throttle:30,1');

    Route::get('content/testimonials', [ContentController::class, 'testimonials']);
    Route::get('content/platform-cards', [ContentController::class, 'platformCards']);

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('services/platforms', [ServiceController::class, 'platforms']);
        Route::get('services', [ServiceController::class, 'index']);
        Route::get('services/{service}', [ServiceController::class, 'show']);
        Route::get('services/{service}/quote', [ServiceController::class, 'quote']);

        Route::get('catalog/platforms', [CatalogController::class, 'platforms']);
        Route::get('catalog/storefront', [CatalogController::class, 'storefront']);
        Route::get('catalog/platforms/{slug}/categories', [CatalogController::class, 'categories']);
        Route::get('catalog/categories/{category}/services', [CatalogController::class, 'services']);

        Route::get('wallet', [WalletController::class, 'show']);
        Route::get('wallet/transactions', [WalletController::class, 'transactions']);

        Route::get('deposits', [DepositController::class, 'index']);
        Route::post('deposits', [DepositController::class, 'store']);
        Route::get('deposits/{deposit}', [DepositController::class, 'show']);

        Route::get('checkout/settings', [CheckoutController::class, 'settings']);
        Route::post('checkout/ccp-receipt', [CheckoutController::class, 'storeCcpReceipt']);
        Route::post('checkout/wallet', [WalletCheckoutController::class, 'store']);

        Route::post('payments/sofizpay/checkout', [SofizPayController::class, 'initCheckout']);
        Route::post('payments/sofizpay/topup', [SofizPayController::class, 'initTopup']);
        Route::get('payments/sofizpay/{invoiceId}/status', [SofizPayController::class, 'status']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/check-target', [OrderController::class, 'checkTarget']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders/{order}/sync-status', [OrderController::class, 'syncStatus']);
        Route::get('orders/{order}/delivery', [OrderController::class, 'delivery']);

        Route::get('refills', [RefillController::class, 'index']);
        Route::get('orders/{order}/refills', [RefillController::class, 'forOrder']);
        Route::post('orders/{order}/refill', [RefillController::class, 'store'])->middleware('throttle:5,1');
        Route::get('refills/{refill}', [RefillController::class, 'show']);
        Route::post('refills/{refill}/sync-status', [RefillController::class, 'syncStatus']);

        Route::prefix('admin')->middleware('admin')->group(function (): void {
            Route::get('deposits', [AdminDepositController::class, 'index']);
            Route::post('deposits/{deposit}/approve', [AdminDepositController::class, 'approve']);
            Route::post('deposits/{deposit}/reject', [AdminDepositController::class, 'reject']);

            Route::get('providers', [AdminProviderController::class, 'index']);
            Route::get('providers/{provider}', [AdminProviderController::class, 'show']);
            Route::get('providers/{provider}/services', [AdminProviderController::class, 'services']);
            Route::post('providers/{provider}/sync-catalog', [AdminProviderController::class, 'syncCatalog']);
            Route::post('providers/{provider}/sync-balance', [AdminProviderController::class, 'syncBalance']);

            Route::get('exchange-rates', [ExchangeRateController::class, 'index']);
            Route::get('exchange-rates/latest', [ExchangeRateController::class, 'latest']);
            Route::post('exchange-rates', [ExchangeRateController::class, 'store']);

            Route::get('sync-logs', [OpsController::class, 'syncLogs']);
            Route::get('webhook-events', [OpsController::class, 'webhookEvents']);
            Route::get('webhook-events/{webhookEvent}', [OpsController::class, 'webhookEvent']);
            Route::get('orders', [OpsController::class, 'orders']);
            Route::get('orders/{order}', [OpsController::class, 'order']);
            Route::get('order-status-logs', [OpsController::class, 'orderStatusLogs']);
            Route::get('order-refills', [OpsController::class, 'orderRefills']);
        });
    });
});
