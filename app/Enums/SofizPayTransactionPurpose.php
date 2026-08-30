<?php

namespace App\Enums;

enum SofizPayTransactionPurpose: string
{
    case Checkout = 'checkout';
    case Topup = 'topup';
}
