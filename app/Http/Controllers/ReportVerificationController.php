<?php

namespace App\Http\Controllers;

use App\Models\Syndicate;
use App\Support\DomainScope;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public landing page for the QR code / verification link printed on every
 * generated syndicate and Ministry PDF report. The route is protected by
 * Laravel's `signed` middleware, so any edited query parameter (scope,
 * period, reference, ...) invalidates the signature and the request never
 * reaches this controller. Only non-sensitive metadata is rendered here —
 * no vendor, order or financial detail — so the link is safe to publish on
 * a report that may be handed to a third party.
 */
class ReportVerificationController extends Controller
{
    public function show(Request $request): View
    {
        $locale = $request->query('locale') === 'en' ? 'en' : 'ar';
        $isArabic = $locale === 'ar';
        $syndicateId = $request->query('syndicate');
        $sector = $request->query('scope');

        $isMinistry = ! $syndicateId;
        $scopeLabel = null;

        if ($isMinistry) {
            abort_unless(in_array($sector, DomainScope::sectors(), true), 404);
            $organization = __('ministry.report_organization', [], $locale);
            $scopeLabel = trans('reports.values.'.($sector === DomainScope::COMBINED ? 'both' : $sector), [], $locale);
        } else {
            $syndicate = Syndicate::query()->find($syndicateId);
            abort_unless($syndicate, 404);
            $organization = $syndicate->name;
            $scopeLabel = trans('reports.verify.syndicate_report', [], $locale);
        }

        $generatedAtTimestamp = $request->query('generated_at');

        return view('reports.verify', [
            'isArabic' => $isArabic,
            'organization' => $organization,
            'scopeLabel' => $scopeLabel,
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'generatedAt' => is_numeric($generatedAtTimestamp) ? CarbonImmutable::createFromTimestamp((int) $generatedAtTimestamp) : null,
            'reference' => (string) $request->query('ref'),
        ]);
    }
}
