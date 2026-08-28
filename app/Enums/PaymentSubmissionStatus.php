<?php

namespace App\Enums;

enum PaymentSubmissionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case Failed = 'failed';
}
