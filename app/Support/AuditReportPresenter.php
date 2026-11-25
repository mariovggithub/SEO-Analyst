<?php

namespace App\Support;

use App\Models\Audit;
use Illuminate\Support\Collection;

class AuditReportPresenter
{
    /**
     * @var array<string, array{label: string, name: string, target: string, thresholds: array{0: float|int, 1: float|int}}>
     */
    private const CWV_DEFINITIONS = [
        'fcp' => ['label' => 'FCP', 'name' => 'First Contentful Paint', 'target' => '≤ 1.8 s', 'thresholds' => [1800, 3000]],
        'lcp' => ['label' => 'LCP', 'name' => 'Largest Contentful Paint', 'target' => '≤ 2.5 s', 'thresholds' => [2500, 4000]],
        'cls' => ['label' => 'CLS', 'name' => 'Cumulative Layout Shift', 'target' => '≤ 0.100', 'thresholds' => [0.1, 0.25]],
        'tbt' => ['label' => 'TBT', 'name' => 'Total Blocking Time', 'target' => '≤ 200 ms', 'thresholds' => [200, 600]],
        'si' => ['label' => 'Speed Index', 'name' => 'Visual Load Speed', 'target' => '≤ 3.4 s', 'thresholds' => [3400, 5800]],
        'tti' => ['label' => 'TTI', 'name' => 'Time to Interactive', 'target' => '≤ 3.8 s', 'thresholds' => [3800, 7300]],
    ];

    /**
     * @param  array<string, mixed>|null  $performance
     * @param  array<string, mixed>|null  $onPage
     * @param  array<string, mixed>|null  $techStack
     * @param  array<int, array{tone: string, category: string, title: string, detail: string}>  $findings
     */
    public function __construct(
        public readonly ?array $performance,
        public readonly ?array $onPage,
        public readonly ?array $techStack,
        public readonly array $findings,
    ) {}

    /**
     * Build a presenter instance from an audit and keyed category summaries.
     *
     * @param  Collection<string, array<string, mixed>|null>  $results
     */
    public static function make(Audit $audit, Collection $results): self
    {
        /** @var array<string, mixed>|null $performance */
        $performance = $results->get('performance');
        /** @var array<string, mixed>|null $onPage */
        $onPage = $results->get('on_page');
        /** @var array<string, mixed>|null $techStack */
        $techStack = $results->get('tech_stack');

        return new self(
            performance: $performance,
            onPage: $onPage,
            techStack: $techStack,
            findings: self::buildFindings($audit, $performance, $onPage, $techStack),
        );
    }

    /**
     * Resolve semantic status tone and label for a 0-100 score.
     *
     * @return array{tone: string, label: string}
     */
    public function scoreStatus(mixed $score): array
    {
        if (! is_numeric($score)) {
            return ['tone' => 'neutral', 'label' => 'N/A'];
        }

        $value = (int) round((float) $score);

        if ($value >= 90) {
            return ['tone' => 'pass', 'label' => 'Healthy'];
        }

        if ($value >= 50) {
            return ['tone' => 'warn', 'label' => 'Needs Work'];
        }

        return ['tone' => 'fail', 'label' => 'Critical'];
    }

    /**
     * Compute SVG ring geometry and status metadata for a 0-100 score.
     *
     * @return array{value: string, percent: int, dasharray: string, dashoffset: string, tone: string, label: string}
     */
    public function scoreRing(mixed $score, int $radius = 34): array
    {
        $status = $this->scoreStatus($score);
        $circumference = 2 * M_PI * $radius;
        $percent = is_numeric($score) ? max(0, min(100, (int) round((float) $score))) : 0;
        $offset = $circumference - ($percent / 100) * $circumference;

        return [
            'value' => is_numeric($score) ? (string) $percent : '—',
            'percent' => $percent,
            'dasharray' => number_format($circumference, 2, '.', ''),
            'dashoffset' => number_format($offset, 2, '.', ''),
            'tone' => $status['tone'],
            'label' => $status['label'],
        ];
    }

