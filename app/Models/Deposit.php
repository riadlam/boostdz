<?php

namespace App\Models;

use App\Enums\DepositStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Deposit extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount_dzd',
        'method',
        'status',
        'proof_path',
        'wired_amount_dzd',
        'provider_reference',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
        'telegram_chat_id',
        'telegram_message_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_dzd' => 'decimal:2',
            'wired_amount_dzd' => 'decimal:2',
            'status' => DepositStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function proofPublicUrl(): ?string
    {
        if (! $this->proof_path) {
            return null;
        }

        return Storage::disk('public')->url($this->proof_path);
    }
}
