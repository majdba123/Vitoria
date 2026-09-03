<?php
    $locale = $isArabic ? 'ar' : 'en';
    $domain = trans('reports.values.'.($data['scope']['domain'] ?? 'both'), [], $locale);
    $scope = $syndicate
        ? str_replace(':domain', $domain, trans('reports.scope.syndicate', [], $locale))
        : trans('reports.scope.admin', [], $locale);
    $logoPath = $syndicate && $syndicate->logo ? \Illuminate\Support\Facades\Storage::disk('public')->path($syndicate->logo) : null;
    if (! $logoPath || ! is_file($logoPath)) {
        $logoPath = public_path('images/vetora-logo-transparent.png');
    }
    $reportPeriod = trim(($data['scope']['period']['from'] ?? '').' – '.($data['scope']['period']['to'] ?? ''), ' –');
?>
<table width="100%" style="border-bottom:1.5px solid #173f35;padding-bottom:7px;font-family:dejavusans">
    <tr>
        <td width="16%" style="vertical-align:middle"><img src="{{ $logoPath }}" style="max-width:72px;max-height:52px;width:auto;height:auto" alt="{{ $syndicate->name ?? 'Vetora' }}"></td>
        <td width="84%" style="text-align:{{ $isArabic ? 'right' : 'left' }};vertical-align:middle">
            <strong style="font-size:13px;color:#173f35">{{ trans('reports.vendor.labels.title', [], $locale) }}</strong>
            <span style="font-size:9px;color:#64748b">&nbsp;·&nbsp;{{ $reportPeriod ?: trans('reports.vendor.labels.not_available', [], $locale) }}</span><br>
            <span dir="auto" style="font-size:9.5px;color:#334155">{{ $data['vendor']['store_name'] }} — {{ $scope }}</span>
        </td>
    </tr>
</table>
