<?php

namespace App\Http\Controllers\Api\Ministry;

use App\Support\DomainScope;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ResolvesMinistrySector
{
    /**
     * The Ministry reads `?sector=combined|agriculture|veterinary`
     * (combined when omitted); anything else is rejected, never widened.
     */
    protected function scope(Request $request): DomainScope
    {
        $sector = $request->query('sector', DomainScope::COMBINED);

        if (! is_string($sector) || ! in_array($sector, DomainScope::sectors(), true)) {
            throw ValidationException::withMessages(['sector' => __('The selected sector is invalid.')]);
        }

        return DomainScope::forSector($sector);
    }
}
