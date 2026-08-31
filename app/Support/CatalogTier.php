<?php

namespace App\Support;

class CatalogTier
{
    public const BASIC = 'basic';

    public const GOLD = 'gold';

    public const PREMIUM = 'premium';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::BASIC,
            self::GOLD,
            self::PREMIUM,
        ];
    }

    public static function isValid(?string $tier): bool
    {
        return in_array($tier, self::all(), true);
    }

    public static function serviceColumn(string $tier): string
    {
        return match ($tier) {
            self::GOLD => 'gold_service_id',
            self::PREMIUM => 'premium_service_id',
            default => 'basic_service_id',
        };
    }
}
