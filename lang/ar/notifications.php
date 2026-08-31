<?php

return [
    'new_product' => [
        'title' => 'منتج جديد',
        'body' => 'متوفر الآن: :product',
    ],
    'new_order' => [
        'title' => 'طلب جديد',
        'admin_body' => 'تم إنشاء طلب جديد. رقم الطلب: #:order',
        'vendor_body' => 'لديك طلب جديد. رقم الطلب: #:order',
    ],
    'order_status' => [
        'title' => 'تحديث الطلب',
        'completed' => 'تم إكمال الطلب #:order.',
        'cancelled' => 'تم إلغاء الطلب #:order.',
        'confirmed' => 'تم تأكيد الطلب #:order.',
        'updated' => 'تم تحديث حالة الطلب #:order إلى :status.',
    ],
    'return_requested' => [
        'title' => 'طلب إرجاع',
        'body' => 'تم تقديم طلب إرجاع جديد للطلب #:order.',
    ],
    'return_status' => [
        'title' => 'تحديث الإرجاع',
        'approved' => 'تمت الموافقة على طلب إرجاعك للطلب #:order.',
        'rejected' => 'تم رفض طلب إرجاعك للطلب #:order.',
        'received' => 'تم استلام المرتجع الخاص بالطلب #:order.',
        'completed' => 'اكتمل إرجاع الطلب #:order.',
        'cancelled' => 'تم إلغاء طلب إرجاع الطلب #:order.',
        'updated' => 'تم تحديث حالة إرجاع الطلب #:order.',
    ],
    'refund_status' => [
        'title' => 'تحديث الاسترداد',
        'pending' => 'تم إنشاء طلب استرداد للطلب #:order.',
        'completed' => 'تم استرداد مبلغ الطلب #:order.',
        'failed' => 'فشلت عملية استرداد مبلغ الطلب #:order.',
        'cancelled' => 'تم إلغاء استرداد مبلغ الطلب #:order.',
        'updated' => 'تم تحديث حالة استرداد مبلغ الطلب #:order.',
    ],
    'staff_added' => [
        'title' => 'إضافة إلى فريق متجر',
        'body' => 'تمت إضافتك إلى فريق متجر :store بصلاحية :role.',
    ],
    'vendor_document_submitted' => [
        'title' => 'وثيقة بائع بانتظار المراجعة',
        'body' => 'قدّم متجر :store وثيقة جديدة للمراجعة.',
    ],
    'vendor_document_reviewed' => [
        'title' => 'مراجعة وثيقة',
        'verified' => 'تم اعتماد وثيقة :document.',
        'rejected' => 'تم رفض وثيقة :document.',
    ],
    'product_document_submitted' => [
        'title' => 'وثيقة منتج بانتظار المراجعة',
        'body' => 'تم رفع وثيقة جديدة للمنتج :product.',
    ],
    'product_document_reviewed' => [
        'title' => 'مراجعة وثيقة منتج',
        'approved' => 'تم اعتماد وثيقة المنتج :product.',
        'rejected' => 'تم رفض وثيقة المنتج :product.',
    ],
    'discount_added' => [
        'title' => 'خصم على منتج',
        'body' => 'يتوفر خصم جديد على المنتج :product:discount.',
    ],
    'discount_updated' => [
        'title' => 'تحديث خصم منتج',
        'body' => 'تم تحديث الخصم على المنتج :product:discount.',
    ],
];
