<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Partial = 'partial';
    case Canceled = 'canceled';
    case Refunded = 'refunded';
    case Failed = 'failed';

    public static function fromProvider(?string $status): self
    {
        $normalized = strtolower(trim((string) $status));

        // BuzzerPanel docs: Pending, Processing, Partial, In progress, Error, Success
        return match (true) {
            in_array($normalized, ['completed', 'complete', 'success', 'done'], true) => self::Completed,
            in_array($normalized, ['partial', 'partially completed'], true) => self::Partial,
            in_array($normalized, ['in progress', 'in_progress', 'progress'], true) => self::InProgress,
            in_array($normalized, ['processing'], true) => self::Processing,
            in_array($normalized, ['canceled', 'cancelled'], true) => self::Canceled,
            in_array($normalized, ['refunded', 'refund'], true) => self::Refunded,
            in_array($normalized, ['failed', 'error'], true) => self::Failed,
            default => self::Pending,
        };
    }
}
