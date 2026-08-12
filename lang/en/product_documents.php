<?php

return [

    'type' => [
        'leaflet' => 'Leaflet',
        'label' => 'Label',
        'safety_data_sheet' => 'Safety data sheet',
        'registration_certificate' => 'Registration certificate',
        'manufacturer_document' => 'Manufacturer document',
        'other' => 'Other',
    ],

    'status' => [
        'pending_review' => 'Pending review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'disabled' => 'Disabled',
    ],

    'type_invalid' => 'Please choose a valid document type.',
    'status_invalid' => 'A document can only be marked approved or rejected.',
    'rejection_reason_required' => 'Please explain why this document is being rejected.',
    'transition_invalid' => 'This document cannot be reviewed from its current status.',
    'transition_conflict' => 'This document was already reviewed by someone else.',
    'uploaded_success' => 'Document submitted for review.',
    'reviewed_success' => 'Document review recorded.',
    'disabled_success' => 'Document disabled.',
    'not_found' => 'Document not found.',
];