    /**
     * Build structured Core Web Vitals rows comparing mobile and desktop strategies.
     *
     * @return array<int, array{key: string, label: string, name: string, target: string, mobile: string, desktop: string, mobileStatus: array{tone: string, label: string}, desktopStatus: array{tone: string, label: string}}>
     */
    public function cwvRows(): array
    {
        $mobileVitals = is_array($this->performance['mobile']['core_web_vitals'] ?? null)
            ? $this->performance['mobile']['core_web_vitals']
            : [];
        $desktopVitals = is_array($this->performance['desktop']['core_web_vitals'] ?? null)
            ? $this->performance['desktop']['core_web_vitals']
            : [];

        $rows = [];
        foreach (self::CWV_DEFINITIONS as $key => $meta) {
            $mobileVal = $mobileVitals[$key] ?? null;
            $desktopVal = $desktopVitals[$key] ?? null;

            $rows[] = [
                'key' => $key,
                'label' => $meta['label'],
                'name' => $meta['name'],
                'target' => $meta['target'],
                'mobile' => $this->formatCwv($key, $mobileVal),
                'desktop' => $this->formatCwv($key, $desktopVal),
                'mobileStatus' => $this->cwvStatus($key, $mobileVal),
                'desktopStatus' => $this->cwvStatus($key, $desktopVal),
            ];
        }

        return $rows;
    }

    public function formatCwv(string $metric, mixed $value): string
    {
        if (! is_numeric($value)) {
            return '—';
        }

        $numeric = (float) $value;

        if ($metric === 'cls') {
            return number_format($numeric, 3);
        }

        if ($numeric < 1000) {
            return (string) round($numeric).' ms';
        }

        return number_format($numeric / 1000, 1).' s';
    }

    /**
     * @return array{tone: string, label: string}
     */
    public function cwvStatus(string $metric, mixed $value): array
    {
        if (! is_numeric($value) || ! isset(self::CWV_DEFINITIONS[$metric])) {
            return ['tone' => 'neutral', 'label' => 'N/A'];
        }

        [$good, $poor] = self::CWV_DEFINITIONS[$metric]['thresholds'];
        $numeric = (float) $value;

        if ($numeric <= $good) {
            return ['tone' => 'pass', 'label' => 'Good'];
        }

        if ($numeric <= $poor) {
            return ['tone' => 'warn', 'label' => 'Needs Work'];
        }

        return ['tone' => 'fail', 'label' => 'Poor'];
    }

    public function sourceLabel(?string $source): string
    {
        return match ($source) {
            'google_pagespeed_api' => 'Google PageSpeed API',
            'synthetic_engine' => 'Synthetic Engine',
            default => 'Audit Engine',
        };
    }

