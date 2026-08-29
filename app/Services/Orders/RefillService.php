<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\RefillStatus;
use App\Models\Order;
use App\Models\OrderRefill;
use App\Services\BuzzerPanel\BuzzerPanelClient;
use App\Services\BuzzerPanel\BuzzerPanelException;
use InvalidArgumentException;

class RefillService
{
    /**
     * Request a refill from BuzzerPanel:
     * POST action=refill, id=<provider_order_id>
     * Success: { "status": true, "data": { "id": 123 } }
     */
    public function request(Order $order): OrderRefill
    {
        $order->loadMissing(['service', 'provider', 'refills']);

        if (! $order->canRequestRefill()) {
            throw new InvalidArgumentException($order->refillBlockReason() ?? __('api.refill.not_eligible'));
        }

        $client = BuzzerPanelClient::fromProvider($order->provider);

        try {
            $remote = $client->refill($order->provider_order_id);
        } catch (BuzzerPanelException $exception) {
            throw new InvalidArgumentException($this->safePublicMessage($exception->getMessage()));
        }

        $providerRefillId = (int) ($remote['id'] ?? $remote['refill'] ?? 0) ?: null;

        return OrderRefill::query()->create([
            'order_id' => $order->id,
            'provider_id' => $order->provider_id,
            'provider_refill_id' => $providerRefillId,
            'status' => RefillStatus::Processing,
            'raw_payload' => $remote,
            'requested_at' => now(),
        ]);
    }

    /**
     * Strip provider brand names from user-facing errors.
     */
    protected function safePublicMessage(?string $message): string
    {
        $clean = trim((string) $message);
        if ($clean === '') {
            return __('api.refill.request_failed');
        }

        $clean = preg_replace('/\bbuzzer\s*panel\b/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/\s{2,}/', ' ', $clean) ?? $clean;
        $clean = trim($clean, " \t\n\r\0\x0B-–—:|");

        return $clean !== '' ? $clean : __('api.refill.request_failed');
    }

    /**
     * Poll refill status from BuzzerPanel:
     * POST action=refill_status, id=<provider_refill_id>
     */
    public function syncStatus(OrderRefill $refill): OrderRefill
    {
        if (! $refill->provider_refill_id) {
            return $refill;
        }

        $refill->loadMissing('provider');
        $client = BuzzerPanelClient::fromProvider($refill->provider);

        try {
            $remote = $client->refillStatus($refill->provider_refill_id);
        } catch (BuzzerPanelException $exception) {
            $refill->update([
                'raw_payload' => array_merge($refill->raw_payload ?? [], [
                    'last_error' => $exception->getMessage(),
                    'checked_at' => now()->toIso8601String(),
                ]),
            ]);

            return $refill->fresh();
        }

        $status = RefillStatus::fromProvider($remote['status'] ?? null);

        $updates = [
            'status' => $status,
            'raw_payload' => $remote,
        ];

        if (! $status->isOpen()) {
            $updates['completed_at'] = now();
        }

        $refill->update($updates);

        return $refill->fresh(['order.service']);
    }

    /**
     * @return list<Order>
     */
    public function refillableOrdersForUser(int $userId, int $limit = 50)
    {
        return Order::query()
            ->with(['service.providerService', 'refills'])
            ->where('user_id', $userId)
            ->whereNotNull('provider_order_id')
            ->whereIn('status', [OrderStatus::Completed, OrderStatus::Partial])
            ->whereHas('service', function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('refill', true)
                        ->orWhereHas('providerService', fn ($ps) => $ps->where('refill', true));
                });
            })
            ->latest('completed_at')
            ->limit($limit)
            ->get()
            ->filter(fn (Order $order) => $order->canRequestRefill())
            ->values();
    }
}
