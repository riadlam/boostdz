<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Service;
use App\Models\User;
use App\Services\BuzzerPanel\BuzzerPanelClient;
use App\Services\BuzzerPanel\BuzzerPanelException;
use App\Exceptions\MinimumCheckoutException;
use App\Services\Checkout\CheckoutPolicy;
use App\Services\Pricing\PricingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly CheckoutPolicy $checkoutPolicy,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function place(User $user, Service $service, array $payload): Order
    {
        if (! $service->is_active) {
            throw new InvalidArgumentException(__('api.orders.service_not_available'));
        }

        $service->loadMissing('providerService.provider');

        $quantity = (int) ($payload['quantity'] ?? 0);
        $link = trim((string) ($payload['link'] ?? ''));

        if ($quantity < $service->min || $quantity > $service->max) {
            throw new InvalidArgumentException(__('api.orders.quantity_between', [
                'min' => $service->min,
                'max' => $service->max,
            ]));
        }

        if ($link === '') {
            throw new InvalidArgumentException(__('api.orders.link_required'));
        }

        $idempotencyKey = (string) ($payload['idempotency_key'] ?? Str::uuid());
        $existing = Order::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return $existing;
        }

        $quote = $this->pricing->quote($service, $quantity);
        $this->checkoutPolicy->assertMinimumCheckout($quote);
        $this->pricing->assertExpectedCharge(
            isset($payload['expected_charge_dzd']) ? (string) $payload['expected_charge_dzd'] : null,
            $quote,
        );

        $provider = $service->providerService->provider;
        $pricingAttrs = $quote->orderPricingAttributes();

        return DB::transaction(function () use ($user, $service, $payload, $quantity, $link, $idempotencyKey, $provider, $pricingAttrs) {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'service_id' => $service->id,
                'provider_id' => $provider->id,
                'idempotency_key' => $idempotencyKey,
                'link' => $link,
                'quantity' => $quantity,
                'runs' => $payload['runs'] ?? null,
                'interval' => $payload['interval'] ?? null,
                'comments' => $payload['comments'] ?? null,
                'usernames' => $payload['usernames'] ?? null,
                'hashtag' => $payload['hashtag'] ?? null,
                'posts' => $payload['posts'] ?? null,
                'delay' => $payload['delay'] ?? null,
                'expiry' => $payload['expiry'] ?? null,
                'answer_number' => $payload['answer_number'] ?? null,
                'payload_meta' => $payload['meta'] ?? null,
                'status' => OrderStatus::Pending,
                'charge_dzd' => $pricingAttrs['charge_dzd'],
                'cost_idr' => $pricingAttrs['cost_idr'],
                'rate_idr_per_1k' => $pricingAttrs['rate_idr_per_1k'],
                'cost_eur' => $pricingAttrs['cost_eur'],
                'base_dzd' => $pricingAttrs['base_dzd'],
                'profit_dzd' => $pricingAttrs['profit_dzd'],
                'markup_percent' => $pricingAttrs['markup_percent'],
                'pricing_snapshot' => $pricingAttrs['pricing_snapshot'],
                'currency_provider' => $provider->currency,
                'country' => $payload['country'] ?? null,
                'quality' => $payload['quality'] ?? null,
                'is_repeat' => (bool) ($payload['is_repeat'] ?? false),
            ]);

            try {
                $client = BuzzerPanelClient::fromProvider($provider);
                $remote = $client->placeOrder($this->buildProviderPayload($service, $order));

                $order->update([
                    'provider_order_id' => (int) ($remote['id'] ?? $remote['order'] ?? 0) ?: null,
                    'status' => OrderStatus::Processing,
                    'submitted_at' => now(),
                    'raw_last_response' => $remote,
                ]);
            } catch (BuzzerPanelException $exception) {
                $order->update([
                    'status' => OrderStatus::Failed,
                    'error_message' => $exception->getMessage(),
                    'raw_last_response' => $exception->response(),
                ]);
            }

            return $order->fresh(['service', 'provider']);
        });
    }

    /**
     * Poll BuzzerPanel action=status and update delivery fields (start_count, remains, status).
     */
    public function syncStatus(Order $order, bool $force = false): Order
    {
        if (! $order->provider_order_id) {
            return $order;
        }

        $minInterval = (int) config('buzzerpanel.status_poll_min_seconds', 20);

        if (
            ! $force
            && $order->last_status_check_at
            && $order->last_status_check_at->greaterThan(now()->subSeconds($minInterval))
        ) {
            return $order->fresh(['service', 'provider', 'statusLogs']);
        }

        $order->loadMissing('provider');
        $client = BuzzerPanelClient::fromProvider($order->provider);

        try {
            $remote = $client->orderStatus($order->provider_order_id);
        } catch (BuzzerPanelException $exception) {
            $order->update([
                'last_status_check_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            return $order->fresh(['service', 'provider', 'statusLogs']);
        }

        $status = OrderStatus::fromProvider($remote['status'] ?? null);
        $startCount = array_key_exists('start_count', $remote) && $remote['start_count'] !== null && $remote['start_count'] !== ''
            ? (int) $remote['start_count']
            : $order->start_count;
        $remains = array_key_exists('remains', $remote) && $remote['remains'] !== null && $remote['remains'] !== ''
            ? max(0, (int) $remote['remains'])
            : $order->remains;

        if ($status === OrderStatus::Completed) {
            $remains = 0;
        }

        $changed = $order->status !== $status
            || (int) $order->start_count !== (int) $startCount
            || (int) $order->remains !== (int) $remains;

        if ($changed) {
            OrderStatusLog::query()->create([
                'order_id' => $order->id,
                'status' => $status->value,
                'start_count' => $startCount,
                'remains' => $remains,
                'charge_idr' => $remote['charge'] ?? null,
                'currency' => $remote['currency'] ?? 'IDR',
                'source' => 'poll',
                'raw_payload' => $remote,
                'created_at' => now(),
            ]);
        }

        $updates = [
            'status' => $status,
            'start_count' => $startCount,
            'remains' => $remains,
            'last_status_check_at' => now(),
            'raw_last_response' => $remote,
            'error_message' => null,
        ];

        if (
            in_array($status, [OrderStatus::Completed, OrderStatus::Partial, OrderStatus::Canceled, OrderStatus::Refunded], true)
            && ! $order->completed_at
        ) {
            $updates['completed_at'] = now();
        }

        $order->update($updates);

        return $order->fresh(['service', 'provider', 'statusLogs']);
    }

    /**
     * Sync open orders that need a fresh delivery poll.
     *
     * @return Collection<int, Order>
     */
    public function syncOpenOrders(int $limit = 100): Collection
    {
        $orders = Order::query()
            ->whereNotNull('provider_order_id')
            ->whereIn('status', [
                OrderStatus::Pending,
                OrderStatus::Processing,
                OrderStatus::InProgress,
                OrderStatus::Partial,
            ])
            ->orderByRaw('last_status_check_at IS NOT NULL, last_status_check_at ASC')
            ->limit($limit)
            ->get();

        return $orders->map(fn (Order $order) => $this->syncStatus($order, force: true));
    }

    public function delivery(Order $order): DeliveryProgress
    {
        return DeliveryProgress::fromOrder($order);
    }

    protected function buildProviderPayload(Service $service, Order $order): array
    {
        $service->loadMissing('providerService');
        $externalId = $service->providerService->external_id;

        $payload = [
            'service' => $externalId,
            'data' => $order->link,
            'quantity' => $order->quantity,
        ];

        foreach ([
            'comments', 'usernames', 'hashtag', 'runs', 'interval', 'posts', 'delay', 'answer_number',
        ] as $field) {
            if ($order->{$field} !== null && $order->{$field} !== '') {
                $payload[$field] = $order->{$field};
            }
        }

        return $payload;
    }
}
