<?php

return [
    'auth' => [
        'credentials_incorrect' => 'The provided credentials are incorrect.',
        'account_inactive' => 'Your account is inactive.',
        'logged_out' => 'Logged out.',
    ],

    'orders' => [
        'service_not_available' => 'Service is not available.',
        'quantity_between' => 'Quantity must be between :min and :max.',
        'link_required' => 'Link is required.',
        'provider_rejected' => 'Provider rejected the order.',
    ],

    'refill' => [
        'not_eligible' => 'This order is not eligible for refill.',
        'request_failed' => 'Refill request failed. Please try again later.',
        'submitted_pending' => 'Refill request submitted. It is now pending.',
        'order_not_placed' => 'Order was not placed successfully.',
        'service_no_refill' => 'This service does not include refill.',
        'after_completed_partial' => 'Refill is only available after the order is completed or partial.',
        'warranty_expired' => 'Refill warranty expired (:days days).',
        'already_in_progress' => 'A refill request is already in progress.',
    ],

    'deposits' => [
        'amount_gt_zero' => 'Amount must be greater than zero.',
        'invalid_method' => 'Invalid deposit method.',
        'ccp_proof_required' => 'Proof of payment is required for CCP deposits.',
        'pending_only_approve' => 'Only pending deposits can be approved.',
        'pending_only_reject' => 'Only pending deposits can be rejected.',
    ],

    'checkout' => [
        'receipt_pending' => 'Receipt submitted. Waiting for admin approval on Telegram.',
        'receipt_processed' => 'Receipt submitted and order processed.',
        'minimum_checkout' => 'Minimum checkout amount is :amount DA. Please top up your wallet first.',
        'minimum_topup' => 'Minimum top-up amount is :amount DA.',
    ],

    'pricing' => [
        'eur_idr_gt_zero' => 'PRICING_EUR_IDR must be greater than zero.',
        'eur_dzd_gt_zero' => 'PRICING_EUR_DZD must be greater than zero.',
        'quantity_min_one' => 'Quantity must be at least 1.',
        'price_changed' => 'Price changed. Expected :expected DA but current price is :actual DA. Refresh and try again.',
    ],

    'wallet' => [
        'debit_positive' => 'Debit amount must be positive.',
        'insufficient_balance' => 'Insufficient wallet balance.',
    ],

    'sofizpay' => [
        'disabled' => 'Algérie Post payment is not available right now.',
        'phone_required' => 'Phone number is required for Algérie Post payment.',
        'phone_invalid' => 'Enter a valid Algerian mobile number (e.g. 0555123456 or +213555123456).',
        'missing_reference' => 'Payment reference is missing.',
        'missing_cib_transaction' => 'Payment transaction ID is missing.',
        'payment_not_successful' => 'Payment was not completed successfully.',
    ],

    'comments' => [
        'required_for_service' => 'Comments are required for this service (one per line).',
        'enter_at_least_one' => 'Enter at least one comment (one per line).',
        'count_mismatch' => '{0} You entered :count comments but quantity is :quantity. They must match.|{1} You entered :count comment but quantity is :quantity. They must match.|[2,*] You entered :count comments but quantity is :quantity. They must match.',
    ],

    'catalog' => [
        'quality' => [
            'premium' => 'Premium',
            'standard' => 'Standard',
            'economy' => 'Economy',
        ],
        'refill' => [
            'auto' => 'Auto refill',
            'auto_days' => 'Auto refill :days d',
            'manual' => 'Refill',
            'manual_days' => 'Refill :days d',
            'lifetime' => 'Lifetime refill',
            'none' => 'No refill',
        ],
        'start' => [
            'instant' => 'Instant',
            'fast' => 'Fast',
            'slow' => 'Slow',
            'normal' => 'Normal start',
        ],
        'drip_feed' => 'Drip-feed',
        'top' => 'Top',
        'cheap' => 'Cheap',
    ],
];
