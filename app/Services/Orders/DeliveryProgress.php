<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;

/**
 * Delivery progress derived from BuzzerPanel status fields:
 * start_count, remains, and our ordered quantity (goal).
 */
final class DeliveryProgress
{
    public function __construct(
        public readonly int $goal,
        public readonly ?int $startCount,
        public readonly ?int $remains,
        public readonly int $delivered,
        public readonly float $percent,
        public readonly bool $isComplete,
        public readonly bool $isTrackable,
        public readonly ?string $label,
    ) {}

    public static function fromOrder(Order $order): self
    {
        $goal = max(0, (int) $order->quantity);
        $startCount = $order->start_count !== null ? (int) $order->start_count : null;
        $remains = $order->remains !== null ? (int) $order->remains : null;
        $status = $order->status;

        if ($goal <= 0) {
            return new self(
                goal: 0,
                startCount: $startCount,
                remains: $remains,
                delivered: 0,
                percent: 0.0,
                isComplete: false,
                isTrackable: false,
                label: null,
            );
        }

        // Completed with no remains reported = fully delivered.
        if ($status === OrderStatus::Completed && $remains === null) {
            $remains = 0;
        }

        // Pending / not yet polled — not trackable yet.
        if ($remains === null && ! in_array($status, [OrderStatus::Completed, OrderStatus::Partial], true)) {
            return new self(
                goal: $goal,
                startCount: $startCount,
                remains: null,
                delivered: 0,
                percent: 0.0,
                isComplete: false,
                isTrackable: false,
                label: 'Waiting for delivery data',
            );
        }

        $remains ??= 0;
        $remains = max(0, min($remains, $goal));
        $delivered = max(0, $goal - $remains);
        $percent = round(($delivered / $goal) * 100, 1);

        if ($status === OrderStatus::Completed) {
            $percent = 100.0;
            $delivered = $goal;
            $remains = 0;
        }

        $isComplete = $status === OrderStatus::Completed || ($remains === 0 && $delivered >= $goal);

        return new self(
            goal: $goal,
            startCount: $startCount,
            remains: $remains,
            delivered: $delivered,
            percent: min(100.0, $percent),
            isComplete: $isComplete,
            isTrackable: true,
            label: $isComplete
                ? 'Delivered'
                : number_format($delivered).' / '.number_format($goal),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'goal' => $this->goal,
            'start_count' => $this->startCount,
            'remains' => $this->remains,
            'delivered' => $this->delivered,
            'percent' => $this->percent,
            'is_complete' => $this->isComplete,
            'is_trackable' => $this->isTrackable,
            'label' => $this->label,
            // Convenience for UI bars: current count on the target when start_count is known.
            'current_count' => $this->startCount !== null && $this->delivered > 0
                ? $this->startCount + $this->delivered
                : ($this->startCount !== null ? $this->startCount : null),
        ];
    }
}
