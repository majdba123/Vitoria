<?php

namespace App\Http\Controllers\Api\Ministry;

use App\Http\Controllers\Api\Syndicate\VendorAnalyticsController as SyndicateVendorAnalyticsController;

class VendorAnalyticsController extends SyndicateVendorAnalyticsController
{
    use ResolvesMinistrySector;
}
