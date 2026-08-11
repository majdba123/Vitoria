<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | ISO 4217 code the platform trades in. Orders persist this at creation so
    | historical orders remain interpretable if a second currency is ever added
    | (decision D6). No FX conversion is performed anywhere in the application.
    |
    */

    'currency' => env('VETORA_CURRENCY', 'SYP'),

    /*
    |--------------------------------------------------------------------------
    | Tax
    |--------------------------------------------------------------------------
    |
    | Orders carry a `tax_total` column so adding tax later is not an order
    | schema migration. The rate is zero until the business supplies a real
    | one — no VAT rate is invented (decision D7).
    |
    */

    'tax_rate' => (float) env('VETORA_TAX_RATE', 0),

];
