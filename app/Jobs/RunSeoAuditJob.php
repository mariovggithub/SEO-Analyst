<?php

namespace App\Jobs;

use App\Enums\AuditCategory;
use App\Models\Audit;
use App\Models\AuditResult;
use App\Services\OnPageAnalyzer;
use App\Services\PageSpeedAnalyzer;
use App\Services\ScoreCalculator;
use App\Services\TechStackAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunSeoAuditJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public int $tries = 2;

    /**
     * @var array<int>
     */
    public array $backoff = [10, 30];

    public function __construct(
        public readonly string $auditId,
    ) {}

    public function handle(
        PageSpeedAnalyzer $pageSpeed,
        TechStackAnalyzer $techStack,
        OnPageAnalyzer $onPage,
        ScoreCalculator $scoreCalculator,
    ): void {
        $audit = Audit::find($this->auditId);

        if (! $audit) {
            Log::error('RunSeoAuditJob: Audit not found', ['audit_id' => $this->auditId]);

            return;
        }

        $audit->markAsProcessing();

        $url = $audit->url;
        $scores = [];

        // 1. PageSpeed Insights
        try {
            $perfResult = $pageSpeed->analyze($url);
            AuditResult::updateOrCreate(
                ['audit_id' => $audit->id, 'category' => AuditCategory::Performance],
                ['raw_data' => $perfResult['raw'], 'summary' => $perfResult['summary']],
            );
            $scores['performance'] = $perfResult['summary']['score'] ?? 0;
        } catch (\Throwable $e) {
            Log::error('RunSeoAuditJob: PageSpeed analysis failed', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
            ]);
            $scores['performance'] = 0;
        }

        // 2. Tech Stack (Wappalyzer)
        try {
            $techResult = $techStack->analyze($url);
            AuditResult::updateOrCreate(
                ['audit_id' => $audit->id, 'category' => AuditCategory::TechStack],
                ['raw_data' => $techResult['raw'], 'summary' => $techResult['summary']],
            );
            $scores['tech_stack'] = $techResult['summary']['score'] ?? 0;
        } catch (\Throwable $e) {
            Log::error('RunSeoAuditJob: TechStack analysis failed', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
            ]);
            $scores['tech_stack'] = 0;
        }

        // 3. On-Page SEO
        try {
            $onPageResult = $onPage->analyze($url);
            AuditResult::updateOrCreate(
                ['audit_id' => $audit->id, 'category' => AuditCategory::OnPage],
                ['raw_data' => $onPageResult['raw'], 'summary' => $onPageResult['summary']],
            );
            $scores['on_page'] = $onPageResult['summary']['score'] ?? 0;
        } catch (\Throwable $e) {
            Log::error('RunSeoAuditJob: OnPage analysis failed', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
            ]);
            $scores['on_page'] = 0;
        }

        // Calculate composite score and mark completed
        $overallScore = $scoreCalculator->calculate($scores);
        $audit->markAsCompleted($overallScore);
    }

    public function failed(?\Throwable $exception): void
    {
        $audit = Audit::find($this->auditId);

        if ($audit) {
            $audit->markAsFailed();
        }

        Log::error('RunSeoAuditJob: Job failed permanently', [
            'audit_id' => $this->auditId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}

