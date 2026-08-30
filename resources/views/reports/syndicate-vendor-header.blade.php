<table width="100%" style="border-bottom:1px solid #d7dee7;padding-bottom:8px;font-family:dejavusans">
    <tr>
        <td width="22%"><img src="{{ public_path('images/vetora-logo-transparent.png') }}" style="width:88px" alt="Vetora"></td>
        <td width="78%" style="text-align:{{ $isArabic ? 'right' : 'left' }}">
            <strong style="font-size:13px">{{ $isArabic ? 'تقرير أداء التاجر' : 'Vendor Performance Report' }}</strong><br>
            <span dir="auto" style="font-size:9px;color:#64748b">{{ $data['vendor']['store_name'] }} — {{ $isArabic ? 'النقابة '.($data['scope']['domain'] === 'veterinary' ? 'البيطرية' : 'الزراعية') : $syndicate->name }}</span>
        </td>
    </tr>
</table>
