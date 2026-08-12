<?php

return [

    'role' => [
        'owner' => 'Owner',
        'manager' => 'Manager',
        'catalog_manager' => 'Catalog Manager',
        'order_manager' => 'Order Manager',
        'finance' => 'Finance',
        'viewer' => 'Viewer',
    ],

    'status' => [
        'active' => 'Active',
        'removed' => 'Removed',
    ],

    'role_invalid' => 'Please choose a valid staff role.',
    'user_not_found' => 'No account was found with that email or phone number. Ask them to register first.',
    'already_owner' => 'This user already owns this store.',
    'owns_another_vendor' => 'This user already owns a different store and cannot also be added as staff.',
    'already_member' => 'This user is already a member of this store.',
    'staff_elsewhere' => 'This user is already active staff at another store.',
    'added_success' => 'Staff member added.',
    'role_updated_success' => 'Staff role updated.',
    'removed_success' => 'Staff member removed.',
];
