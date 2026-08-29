<?php

return [
    'required' => 'The :attribute field is required.',
    'integer' => 'The :attribute must be an integer.',
    'numeric' => 'The :attribute must be a number.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'integer' => 'The :attribute must be at least :min.',
    ],
    'max' => [
        'string' => 'The :attribute must not be greater than :max characters.',
        'file' => 'The :attribute must not be greater than :max kilobytes.',
    ],
    'uuid' => 'The :attribute must be a valid UUID.',
    'exists' => 'The selected :attribute is invalid.',
    'mimes' => 'The :attribute must be a file of type: :values.',
    'file' => 'The :attribute must be a file.',

    'attributes' => [
        'service_id' => 'service',
        'link' => 'link',
        'quantity' => 'quantity',
        'amount_dzd' => 'amount',
        'receipt' => 'receipt',
        'comments' => 'comments',
        'idempotency_key' => 'idempotency key',
        'expected_charge_dzd' => 'expected charge',
        'reference' => 'reference',
    ],
];
