<?php

return [

    'status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ],

    'return_not_ready' => 'This return must be received before a refund can be issued.',
    'duplicate' => 'A refund already exists for this return.',
    'no_payment' => 'This order has no payment record to refund against.',
    'amount_invalid' => 'The refund amount must be greater than zero.',
    'amount_exceeds' => 'The refund amount exceeds what remains available to refund.',
    'reason_invalid' => 'Please choose a valid refund reason.',
    'already_finalized' => 'This refund has already been completed or cancelled.',
    'initiated_success' => 'Refund initiated.',
    'completed_success' => 'Refund completed.',
    'cancelled_success' => 'Refund cancelled.',
];
