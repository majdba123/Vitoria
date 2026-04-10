<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;

class VendorController extends Controller
{
    /**
     * Public vendor listings are disabled — storefront is marketplace-only.
     */
    public function index(): never
    {
        abort(404, __('Not found.'));
    }

    /**
     * Public vendor profile is disabled — storefront is marketplace-only.
     */
    public function show(Vendor $vendor): never
    {
        abort(404, __('Not found.'));
    }
}
