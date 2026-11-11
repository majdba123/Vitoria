<?php

namespace App\Http\Controllers;

use App\Models\Syndicate;
use App\Support\DomainScope;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public landing page for the QR code printed on every generated syndicate
 * and Ministry PDF report (see SyndicateReportService::verifyUrl()).
 *
 * The link is HMAC-signed with APP_KEY. Any edited, truncated or forged
 * query string fails the signature check and gets an explicit "could not be
 * verified" page (HTTP 403) instead of the verified details. A valid link
 * only echoes what it carries — organisation, scope, period, issue time,
 * reference and the headline figures already printed on the report — so it
 * never discloses vendor, order or other business records.
 */
class ReportVerificationController extends Controller
{
    public function show(Request $request): Response
    {
        $locale = $request->query('locale') === 'en' ? 'en' : 'ar';

        if (! $request->hasValidSignature()) {
            return $this->failed($locale);
        }

        $syndicateId = $request->query('syndicate');

        if ($syndicateId) {
            $syndicate = Syndicate::query()->find($syndicateId);

            if (! $syndicate) {
                return $this->failed($locale);
            }

            $organization = $syndicate->name;
            $scopeLabel = trans('reports.verify.syndicate_report', [], $locale);
        } else {
            $sector = $request->query('scope');

            if (! in_array($sector, DomainScope::sectors(), true)) {
                return $this->failed($locale);
            }

            $organization = trans('ministry.report_organization', [], $locale);
            $scopeLabel = trans('reports.values.'.($sector === DomainScope::COMBINED ? 'both' : $sector), [], $locale);
        }

        $generatedAtTimestamp = $request->query('generated_at');

        return response()->view('reports.verify', [
            'verified' => true,
            'isArabic' => $locale === 'ar',
            'locale' => $locale,
            'organization' => $organization,
            'scopeLabel' => $scopeLabel,
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'generatedAt' => is_numeric($generatedAtTimestamp)
                ? CarbonImmutable::createFromTimestamp((int) $generatedAtTimestamp)->setTimezone(config('app.timezone'))
                : null,
            'reference' => (string) $request->query('ref'),
            'figures' => $this->figures($request, $locale),
        ]);
    }

    /**
     * Headline figures carried by the signed link, formatted exactly as the
     * PDF prints them so a reader can compare them side by side.
     *
     * @return array<string, string>
     */
    private function figures(Request $request, string $locale): array
    {
        $labels = trans('reports.general.labels', [], $locale);
        $currency = trans('reports.currency', [], $locale);
        $figures = [];

        if (is_numeric($request->query('vendors'))) {
            $figures[$labels['vendors']] = (string) (int) $request->query('vendors');
        }

        if (is_numeric($request->query('orders'))) {
            $figures[$labels['orders']] = (string) (int) $request->query('orders');
        }

        if (is_numeric($request->query('sales'))) {
            $amount = number_format((float) $request->query('sales'), 2);
            $figures[$labels['sales']] = $locale === 'ar' ? "{$amount} {$currency}" : "{$currency} {$amount}";
        }

        return $figures;
    }

    private function failed(string $locale): Response
    {
        return response()->view('reports.verify', [
            'verified' => false,
            'isArabic' => $locale === 'ar',
            'locale' => $locale,
        ], 403);
    }
}
