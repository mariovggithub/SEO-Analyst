<?php

namespace App\Http\Controllers;

use App\Enums\AuditStatus;
use App\Models\Audit;
use App\Support\AuditReportPresenter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Wayfinder\Attributes\Wayfinder;

class AuditPdfController extends Controller
{
    #[Wayfinder]
    public function __invoke(Audit $audit): Response
    {
        abort_unless($audit->created_by === Auth::id(), 403);
        abort_unless($audit->status === AuditStatus::Completed, 422, 'Audit is not yet completed.');

        $audit->load('results');

        $results = $audit->results
            ->keyBy(fn ($r) => $r->category->value)
            ->map(fn ($r) => $r->summary);

        $html = view('pdf.audit-report', [
            'audit' => $audit,
            'results' => $results,
            'report' => AuditReportPresenter::make($audit, $results),
        ])->render();

        // In production with Puppeteer / Spatie Browsershot:
        if (class_exists(\Spatie\Browsershot\Browsershot::class) && request()->query('format') !== 'html') {
            try {
                $pdf = \Spatie\Browsershot\Browsershot::html($html)
                    ->format('A4')
                    ->margins(10, 10, 10, 10)
                    ->pdf();

                return response($pdf, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => "inline; filename=\"seo-report-{$audit->domain}.pdf\"",
                ]);
            } catch (\Throwable) {
                // Fall back to printable HTML view
            }
        }

        return response($html, 200, [
            'Content-Type' => 'text/html',
        ]);
    }
}

