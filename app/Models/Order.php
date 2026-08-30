<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'provider_id',
        'provider_order_id',
        'idempotency_key',
        'link',
        'quantity',
        'runs',
        'interval',
        'comments',
        'usernames',
        'hashtag',
        'posts',
        'delay',
        'expiry',
        'answer_number',
        'payload_meta',
        'status',
        'start_count',
        'remains',
        'charge_dzd',
        'cost_idr',
        'rate_idr_per_1k',
        'cost_eur',
        'base_dzd',
        'profit_dzd',
        'markup_percent',
        'pricing_snapshot',
        'currency_provider',
        'country',
        'quality',
        'is_repeat',
        'scheduled_at',
        'submitted_at',
        'completed_at',
        'last_status_check_at',
        'error_code',
        'error_message',
        'raw_last_response',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'runs' => 'integer',
            'interval' => 'integer',
            'posts' => 'integer',
            'delay' => 'integer',
            'answer_number' => 'integer',
            'payload_meta' => 'array',
            'status' => OrderStatus::class,
            'start_count' => 'integer',
            'remains' => 'integer',
            'charge_dzd' => 'decimal:2',
            'cost_idr' => 'decimal:4',
            'rate_idr_per_1k' => 'decimal:4',
            'cost_eur' => 'decimal:6',
            'base_dzd' => 'decimal:2',
            'profit_dzd' => 'decimal:2',
            'markup_percent' => 'decimal:2',
            'pricing_snapshot' => 'array',
            'is_repeat' => 'boolean',
            'scheduled_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_status_check_at' => 'datetime',
            'expiry' => 'datetime',
            'raw_last_response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function refills(): HasMany
    {
        return $this->hasMany(OrderRefill::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::openStatuses(), true);
    }

    /** @return list<OrderStatus> */
    public static function openStatuses(): array
    {
        return [
            OrderStatus::Pending,
            OrderStatus::Processing,
            OrderStatus::InProgress,
            OrderStatus::Partial,
        ];
    }

    /** @return list<OrderStatus> */
    public static function blockingTargetStatuses(): array
    {
        return [
            OrderStatus::Pending,
            OrderStatus::Processing,
            OrderStatus::InProgress,
        ];
    }

    public static function normalizeTarget(string $link): string
    {
        return strtolower(trim($link));
    }

    public static function hasBlockingOrderForTarget(int $userId, string $link): bool
    {
        $normalized = self::normalizeTarget($link);

        if ($normalized === '') {
            return false;
        }

        return self::query()
            ->where('user_id', $userId)
            ->whereIn('status', self::blockingTargetStatuses())
            ->whereRaw('LOWER(TRIM(link)) = ?', [$normalized])
            ->exists();
    }

    public function supportsRefill(): bool
    {
        $this->loadMissing(['service.providerService']);

        if (! $this->service) {
            return false;
        }

        if ($this->service->providerService !== null) {
            return (bool) $this->service->providerService->refill;
        }

        return (bool) $this->service->refill;
    }

    public function hasLifetimeRefill(): bool
    {
        $this->loadMissing('service');

        $mode = strtolower((string) ($this->service?->refill_mode ?? ''));
        if ($mode === 'lifetime') {
            return true;
        }

        $haystack = strtolower((string) ($this->service?->name.' '.$this->service?->description));

        return str_contains($haystack, 'lifetime')
            && (str_contains($haystack, 'refill') || str_contains($haystack, '♻'));
    }

    public function refillWarrantyDays(): int
    {
        $this->loadMissing('service');

        if ($this->hasLifetimeRefill()) {
            return 0;
        }

        $catalogDays = (int) ($this->service?->refill_days ?? 0);
        if ($catalogDays > 0) {
            return $catalogDays;
        }

        $haystack = (string) ($this->service?->name.' '.$this->service?->description);

        if (preg_match('/\b(?:ar|r)\s*(\d{1,3})\b/i', $haystack, $matches)
            || preg_match('/refill[^\d]{0,16}(\d{1,3})\s*d/i', $haystack, $matches)
            || preg_match('/(\d{1,3})\s*d[^\w]{0,12}refill/i', $haystack, $matches)
            || preg_match('/(\d{1,3})\s*days?\s*(?:♻️|♻|refill)/i', $haystack, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return (int) config('buzzerpanel.default_refill_days', 30);
    }

    public function canRequestRefill(): bool
    {
        return $this->refillBlockReason() === null;
    }

    public function refillBlockReason(): ?string
    {
        $this->loadMissing(['service', 'refills']);

        if (! $this->provider_order_id) {
            return __('api.refill.order_not_placed');
        }

        if (! $this->supportsRefill()) {
            return __('api.refill.service_no_refill');
        }

        if (! in_array($this->status, [OrderStatus::Completed, OrderStatus::Partial], true)) {
            return __('api.refill.after_completed_partial');
        }

        if (! $this->hasLifetimeRefill()) {
            $anchor = $this->completed_at ?? $this->submitted_at ?? $this->created_at;
            $days = $this->refillWarrantyDays();

            if ($days > 0 && $anchor && $anchor->copy()->addDays($days)->isPast()) {
                return __('api.refill.warranty_expired', ['days' => $days]);
            }
        }

        $openRefill = $this->refills->first(function (OrderRefill $refill): bool {
            $status = $refill->status;

            return $status instanceof \App\Enums\RefillStatus
                ? $status->isOpen()
                : in_array((string) $status, ['pending', 'processing', 'in_progress'], true);
        });

        if ($openRefill) {
            return __('api.refill.already_in_progress');
        }

        return null;
    }

    public function delivery(): \App\Services\Orders\DeliveryProgress
    {
        return \App\Services\Orders\DeliveryProgress::fromOrder($this);
    }

    public function deliveredQuantity(): int
    {
        return $this->delivery()->delivered;
    }

    public function deliveryPercent(): float
    {
        return $this->delivery()->percent;
    }
}
