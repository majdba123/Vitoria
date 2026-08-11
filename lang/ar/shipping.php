<?php

return [

    'status' => [
        'pending' => 'قيد الانتظار',
        'preparing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'out_for_delivery' => 'قيد التوصيل',
        'delivered' => 'تم التسليم',
        'failed' => 'فشل التوصيل',
        'returned' => 'أُعيد إلى التاجر',
    ],

    'method' => [
        'standard_delivery' => 'توصيل عادي',
        'express_delivery' => 'توصيل سريع',
        'vendor_delivery' => 'توصيل من التاجر',
    ],

    'method_invalid' => 'يرجى اختيار طريقة شحن صحيحة.',
    'transition_invalid' => 'لا يمكن نقل الشحنة من :from إلى :to.',
    'transition_conflict' => 'تم تحديث هذه الشحنة من قبل شخص آخر. يرجى إعادة التحميل والمحاولة مرة أخرى.',
    'transition_success' => 'تم تحديث حالة الشحنة.',
    'failure_reason_required' => 'يرجى وصف سبب فشل محاولة التوصيل.',
];
