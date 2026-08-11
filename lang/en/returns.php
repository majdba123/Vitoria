<?php

return [

    'status' => [
        'requested' => 'Requested',
        'under_review' => 'Under review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'received' => 'Received',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'reason' => [
        'damaged_on_arrival' => 'Damaged on arrival',
        'wrong_item_received' => 'Wrong item received',
        'expired_product' => 'Expired product',
        'not_as_described' => 'Not as described',
        'missing_items' => 'Missing items',
        'quality_issue' => 'Quality issue',
        'other' => 'Other',
    ],

    'order_invalid' => 'This order does not belong to you.',
    'reason_invalid' => 'Please choose a valid return reason.',
    'order_not_returnable' => 'An order that is :status cannot be returned.',
    'items_required' => 'Select at least one item to return.',
    'quantity_invalid' => 'Return quantity must be at least 1.',
    'item_invalid' => 'That item is not part of this order.',
    'quantity_exceeds' => 'You cannot return more of :product than you purchased.',
    'transition_not_permitted' => 'You are not allowed to make this change.',
    'transition_invalid' => 'A return cannot move from :from to :to.',
    'transition_conflict' => 'This return was updated by someone else. Please reload and try again.',
    'transition_success' => 'Return status updated.',
    'requested_success' => 'Your return request has been submitted.',
    'cancelled_success' => 'Return cancelled.',
];