    /**
     * Derive prioritized executive findings from audit category summaries.
     *
     * @param  array<string, mixed>|null  $performance
     * @param  array<string, mixed>|null  $onPage
     * @param  array<string, mixed>|null  $techStack
     * @return array<int, array{tone: string, category: string, title: string, detail: string}>
     */
    private static function buildFindings(Audit $audit, ?array $performance, ?array $onPage, ?array $techStack): array
    {
        $items = [];

        if (is_array($performance)) {
            $mobilePerf = $performance['mobile']['scores']['performance'] ?? null;
            if (is_numeric($mobilePerf)) {
                $score = (int) round((float) $mobilePerf);
                if ($score < 50) {
                    $items[] = [
                        'tone' => 'fail',
                        'category' => 'Performance',
                        'title' => "Mobile performance is critical ({$score}/100)",
                        'detail' => 'Slow mobile loading directly impacts bounce rate and Core Web Vitals rankings.',
                    ];
                } elseif ($score < 90) {
                    $items[] = [
                        'tone' => 'warn',
                        'category' => 'Performance',
                        'title' => "Mobile performance has room to improve ({$score}/100)",
                        'detail' => 'Optimize render-blocking assets, images, and JavaScript execution time.',
                    ];
                } else {
                    $items[] = [
                        'tone' => 'pass',
                        'category' => 'Performance',
                        'title' => "Strong mobile performance ({$score}/100)",
                        'detail' => 'Page meets high-speed Lighthouse benchmarks on mobile devices.',
                    ];
                }
            }

            $lcp = $performance['mobile']['core_web_vitals']['lcp'] ?? null;
            if (is_numeric($lcp) && (float) $lcp > 2500) {
                $seconds = number_format(((float) $lcp) / 1000, 1);
                $items[] = [
                    'tone' => ((float) $lcp > 4000) ? 'fail' : 'warn',
                    'category' => 'Core Web Vitals',
                    'title' => "Mobile LCP is {$seconds}s (target ≤ 2.5s)",
                    'detail' => 'Preload the hero image/font and reduce server response latency.',
                ];
            }
        }

        if (is_array($onPage)) {
            $titleLen = (int) ($onPage['meta']['title_length'] ?? 0);
            if ($titleLen === 0) {
                $items[] = [
                    'tone' => 'fail',
                    'category' => 'On-Page',
                    'title' => 'Missing page title tag',
                    'detail' => 'Add a unique, descriptive 30–60 character <title> tag for search snippets.',
                ];
            } elseif ($titleLen < 30 || $titleLen > 60) {
                $items[] = [
                    'tone' => 'warn',
                    'category' => 'On-Page',
                    'title' => "Title length is {$titleLen} chars (target 30–60)",
                    'detail' => 'Adjust title length so search engines display it cleanly without truncation.',
                ];
            }

            $descLen = (int) ($onPage['meta']['description_length'] ?? 0);
            if ($descLen === 0) {
                $items[] = [
                    'tone' => 'fail',
                    'category' => 'On-Page',
                    'title' => 'Missing meta description',
                    'detail' => 'Write a compelling 120–160 character meta description to improve click-through rate.',
                ];
            } elseif ($descLen < 120 || $descLen > 160) {
                $items[] = [
                    'tone' => 'warn',
                    'category' => 'On-Page',
                    'title' => "Meta description is {$descLen} chars (target 120–160)",
                    'detail' => 'Refine meta description length to maximize SERP snippet visibility.',
                ];
            }

            if (! ($onPage['ssl'] ?? false)) {
                $items[] = [
                    'tone' => 'fail',
                    'category' => 'Security',
                    'title' => 'HTTPS / SSL is not active',
                    'detail' => 'Serve all pages over HTTPS to protect visitors and maintain search trust.',
                ];
            }

            $h1Count = (int) ($onPage['headings']['h1']['count'] ?? 0);
            if ($h1Count === 0) {
                $items[] = [
                    'tone' => 'fail',
                    'category' => 'Structure',
                    'title' => 'No H1 heading found',
                    'detail' => 'Include a single clear H1 heading summarizing the primary page topic.',
                ];
            } elseif ($h1Count > 1) {
                $items[] = [
                    'tone' => 'warn',
                    'category' => 'Structure',
                    'title' => "Multiple H1 headings detected ({$h1Count})",
                    'detail' => 'Consolidate to one primary H1 per page and demote secondary titles to H2.',
                ];
            }

            $altCoverage = (int) ($onPage['images']['alt_coverage'] ?? 100);
            $imgTotal = (int) ($onPage['images']['total'] ?? 0);
            if ($imgTotal > 0 && $altCoverage < 80) {
                $withoutAlt = (int) ($onPage['images']['without_alt'] ?? 0);
                $items[] = [
                    'tone' => 'warn',
                    'category' => 'Accessibility',
                    'title' => "{$withoutAlt} of {$imgTotal} images missing alt text ({$altCoverage}% coverage)",
                    'detail' => 'Add descriptive alt attributes to improve image SEO and screen-reader support.',
                ];
            }

            $schemaFound = (bool) ($onPage['schema_markup']['found'] ?? false);
            $schemaValid = (bool) ($onPage['schema_markup']['valid'] ?? true);
            if (! $schemaFound) {
                $items[] = [
                    'tone' => 'warn',
                    'category' => 'Structured Data',
                    'title' => 'No Schema.org markup detected',
                    'detail' => 'Implement JSON-LD (Organization, WebSite, or LocalBusiness) for rich results.',
                ];
            } elseif (! $schemaValid) {
                $items[] = [
                    'tone' => 'fail',
                    'category' => 'Structured Data',
                    'title' => 'Invalid JSON-LD structured data detected',
                    'detail' => 'Fix syntax errors so search crawlers can parse your structured data.',
                ];
            } else {
                $items[] = [
                    'tone' => 'pass',
                    'category' => 'Structured Data',
                    'title' => 'Valid structured data implemented',
                    'detail' => 'Search engines can parse entity metadata cleanly from this page.',
                ];
            }
        }

        if (is_array($techStack) && ($techStack['count'] ?? 0) > 0) {
            $items[] = [
                'tone' => 'pass',
                'category' => 'Tech Stack',
                'title' => ($techStack['count']).' web technologies fingerprinted',
                'detail' => 'Architecture components and client frameworks were successfully cataloged.',
            ];
        }

        if ($items === []) {
            $score = $audit->overall_score ?? 0;
            $items[] = [
                'tone' => $score >= 90 ? 'pass' : ($score >= 50 ? 'warn' : 'fail'),
                'category' => 'Summary',
                'title' => "Overall audit score: {$score}/100",
                'detail' => 'Review individual sections below for detailed metrics and technical signals.',
            ];
        }

        $priority = ['fail' => 0, 'warn' => 1, 'pass' => 2];
        usort($items, fn (array $a, array $b): int => ($priority[$a['tone']] ?? 9) <=> ($priority[$b['tone']] ?? 9));

        return array_slice($items, 0, 6);
    }
}
