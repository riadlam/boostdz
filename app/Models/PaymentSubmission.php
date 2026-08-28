<?php

namespace App\Models;

use App\Enums\PaymentSubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PaymentSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'order_id',
        'payment_method',
        'link',
        'quantity',
        'is_repeat',
        'idempotency_key',
        'payload_meta',
        'amount_dzd',
        'payer_reference',
        'proof_path',
        'status',
        'telegram_chat_id',
        'telegram_message_id',
        'reviewed_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_repeat' => 'boolean',
            'payload_meta' => 'array',
            'amount_dzd' => 'decimal:2',
            'status' => PaymentSubmissionStatus::class,
            'reviewed_at' => 'datetime',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentSubmissionStatus::Pending;
    }

    public function proofPublicUrl(): ?string
    {
        if (! $this->proof_path) {
            return null;
        }

        return Storage::disk('public')->url($this->proof_path);
    }
}
