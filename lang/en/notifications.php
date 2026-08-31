<?php

return [
    'new_product' => [
        'title' => 'New product',
        'body' => 'Now available: :product',
    ],
    'new_order' => [
        'title' => 'New order',
        'admin_body' => 'A new order was created. Order number: #:order',
        'vendor_body' => 'You have a new order. Order number: #:order',
    ],
    'order_status' => [
        'title' => 'Order update',
        'completed' => 'Order #:order has been completed.',
        'cancelled' => 'Order #:order has been cancelled.',
        'confirmed' => 'Order #:order has been confirmed.',
        'updated' => 'The status of order #:order was updated to :status.',
    ],
    'return_requested' => [
        'title' => 'Return request',
        'body' => 'A new return was requested for order #:order.',
    ],
    'return_status' => [
        'title' => 'Return update',
        'approved' => 'Your return request for order #:order was approved.',
        'rejected' => 'Your return request for order #:order was rejected.',
        'received' => 'The return for order #:order was received.',
        'completed' => 'The return for order #:order was completed.',
        'cancelled' => 'The return request for order #:order was cancelled.',
        'updated' => 'The return status for order #:order was updated.',
    ],
    'refund_status' => [
        'title' => 'Refund update',
        'pending' => 'A refund was created for order #:order.',
        'completed' => 'The amount for order #:order was refunded.',
        'failed' => 'The refund for order #:order failed.',
        'cancelled' => 'The refund for order #:order was cancelled.',
        'updated' => 'The refund status for order #:order was updated.',
    ],
    'staff_added' => [
        'title' => 'Added to a store team',
        'body' => 'You were added to the :store team with the :role role.',
    ],
    'vendor_document_submitted' => [
        'title' => 'Vendor document awaiting review',
        'body' => ':store submitted a new document for review.',
    ],
    'vendor_document_reviewed' => [
        'title' => 'Document review',
        'verified' => 'The :document document was verified.',
        'rejected' => 'The :document document was rejected.',
    ],
    'product_document_submitted' => [
        'title' => 'Product document awaiting review',
        'body' => 'A new document was uploaded for :product.',
    ],
    'product_document_reviewed' => [
        'title' => 'Product document review',
        'approved' => 'The document for :product was approved.',
        'rejected' => 'The document for :product was rejected.',
    ],
    'discount_added' => [
        'title' => 'Product discount',
        'body' => 'A new discount is available on :product:discount.',
    ],
    'discount_updated' => [
        'title' => 'Product discount updated',
        'body' => 'The discount on :product was updated:discount.',
    ],
];
