<?php

return [

    'type' => [
        'commercial_registration' => 'Commercial registration',
        'business_license' => 'Business license',
        'tax_registration' => 'Tax registration',
        'industry_license' => 'Industry license',
        'other' => 'Other',
    ],

    'status' => [
        'pending_review' => 'Pending review',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
        'suspended' => 'Suspended',
    ],

    'type_invalid' => 'Please choose a valid document type.',
    'status_invalid' => 'A document can only be marked verified or rejected.',
    'rejection_reason_required' => 'Please explain why this document is being rejected.',
    'transition_invalid' => 'This document cannot be reviewed from its current status.',
    'transition_conflict' => 'This document was already reviewed by someone else.',
    'uploaded_success' => 'Document submitted for review.',
    'reviewed_success' => 'Document review recorded.',
    'suspended_success' => 'Document suspended.',
    'not_found' => 'Document not found.',
];
