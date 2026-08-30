<?php

namespace App\Enums;

enum SofizPayTransactionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';
}
