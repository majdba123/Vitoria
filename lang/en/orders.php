<?php

return [

    'status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'shipped' => 'Shipped',
        'out_for_delivery' => 'Out for delivery',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'cancel_reason' => [
        'customer_changed_mind' => 'Changed my mind',
        'wrong_order' => 'Ordered the wrong item',
        'unavailable_product' => 'Product unavailable',
        'vendor_issue' => 'Vendor issue',
        'delivery_issue' => 'Delivery issue',
        'payment_issue' => 'Payment issue',
        'duplicate_order' => 'Duplicate order',
        'other' => 'Other',
    ],

    'transition_not_permitted' => 'You are not allowed to make this change.',
    'transition_invalid' => 'An order cannot move from :from to :to.',
    'transition_conflict' => 'This order was updated by someone else. Please reload and try again.',
    'transition_success' => 'Order status updated.',
    'cancel_reason_invalid' => 'Please choose a valid cancellation reason.',
    'already_cancelled' => 'This order is already cancelled.',
    'not_cancellable' => 'An order that is :status can no longer be cancelled.',
    'cancelled_success' => 'Order cancelled.',
    'placed_success' => 'Your order has been placed.',
    'placed_success_multi' => 'Your order has been placed as :count separate vendor orders.',
    'timeline' => 'Order timeline',
    'order_placed' => 'Order placed',
];
