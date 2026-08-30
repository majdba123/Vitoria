<?php

namespace App\Services\Vendor;

use App\Models\Syndicate;
use App\Models\Vendor;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class SyndicateVendorPdfService
{
    public function __construct(private readonly VendorAnalyticsService $analytics) {}

    /**
     * @param  array{key: string, from: \Carbon\CarbonImmutable|null, to: \Carbon\CarbonImmutable|null}  $period
     * @return array{bytes: string, filename: string, data: array<string, mixed>}
     */
    public function render(Vendor $vendor, ?Syndicate $syndicate, array $period, string $locale): array
    {
        $data = $this->analytics->report($vendor, $period, $syndicate?->type);
        $isArabic = $locale === 'ar';
        $temporaryDirectory = storage_path('app/tmp/mpdf');

        if (! is_dir($temporaryDirectory)) {
            mkdir($temporaryDirectory, 0755, true);
        }

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'directionality' => $isArabic ? 'rtl' : 'ltr',
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => $temporaryDirectory,
            'margin_top' => 25,
            'margin_bottom' => 18,
        ]);
        $pdf->SetHTMLHeader(view('reports.syndicate-vendor-header', compact('data', 'syndicate', 'isArabic'))->render());
        $pdf->SetHTMLFooter('<div style="text-align:center;color:#64748b;font-size:9px">{PAGENO} / {nbpg}</div>');
        $pdf->WriteHTML(view('reports.syndicate-vendor', compact('data', 'syndicate', 'isArabic'))->render(), HTMLParserMode::DEFAULT_MODE);

        $from = $data['scope']['period']['from'] ?? 'all';
        $to = $data['scope']['period']['to'] ?? now()->toDateString();

        return [
            'bytes' => $pdf->Output('', Destination::STRING_RETURN),
            'filename' => "vetora-vendor-report-{$vendor->id}-{$from}-{$to}.pdf",
            'data' => $data,
        ];
    }
}
