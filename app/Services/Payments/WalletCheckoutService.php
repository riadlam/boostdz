<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Orders\OrderService;
use App\Services\Pricing\PricingService;
use App\Services\Telegram\PaymentTelegramNotifier;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WalletCheckoutService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PricingService $pricing,
        private readonly WalletService $wallets,
        private readonly PaymentTelegramNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function checkout(User $user, array $data): Order
    {
        $service = Service::query()->findOrFail((int) $data['service_id']);

        if (! $service->is_active) {
            throw new InvalidArgumentException(__('api.orders.service_not_available'));
        }

        $quantity = (int) ($data['quantity'] ?? 0);
        $link = trim((string) ($data['link'] ?? ''));

        if ($quantity < $service->min || $quantity > $service->max) {
            throw new InvalidArgumentException(__('api.orders.quantity_between', [
                'min' => $service->min,
                'max' => $service->max,
            ]));
        }

        if ($link === '') {
            throw new InvalidArgumentException(__('api.orders.link_required'));
        }

        $this->orders->assertTargetAvailableForOrder($user, $link);
        $service->validateComments($data['comments'] ?? null, $quantity);

        $quote = $this->pricing->quote($service, $quantity);
        $charge = number_format((float) $this->pricing->roundDzd($quote->charge_dzd), 2, '.', '');

        $this->pricing->assertExpectedCharge(
            isset($data['expected_charge_dzd']) ? (string) $data['expected_charge_dzd'] : null,
            $quote,
        );

        $idempotencyKey = (string) ($data['idempotency_key'] ?? Str::uuid());
        $existing = Order::query()
            ->where('idempotency_key', $idempotencyKey)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $this->ensureWalletCharged($existing);

            return $existing->fresh(['service', 'provider']);
        }

        $wallet = $this->wallets->forUser($user);

        if ((float) $wallet->availableBalance() < (float) $charge) {
            throw new InvalidArgumentException(__('api.wallet.insufficient_balance'));
        }

        $orderPayload = [
            'link' => $link,
            'quantity' => $quantity,
            'is_repeat' => (bool) ($data['is_repeat'] ?? false),
            'idempotency_key' => $idempotencyKey,
            'country' => $data['country'] ?? null,
            'quality' => $data['quality'] ?? null,
            'comments' => isset($data['comments']) && trim((string) $data['comments']) !== ''
                ? trim((string) $data['comments'])
                : null,
            'expected_charge_dzd' => $charge,
            'meta' => [
                'payment_method' => 'wallet',
                'platform_slug' => $data['platform_slug'] ?? null,
                'category_slug' => $data['category_slug'] ?? null,
                'quoted_charge_dzd' => $quote->charge_dzd,
                'pricing' => $quote->toArray(),
            ],
            '_skip_minimum_check' => true,
        ];

        try {
            return DB::transaction(function () use ($user, $service, $orderPayload, $charge, $wallet) {
                $lockedWallet = $this->wallets->forUser($user);
                $lockedWallet = \App\Models\Wallet::query()->whereKey($lockedWallet->id)->lockForUpdate()->firstOrFail();

                if ((float) $lockedWallet->availableBalance() < (float) $charge) {
                    throw new InvalidArgumentException(__('api.wallet.insufficient_balance'));
                }

                $order = $this->orders->place($user, $service, $orderPayload);

                if ($order->status !== OrderStatus::Failed) {
                    $this->ensureWalletCharged($order);
                }

                $fresh = $order->fresh(['user.wallet', 'service']);

                try {
                    $this->notifier->notifyWalletCheckout($fresh, $order->status === OrderStatus::Failed ? 'failed' : 'placed');
                } catch (\Throwable $exception) {
                    Log::warning('Wallet checkout Telegram notify failed.', [
                        'order_id' => $order->id,
                        'message' => $exception->getMessage(),
                    ]);
                }

                return $fresh;
            });
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        }
    }

    protected function ensureWalletCharged(Order $order): void
    {
        if ($order->status === OrderStatus::Failed) {
            return;
        }

        $alreadyCharged = WalletTransaction::query()
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->exists();

        if ($alreadyCharged) {
            return;
        }

        $this->wallets->chargeOrder($order->loadMissing('user'));
    }
}
