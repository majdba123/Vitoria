<?php

return [

    'type' => [
        'leaflet' => 'نشرة توضيحية',
        'label' => 'الملصق',
        'safety_data_sheet' => 'ورقة بيانات السلامة',
        'registration_certificate' => 'شهادة التسجيل',
        'manufacturer_document' => 'وثيقة الشركة المصنعة',
        'other' => 'أخرى',
    ],

    'status' => [
        'pending_review' => 'بانتظار المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'disabled' => 'معطّل',
    ],

    'type_invalid' => 'يرجى اختيار نوع وثيقة صحيح.',
    'status_invalid' => 'يمكن فقط اعتماد الوثيقة أو رفضها.',
    'rejection_reason_required' => 'يرجى توضيح سبب رفض هذه الوثيقة.',
    'transition_invalid' => 'لا يمكن مراجعة هذه الوثيقة من حالتها الحالية.',
    'transition_conflict' => 'تمت مراجعة هذه الوثيقة بالفعل من قبل شخص آخر.',
    'uploaded_success' => 'تم إرسال الوثيقة للمراجعة.',
    'reviewed_success' => 'تم تسجيل مراجعة الوثيقة.',
    'disabled_success' => 'تم تعطيل الوثيقة.',
    'not_found' => 'الوثيقة غير موجودة.',
];
