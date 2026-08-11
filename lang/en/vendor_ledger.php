<?php

return [

    'entry' => [
        'sale' => 'Sale — Order #:order',
        'commission' => 'Platform commission — Order #:order',
        'refund' => 'Refund — Order #:order',
        'settlement' => 'Settlement payout',
    ],

    'type' => [
        'sale' => 'Sale',
        'commission' => 'Commission',
        'refund' => 'Refund',
        'adjustment' => 'Adjustment',
        'settlement' => 'Settlement',
    ],

    'method' => [
        'bank_transfer' => 'Bank transfer',
        'cash' => 'Cash',
        'other' => 'Other',
    ],

    'settlement_amount_invalid' => 'The settlement amount must be greater than zero.',
    'settlement_method_invalid' => 'Please choose a valid settlement method.',
    'settlement_exceeds_outstanding' => 'The settlement amount exceeds this vendor\'s outstanding balance.',
    'settlement_recorded' => 'Settlement recorded.',
    'adjustment_recorded' => 'Adjustment recorded.',
];
