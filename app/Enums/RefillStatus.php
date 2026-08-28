<?php

namespace App\Enums;

enum RefillStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Failed = 'failed';

    public static function fromProvider(?string $status): self
    {
        $normalized = strtolower(trim((string) $status));

        return match (true) {
            in_array($normalized, ['completed', 'complete', 'success', 'done'], true) => self::Completed,
            in_array($normalized, ['rejected', 'reject', 'denied'], true) => self::Rejected,
            in_array($normalized, ['failed', 'error', 'fail'], true) => self::Failed,
            in_array($normalized, ['in progress', 'in_progress', 'progress'], true) => self::InProgress,
            in_array($normalized, ['processing'], true) => self::Processing,
            default => self::Pending,
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Processing, self::InProgress], true);
    }
}
