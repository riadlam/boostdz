<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'min' => [
        'numeric' => 'يجب أن يكون :attribute على الأقل :min.',
        'integer' => 'يجب أن يكون :attribute على الأقل :min.',
    ],
    'max' => [
        'string' => 'يجب ألا يتجاوز :attribute :max حرفاً.',
        'file' => 'يجب ألا يتجاوز :attribute :max كيلوبايت.',
    ],
    'uuid' => 'يجب أن يكون :attribute معرف UUID صالحاً.',
    'exists' => ':attribute المحدد غير صالح.',
    'mimes' => 'يجب أن يكون :attribute ملفاً من النوع: :values.',
    'file' => 'يجب أن يكون :attribute ملفاً.',

    'attributes' => [
        'service_id' => 'الخدمة',
        'link' => 'الرابط',
        'quantity' => 'الكمية',
        'amount_dzd' => 'المبلغ',
        'receipt' => 'الإيصال',
        'comments' => 'التعليقات',
        'idempotency_key' => 'مفتاح التكرار',
        'expected_charge_dzd' => 'المبلغ المتوقع',
        'reference' => 'المرجع',
    ],
];
