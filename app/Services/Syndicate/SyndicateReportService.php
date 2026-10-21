<?php

namespace App\Services\Syndicate;

use App\Models\Order;
use App\Models\Refund;
use App\Models\Syndicate;
use App\Support\DomainScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class SyndicateReportService
{
    public function __construct(private readonly SyndicateDashboardService $dashboard) {}

    /**
     * @param  array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null}  $period
     * @return array{bytes: string, filename: string, data: array<string, mixed>}
     */
    public function render(Syndicate|DomainScope $scope, array $period, string $locale): array
    {
        $scope = DomainScope::from($scope);
        $data = $this->data($scope, $period);
        $isArabic = $locale === 'ar';
        $temporaryDirectory = storage_path('app/tmp/mpdf');

        if (! is_dir($temporaryDirectory)) {
            mkdir($temporaryDirectory, 0755, true);
        }

        $pdf = new Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'directionality' => $isArabic ? 'rtl' : 'ltr',
            'default_font' => 'dejavusans', 'autoScriptToLang' => true, 'autoLangToFont' => true,
            'tempDir' => $temporaryDirectory, 'margin_top' => 18, 'margin_bottom' => 18,
        ]);
        $pdf->SetHTMLFooter('<div dir="ltr" style="direction:ltr;text-align:center;color:#64748b;font-size:9px">{PAGENO} / {nbpg}</div>');
        $reportTitle = $scope->syndicate ? null : __('ministry.report_title', [], $locale);
        $reportOrganization = $scope->syndicate?->name ?? __('ministry.report_organization', [], $locale);
        $pdf->WriteHTML(view('reports.syndicate-general', compact('data', 'scope', 'isArabic', 'reportTitle', 'reportOrganization'))->render(), HTMLParserMode::DEFAULT_MODE);
        $from = $data['period']['from'] ?? 'all';
        $to = $data['period']['to'] ?? now()->toDateString();

        return [
            'bytes' => $pdf->Output('', Destination::STRING_RETURN),
            'filename' => $scope->syndicate
                ? "vetora-syndicate-report-{$scope->syndicate->id}-{$from}-{$to}.pdf"
                : "vetora-ministry-{$scope->key}-report-{$from}-{$to}.pdf",
            'data' => $data,
        ];
    }

    /** @param array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null} $period */
    public function data(Syndicate|DomainScope $scope, array $period): array
    {
        $scope = DomainScope::from($scope);
        $types = $scope->types;
        $lines = $this->period($this->lines($types), $period, 'orders.created_at')
            ->where('orders.status', Order::STATUS_COMPLETED);
        $summary = (clone $lines)->selectRaw('COUNT(DISTINCT orders.vendor_id) vendors_with_sales, COUNT(DISTINCT orders.id) completed_orders, COALESCE(SUM(order_items.quantity), 0) units_sold, COALESCE(SUM(order_items.line_total), 0) gross_sales')->first();
        $vendors = $this->dashboard->vendorQuery($types);

        return [
            'scope' => ['domain' => $scope->key, 'syndicate' => $scope->syndicate?->name],
            'period' => ['from' => $period['from']?->toDateString(), 'to' => $period['to']?->toDateString()],
            'generated_at' => now(),
            'kpis' => [
                'vendors' => (clone $vendors)->count(),
                'active_vendors' => (clone $vendors)->where('vendors.is_active', true)->count(),
                'completed_orders' => (int) ($summary?->completed_orders ?? 0),
                'units_sold' => (int) ($summary?->units_sold ?? 0),
                'gross_sales' => round((float) ($summary?->gross_sales ?? 0), 2),
                'refunds' => $this->refunds($types, $period),
            ],
            'top_vendors' => $this->vendorPerformance($types, $period)->take(10),
            'vendor_performance' => $this->vendorPerformance($types, $period),
            'top_products' => (clone $lines)->selectRaw('products.id, products.name_ar, products.name_en, SUM(order_items.quantity) units_sold, SUM(order_items.line_total) gross_sales')->groupBy('products.id', 'products.name_ar', 'products.name_en')->orderByDesc('gross_sales')->limit(10)->get(),
            'categories' => (clone $lines)->selectRaw('COALESCE(snapshot_categories.name, categories.name) name, SUM(order_items.quantity) units_sold, SUM(order_items.line_total) gross_sales')->groupByRaw('COALESCE(snapshot_categories.name, categories.name)')->orderByDesc('gross_sales')->get(),
            'trend' => (clone $lines)->selectRaw('DATE(orders.created_at) sale_date, COUNT(DISTINCT orders.id) completed_orders, SUM(order_items.line_total) gross_sales')->groupByRaw('DATE(orders.created_at)')->orderBy('sale_date')->get(),
            'geography' => (clone $lines)->leftJoin('vendors', 'vendors.id', '=', 'orders.vendor_id')->leftJoin('cities', 'cities.id', '=', 'vendors.city_id')->selectRaw('cities.name city, COUNT(DISTINCT vendors.id) vendors, SUM(order_items.line_total) gross_sales')->groupBy('cities.name')->orderByDesc('gross_sales')->get(),
        ];
    }

    private function lines(array $types): Builder
    {
        return DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('categories as snapshot_categories', 'snapshot_categories.id', '=', 'order_items.category_id_snapshot')
            ->whereIn(DB::raw('COALESCE(order_items.category_type, snapshot_categories.type, categories.type)'), $types);
    }

    /** @param array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null} $period */
    private function vendorPerformance(array $types, array $period): \Illuminate\Support\Collection
    {
        return $this->period($this->lines($types), $period, 'orders.created_at')->where('orders.status', Order::STATUS_COMPLETED)
            ->join('vendors', 'vendors.id', '=', 'orders.vendor_id')->leftJoin('cities', 'cities.id', '=', 'vendors.city_id')
            ->selectRaw('vendors.id, vendors.store_name, cities.name city, COUNT(DISTINCT orders.id) completed_orders, SUM(order_items.quantity) units_sold, SUM(order_items.line_total) gross_sales')
            ->groupBy('vendors.id', 'vendors.store_name', 'cities.name')->orderByDesc('gross_sales')->get();
    }

    /** @param array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null} $period */
    private function refunds(array $types, array $period): float
    {
        $query = DB::table('return_items')->join('order_returns', 'order_returns.id', '=', 'return_items.order_return_id')
            ->join('refunds', 'refunds.order_return_id', '=', 'order_returns.id')
            ->join('order_items', 'order_items.id', '=', 'return_items.order_item_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('categories as snapshot_categories', 'snapshot_categories.id', '=', 'order_items.category_id_snapshot')
            ->where('refunds.status', Refund::STATUS_COMPLETED)
            ->whereIn(DB::raw('COALESCE(order_items.category_type, snapshot_categories.type, categories.type)'), $types);
        $this->period($query, $period, 'refunds.completed_at');

        return round((float) $query->sum('return_items.line_total'), 2);
    }

    /** @param array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null} $period */
    private function period(Builder $query, array $period, string $column): Builder
    {
        return $query
            ->when($period['from'], fn (Builder $builder, CarbonImmutable $from) => $builder->where($column, '>=', $from))
            ->when($period['to'], fn (Builder $builder, CarbonImmutable $to) => $builder->where($column, '<=', $to));
    }
}
