<?php

return [
    'auth' => [
        'credentials_incorrect' => 'بيانات الاعتماد المقدمة غير صحيحة.',
        'account_inactive' => 'حسابك غير نشط.',
        'logged_out' => 'تم تسجيل الخروج.',
    ],

    'orders' => [
        'service_not_available' => 'الخدمة غير متاحة.',
        'quantity_between' => 'يجب أن تكون الكمية بين :min و :max.',
        'link_required' => 'الرابط مطلوب.',
        'provider_rejected' => 'رفض المزود الطلب.',
    ],

    'refill' => [
        'not_eligible' => 'هذا الطلب غير مؤهل لإعادة التعبئة.',
        'request_failed' => 'فشل طلب إعادة التعبئة. يرجى المحاولة لاحقاً.',
        'submitted_pending' => 'تم إرسال طلب إعادة التعبئة. وهو قيد الانتظار.',
        'order_not_placed' => 'لم يتم تقديم الطلب بنجاح.',
        'service_no_refill' => 'هذه الخدمة لا تشمل إعادة التعبئة.',
        'after_completed_partial' => 'إعادة التعبئة متاحة فقط بعد اكتمال الطلب أو جزئياً.',
        'warranty_expired' => 'انتهت ضمانة إعادة التعبئة (:days يوماً).',
        'already_in_progress' => 'طلب إعادة تعبئة قيد التنفيذ بالفعل.',
    ],

    'deposits' => [
        'amount_gt_zero' => 'يجب أن يكون المبلغ أكبر من صفر.',
        'invalid_method' => 'طريقة إيداع غير صالحة.',
        'ccp_proof_required' => 'إثبات الدفع مطلوب لإيداعات CCP.',
        'pending_only_approve' => 'يمكن الموافقة على الإيداعات المعلقة فقط.',
        'pending_only_reject' => 'يمكن رفض الإيداعات المعلقة فقط.',
    ],

    'checkout' => [
        'receipt_pending' => 'تم إرسال الإيصال. في انتظار موافقة المسؤول على تيليغرام.',
        'receipt_processed' => 'تم إرسال الإيصال ومعالجة الطلب.',
        'minimum_checkout' => 'الحد الأدنى لمبلغ الطلب هو :amount دج. يرجى شحن محفظتك أولاً.',
        'minimum_topup' => 'الحد الأدنى لمبلغ الشحن هو :amount دج.',
    ],

    'pricing' => [
        'eur_idr_gt_zero' => 'يجب أن يكون PRICING_EUR_IDR أكبر من صفر.',
        'eur_dzd_gt_zero' => 'يجب أن يكون PRICING_EUR_DZD أكبر من صفر.',
        'quantity_min_one' => 'يجب أن تكون الكمية 1 على الأقل.',
        'price_changed' => 'تغير السعر. المتوقع :expected دج لكن السعر الحالي :actual دج. حدّث الصفحة وحاول مجدداً.',
    ],

    'wallet' => [
        'debit_positive' => 'يجب أن يكون مبلغ الخصم موجباً.',
        'insufficient_balance' => 'رصيد المحفظة غير كافٍ.',
    ],

    'comments' => [
        'required_for_service' => 'التعليقات مطلوبة لهذه الخدمة (واحد في كل سطر).',
        'enter_at_least_one' => 'أدخل تعليقاً واحداً على الأقل (واحد في كل سطر).',
        'count_mismatch' => '{0} أدخلت :count تعليقاً لكن الكمية :quantity. يجب أن يتطابقا.|{1} أدخلت :count تعليقاً لكن الكمية :quantity. يجب أن يتطابقا.|[2,*] أدخلت :count تعليقات لكن الكمية :quantity. يجب أن يتطابقا.',
    ],

    'catalog' => [
        'quality' => [
            'premium' => 'مميز',
            'standard' => 'قياسي',
            'economy' => 'اقتصادي',
        ],
        'refill' => [
            'auto' => 'تعبئة تلقائية',
            'auto_days' => 'تعبئة تلقائية :days يوم',
            'manual' => 'تعبئة',
            'manual_days' => 'تعبئة :days يوم',
            'lifetime' => 'تعبئة مدى الحياة',
            'none' => 'بدون تعبئة',
        ],
        'start' => [
            'instant' => 'فوري',
            'fast' => 'سريع',
            'slow' => 'بطيء',
            'normal' => 'بدء عادي',
        ],
        'drip_feed' => 'تدفق تدريجي',
        'top' => 'الأفضل',
        'cheap' => 'رخيص',
    ],
];
