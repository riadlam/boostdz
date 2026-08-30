<?php

namespace App\Support;

use InvalidArgumentException;

class PhoneNumber
{
    public static function normalize(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', trim($phone)) ?? '';

        if ($phone === '') {
            throw new InvalidArgumentException(__('api.sofizpay.phone_required'));
        }

        if (preg_match('/^0[5-7][0-9]{8}$/', $phone)) {
            return '+213'.substr($phone, 1);
        }

        if (preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            return $phone;
        }

        throw new InvalidArgumentException(__('api.sofizpay.phone_invalid'));
    }

    public static function isValid(string $phone): bool
    {
        try {
            self::normalize($phone);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
