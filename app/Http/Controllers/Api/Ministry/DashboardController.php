<?php

namespace App\Http\Controllers\Api\Ministry;

use App\Http\Controllers\Api\Syndicate\DashboardController as SyndicateDashboardController;

/**
 * The syndicate read API over the Ministry's sector selection. Only the read
 * endpoints are routed (routes/api_ministry.php); syndicate-branded PDFs are not.
 */
class DashboardController extends SyndicateDashboardController
{
    use ResolvesMinistrySector;
}
