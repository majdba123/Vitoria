<?php

return [

    'entry' => [
        'sale' => 'بيع — الطلب رقم :order',
        'commission' => 'عمولة المنصة — الطلب رقم :order',
        'refund' => 'استرداد — الطلب رقم :order',
        'settlement' => 'دفعة تسوية',
    ],

    'type' => [
        'sale' => 'بيع',
        'commission' => 'عمولة',
        'refund' => 'استرداد',
        'adjustment' => 'تسوية يدوية',
        'settlement' => 'تسوية',
    ],

    'method' => [
        'bank_transfer' => 'تحويل بنكي',
        'cash' => 'نقدًا',
        'other' => 'أخرى',
    ],

    'settlement_amount_invalid' => 'يجب أن يكون مبلغ التسوية أكبر من صفر.',
    'settlement_method_invalid' => 'يرجى اختيار طريقة تسوية صحيحة.',
    'settlement_exceeds_outstanding' => 'مبلغ التسوية يتجاوز الرصيد المستحق لهذا البائع.',
    'settlement_date_future' => 'لا يمكن أن يكون تاريخ الدفعة في المستقبل.',
    'settlement_recorded' => 'تم تسجيل التسوية.',
    'adjustment_recorded' => 'تم تسجيل التسوية اليدوية.',
    'paid_amount_not_greater_than_settled' => 'لدى هذا البائع بالفعل :settled مسجلة كمسددة. أدخل مبلغاً إجمالياً أكبر لتسجيل دفعة إضافية.',
];
