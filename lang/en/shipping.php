<?php

return [

    'status' => [
        'pending' => 'Pending',
        'preparing' => 'Preparing',
        'shipped' => 'Shipped',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'failed' => 'Delivery failed',
        'returned' => 'Returned to vendor',
    ],

    'method' => [
        'standard_delivery' => 'Standard delivery',
        'express_delivery' => 'Express delivery',
        'vendor_delivery' => 'Vendor delivery',
    ],

    'method_invalid' => 'Please choose a valid shipping method.',
    'transition_invalid' => 'A shipment cannot move from :from to :to.',
    'transition_conflict' => 'This shipment was updated by someone else. Please reload and try again.',
    'transition_success' => 'Shipment status updated.',
    'failure_reason_required' => 'Please describe why the delivery attempt failed.',
];
