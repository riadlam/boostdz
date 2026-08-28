<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Deposit = 'deposit';
    case OrderCharge = 'order_charge';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
}
