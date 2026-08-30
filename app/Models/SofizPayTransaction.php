<?php

namespace App\Models;

use App\Enums\SofizPayTransactionPurpose;
use App\Enums\SofizPayTransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SofizPayTransaction extends Model
{
    protected $table = 'sofizpay_transactions';

    protected $fillable = [
        'user_id',
        'purpose',
        'invoice_id',
        'amount_dzd',
        'status',
        'sofizpay_transaction_id',
        'cib_transaction_id',
        'payment_url',
        'deposit_id',
        'order_id',
        'checkout_meta',
        'raw_init_response',
        'raw_callback_payload',
        'raw_verify_response',
        'verified_at',
        'completed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => SofizPayTransactionPurpose::class,
            'status' => SofizPayTransactionStatus::class,
            'amount_dzd' => 'decimal:2',
            'checkout_meta' => 'array',
            'raw_init_response' => 'array',
            'raw_callback_payload' => 'array',
            'raw_verify_response' => 'array',
            'verified_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPending(): bool
    {
        return $this->status === SofizPayTransactionStatus::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->status === SofizPayTransactionStatus::Completed;
    }
}
