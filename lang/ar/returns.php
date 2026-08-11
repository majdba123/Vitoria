<?php

return [

    'status' => [
        'requested' => 'مقدَّم',
        'under_review' => 'قيد المراجعة',
        'approved' => 'موافَق عليه',
        'rejected' => 'مرفوض',
        'received' => 'تم الاستلام',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
    ],

    'reason' => [
        'damaged_on_arrival' => 'تالف عند الوصول',
        'wrong_item_received' => 'تم استلام منتج خاطئ',
        'expired_product' => 'منتج منتهي الصلاحية',
        'not_as_described' => 'لا يطابق الوصف',
        'missing_items' => 'عناصر ناقصة',
        'quality_issue' => 'مشكلة في الجودة',
        'other' => 'أخرى',
    ],

    'order_invalid' => 'هذا الطلب لا يخصك.',
    'reason_invalid' => 'يرجى اختيار سبب إرجاع صحيح.',
    'order_not_returnable' => 'لا يمكن إرجاع طلب حالته :status.',
    'items_required' => 'اختر عنصرًا واحدًا على الأقل لإرجاعه.',
    'quantity_invalid' => 'يجب أن تكون كمية الإرجاع 1 على الأقل.',
    'item_invalid' => 'هذا العنصر ليس جزءًا من هذا الطلب.',
    'quantity_exceeds' => 'لا يمكنك إرجاع كمية أكبر مما اشتريته من :product.',
    'transition_not_permitted' => 'غير مسموح لك بإجراء هذا التغيير.',
    'transition_invalid' => 'لا يمكن نقل الإرجاع من :from إلى :to.',
    'transition_conflict' => 'تم تحديث هذا الإرجاع من قبل شخص آخر. يرجى إعادة التحميل والمحاولة مرة أخرى.',
    'transition_success' => 'تم تحديث حالة الإرجاع.',
    'requested_success' => 'تم إرسال طلب الإرجاع الخاص بك.',
    'cancelled_success' => 'تم إلغاء طلب الإرجاع.',
];
