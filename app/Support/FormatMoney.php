<?php

namespace App\Support;

class FormatMoney
{
    public static function dzdPerThousand(string|float|int|null $amount): string
    {
        return number_format((int) round((float) ($amount ?? 0)), 0, '.', ' ').' DZD/1k';
    }
}
