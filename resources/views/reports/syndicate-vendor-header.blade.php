<table width="100%" style="border-bottom:1px solid #d7dee7;padding-bottom:8px;font-family:dejavusans">
    <tr>
        <td width="22%"><img src="{{ public_path('images/vetora-logo-transparent.png') }}" style="width:88px" alt="Vetora"></td>
        <td width="78%" style="text-align:{{ $isArabic ? 'right' : 'left' }}">
            @php
                $locale = $isArabic ? 'ar' : 'en';
                $domain = trans('reports.values.'.($data['scope']['domain'] ?? 'both'), [], $locale);
                $scope = $syndicate
                    ? str_replace(':domain', $domain, trans('reports.scope.syndicate', [], $locale))
                    : trans('reports.scope.admin', [], $locale);
            @endphp
            <strong style="font-size:13px">{{ trans('reports.vendor.labels.title', [], $locale) }}</strong><br>
            <span dir="auto" style="font-size:9px;color:#64748b">{{ $data['vendor']['store_name'] }} — {{ $scope }}</span>
        </td>
    </tr>
</table>
