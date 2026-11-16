<?php

namespace App\Http\Controllers;

use App\Enums\AuditStatus;
use App\Http\Requests\StoreAuditRequest;
use App\Jobs\RunSeoAuditJob;
use App\Models\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Wayfinder\Attributes\Wayfinder;

class AuditController extends Controller
{
    #[Wayfinder]
    public function index(): Response
    {
        $rawAudits = Audit::query()
            ->where('created_by', Auth::id())
            ->latest()
            ->get();

        $completedAudits = $rawAudits->filter(fn (Audit $a) => $a->status === AuditStatus::Completed);

        $stats = [
            'total' => $rawAudits->count(),
            'completed' => $completedAudits->count(),
            'processing' => $rawAudits->filter(fn (Audit $a) => in_array($a->status, [AuditStatus::Pending, AuditStatus::Processing], true))->count(),
            'avg_score' => $completedAudits->isNotEmpty() ? (int) round((float) $completedAudits->avg('overall_score')) : null,
        ];

        $audits = $rawAudits->map(fn (Audit $audit) => [
            'id' => $audit->id,
            'url' => $audit->url,
            'domain' => $audit->domain,
            'status' => $audit->status->value,
            'status_label' => $audit->status->label(),
            'status_color' => $audit->status->color(),
            'overall_score' => $audit->overall_score,
            'created_at' => $audit->created_at->diffForHumans(),
            'created_at_full' => $audit->created_at->toDateTimeString(),
        ]);

        return Inertia::render('Audits/Index', [
            'audits' => $audits,
            'stats' => $stats,
        ]);
    }

    #[Wayfinder]
    public function store(StoreAuditRequest $request): RedirectResponse
    {
        $audit = Audit::create([
            'url' => $request->validated('url'),
            'domain' => $request->domain(),
            'created_by' => Auth::id(),
        ]);

        RunSeoAuditJob::dispatch($audit->id);

        return redirect()->route('audits.show', $audit)
            ->with('flash', [
                'type' => 'success',
                'message' => "Audit started for {$audit->domain}. Results will appear shortly.",
            ]);
    }

    #[Wayfinder]
    public function show(Audit $audit): Response
    {
        $this->authorizeAudit($audit);

        $audit->load('results');

        $results = $audit->results
            ->keyBy(fn ($r) => $r->category->value)
            ->map(fn ($r) => [
                'category' => $r->category->value,
                'category_label' => $r->category->label(),
                'summary' => $r->summary,
            ]);

        return Inertia::render('Audits/Show', [
            'audit' => [
                'id' => $audit->id,
                'url' => $audit->url,
                'domain' => $audit->domain,
                'status' => $audit->status->value,
                'status_label' => $audit->status->label(),
                'status_color' => $audit->status->color(),
                'overall_score' => $audit->overall_score,
                'created_at' => $audit->created_at->diffForHumans(),
                'created_at_full' => $audit->created_at->toDateTimeString(),
            ],
            'results' => $results,
        ]);
    }

    #[Wayfinder]
    public function destroy(Audit $audit): RedirectResponse
    {
        $this->authorizeAudit($audit);

        $audit->delete();

        return redirect()->route('audits.index')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Audit deleted successfully.',
            ]);
    }

    private function authorizeAudit(Audit $audit): void
    {
        abort_unless($audit->created_by === Auth::id(), 403);
    }
}

