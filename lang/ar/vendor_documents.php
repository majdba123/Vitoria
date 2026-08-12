<?php

return [

    'type' => [
        'commercial_registration' => 'السجل التجاري',
        'business_license' => 'رخصة العمل',
        'tax_registration' => 'التسجيل الضريبي',
        'industry_license' => 'الرخصة الصناعية',
        'other' => 'أخرى',
    ],

    'status' => [
        'pending_review' => 'بانتظار المراجعة',
        'verified' => 'موثّق',
        'rejected' => 'مرفوض',
        'expired' => 'منتهي الصلاحية',
        'suspended' => 'موقوف',
    ],

    'type_invalid' => 'يرجى اختيار نوع وثيقة صحيح.',
    'status_invalid' => 'يمكن فقط اعتماد الوثيقة أو رفضها.',
    'rejection_reason_required' => 'يرجى توضيح سبب رفض هذه الوثيقة.',
    'transition_invalid' => 'لا يمكن مراجعة هذه الوثيقة من حالتها الحالية.',
    'transition_conflict' => 'تمت مراجعة هذه الوثيقة بالفعل من قبل شخص آخر.',
    'uploaded_success' => 'تم إرسال الوثيقة للمراجعة.',
    'reviewed_success' => 'تم تسجيل مراجعة الوثيقة.',
    'suspended_success' => 'تم إيقاف الوثيقة.',
    'not_found' => 'الوثيقة غير موجودة.',
];
