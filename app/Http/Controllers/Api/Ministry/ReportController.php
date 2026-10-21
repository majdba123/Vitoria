<?php

namespace App\Http\Controllers\Api\Ministry;

use App\Http\Controllers\Controller;
use App\Http\Requests\MinistryReportRequest;
use App\Services\AuditLogService;
use App\Services\Syndicate\SyndicateReportService;
use App\Support\DomainScope;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private readonly SyndicateReportService $reportService) {}

    public function __invoke(MinistryReportRequest $request): Response
    {
        $scope = DomainScope::forSector($request->validated('sector', 'combined'));
        $report = $this->reportService->render($scope, $request->period(), $request->validated('locale'));
        app(AuditLogService::class)->record($request->user(), 'export.ministry_report', 'MinistryReport', null, null, [
            'range' => $request->period()['key'],
            'locale' => $request->validated('locale'),
            'sector' => $scope->key,
            'format' => 'pdf',
        ]);

        return response($report['bytes'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$report['filename'].'"',
            'Content-Length' => (string) strlen($report['bytes']),
            'X-Content-Type-Options' => 'nosniff',
            'X-Vetora-Report-Domain' => $scope->key,
        ]);
    }
}
