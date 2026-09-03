<?php

namespace App\Http\Controllers;

use App\Support\AdminPjmCoverageReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPjmReportController extends Controller
{
    public function __invoke(
        Request $request,
        AdminPjmCoverageReportService $reportService,
        string $format,
    ): Response {
        abort_unless($request->user()?->isAdministrator(), 403);
        abort_unless(in_array($format, ['json', 'csv'], true), 404);

        $validated = $request->validate([
            'external_id' => ['nullable', 'string', 'max:64'],
        ]);
        $externalId = filled($validated['external_id'] ?? null)
            ? trim((string) $validated['external_id'])
            : null;
        $report = $reportService->build($externalId, 100);
        $filename = 'pjm-coverage-'.now()->format('Ymd-His').'.'.$format;

        if ($format === 'csv') {
            return response($reportService->toCsv($report), 200, [
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return response(
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
            200,
            [
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
        );
    }
}
