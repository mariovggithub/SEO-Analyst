@php
    $report ??= \App\Support\AuditReportPresenter::make($audit, $results);
    $perf = $report->performance;
    $onPage = $report->onPage;
    $tech = $report->techStack;
    $overallRing = $report->scoreRing($audit->overall_score, 34);

    $categoryCards = [
        [
            'title' => 'Performance',
            'weight' => '40% weight',
            'score' => $perf['score'] ?? null,
            'caption' => isset($perf['mobile']['scores']['performance'])
                ? 'Mobile ' . ($perf['mobile']['scores']['performance'] ?? 0) . ' · Desktop ' . ($perf['desktop']['scores']['performance'] ?? 0)
                : 'Core Web Vitals & Lighthouse',
        ],
        [
            'title' => 'On-Page SEO',
            'weight' => '35% weight',
            'score' => $onPage['score'] ?? null,
            'caption' => isset($onPage['meta'])
                ? 'Meta, headings, schema & crawlability'
                : 'Technical & content audit',
        ],
        [
            'title' => 'Tech Stack',
            'weight' => '25% weight',
            'score' => $tech['score'] ?? null,
            'caption' => isset($tech['count'])
                ? ($tech['count'] . ' ' . \Illuminate\Support\Str::plural('technology', (int) $tech['count']) . ' detected')
                : 'Framework & infrastructure signals',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Audit Report — {{ $audit->domain }}</title>
    <style>
        :root {
            --ink-primary: #0f172a;
            --ink-secondary: #334155;
            --ink-muted: #64748b;
            --brand: #1d4ed8;
            --brand-dark: #1e3a8a;
            --brand-soft: #eff6ff;
            --brand-border: #bfdbfe;
            --surface-page: #eef2f6;
            --surface-card: #ffffff;
            --surface-subtle: #f8fafc;
            --border-subtle: #e2e8f0;
            --border-strong: #cbd5e1;
            --pass-ink: #047857;
            --pass-bg: #ecfdf5;
            --pass-border: #a7f3d0;
            --pass-accent: #10b981;
            --warn-ink: #b45309;
            --warn-bg: #fffbeb;
            --warn-border: #fde68a;
            --warn-accent: #f59e0b;
            --fail-ink: #be123c;
            --fail-bg: #fff1f2;
            --fail-border: #fecdd3;
            --fail-accent: #ef4444;
            --neutral-ink: #475569;
            --neutral-bg: #f1f5f9;
            --neutral-border: #cbd5e1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--ink-primary);
            background: var(--surface-page);
            font-size: 10.5px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .tabular {
            font-variant-numeric: tabular-nums;
        }

        /* Screen Toolbar */
        .no-print-bar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: #0f172a;
            color: #f8fafc;
            border-bottom: 1px solid #1e293b;
            padding: 10px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .toolbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12.5px;
            font-weight: 600;
            color: #f8fafc;
        }

        .toolbar-sub {
            color: #94a3b8;
            font-weight: 400;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            padding: 7px 12px;
            border-radius: 6px;
            border: 1px solid #334155;
            transition: background 0.15s, color 0.15s;
        }

        .btn-back:hover {
            background: #1e293b;
            color: #ffffff;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--brand);
            color: #ffffff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: background 0.15s;
        }

        .btn-print:hover {
            background: var(--brand-dark);
        }

        /* A4 Paper Container */
        .report-sheet {
            max-width: 210mm;
            margin: 22px auto 40px;
            background: var(--surface-card);
            padding: 15mm 15mm 16mm;
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }

        /* Masthead */
        .masthead {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 20px;
            padding-bottom: 16px;
            margin-bottom: 18px;
            border-bottom: 2px solid var(--ink-primary);
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .masthead-left {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-kicker {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--brand);
            margin-bottom: 6px;
        }

        .report-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--ink-primary);
            line-height: 1.2;
        }

        .target-domain {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--brand);
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .target-url {
            font-size: 9.5px;
            color: var(--ink-muted);
            margin-top: 2px;
            overflow-wrap: anywhere;
            font-family: 'JetBrains Mono', 'SFMono-Regular', Consolas, monospace;
        }

        .meta-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid var(--border-subtle);
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .meta-label {
            font-size: 8.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ink-muted);
        }

        .meta-value {
            font-size: 10px;
            font-weight: 600;
            color: var(--ink-secondary);
        }

        .masthead-score {
            width: 150px;
            flex-shrink: 0;
            background: var(--surface-subtle);
            border: 1px solid var(--border-subtle);
            border-radius: 10px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .gauge-wrap {
            position: relative;
            width: 82px;
            height: 82px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gauge-wrap svg {
            transform: rotate(-90deg);
            display: block;
        }

        .gauge-value {
            position: absolute;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--ink-primary);
        }

        .gauge-caption {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--ink-muted);
            margin-top: 6px;
            margin-bottom: 4px;
        }

        /* Status Pills & Tone Utilities */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 0.02em;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .pill-pass { color: var(--pass-ink); background: var(--pass-bg); border-color: var(--pass-border); }
        .pill-warn { color: var(--warn-ink); background: var(--warn-bg); border-color: var(--warn-border); }
        .pill-fail { color: var(--fail-ink); background: var(--fail-bg); border-color: var(--fail-border); }
        .pill-neutral { color: var(--neutral-ink); background: var(--neutral-bg); border-color: var(--neutral-border); }

        .stroke-pass { stroke: var(--pass-accent); }
        .stroke-warn { stroke: var(--warn-accent); }
        .stroke-fail { stroke: var(--fail-accent); }
        .stroke-neutral { stroke: var(--neutral-border); }

        .fill-pass { background: var(--pass-accent); }
        .fill-warn { background: var(--warn-accent); }
        .fill-fail { background: var(--fail-accent); }
        .fill-neutral { background: var(--neutral-border); }

        .text-pass { color: var(--pass-ink); }
        .text-warn { color: var(--warn-ink); }
        .text-fail { color: var(--fail-ink); }
        .text-neutral { color: var(--ink-muted); }

        /* Executive Scorecard */
        .scorecard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 16px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .scorecard-item {
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            padding: 11px 12px;
            background: var(--surface-card);
        }

        .scorecard-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 6px;
        }

        .scorecard-title {
            font-size: 10px;
            font-weight: 700;
            color: var(--ink-primary);
        }

        .scorecard-weight {
            font-size: 8.5px;
            color: var(--ink-muted);
        }

        .scorecard-number-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-top: 6px;
            margin-bottom: 6px;
        }

        .scorecard-number {
            font-size: 21px;
            font-weight: 800;
            line-height: 1;
            color: var(--ink-primary);
        }

        .scorecard-denom {
            font-size: 10px;
            font-weight: 500;
            color: var(--ink-muted);
        }

        .progress-track {
            width: 100%;
            height: 5px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 999px;
        }

        .scorecard-caption {
            font-size: 8.5px;
            color: var(--ink-muted);
        }

        /* Executive Findings */
        .findings-panel {
            background: var(--surface-subtle);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 20px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .panel-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--ink-secondary);
            margin-bottom: 9px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .findings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .finding-card {
            background: var(--surface-card);
            border: 1px solid var(--border-subtle);
            border-left-width: 3px;
            border-radius: 6px;
            padding: 8px 10px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .finding-fail { border-left-color: var(--fail-accent); }
        .finding-warn { border-left-color: var(--warn-accent); }
        .finding-pass { border-left-color: var(--pass-accent); }

        .finding-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 3px;
        }

        .finding-cat {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ink-muted);
        }

        .finding-title {
            font-size: 10px;
            font-weight: 700;
            color: var(--ink-primary);
            margin-bottom: 2px;
        }

        .finding-detail {
            font-size: 9px;
            color: var(--ink-secondary);
            line-height: 1.4;
        }

        /* Sections */
        .section {
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 7px;
            margin-bottom: 12px;
            border-bottom: 1.5px solid var(--border-strong);
            break-after: avoid;
            page-break-after: avoid;
        }

        .section-title-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 5px;
            background: var(--ink-primary);
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 700;
        }

        .section-title {
            font-size: 13.5px;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: var(--ink-primary);
        }

        .section-sub {
            font-size: 9px;
            color: var(--ink-muted);
            font-weight: 500;
        }

        .subsection-title {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--ink-secondary);
            margin: 12px 0 7px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            break-after: avoid;
            page-break-after: avoid;
        }

        /* Cards & Tables */
        .card {
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            background: var(--surface-card);
            overflow: hidden;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .two-col {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .strategy-card {
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            padding: 11px 12px;
            background: var(--surface-card);
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .strategy-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 9px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .strategy-name {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-primary);
        }

        .strategy-source {
            font-size: 8px;
            color: var(--ink-muted);
            background: var(--surface-subtle);
            border: 1px solid var(--border-subtle);
            border-radius: 4px;
            padding: 1px 6px;
        }

        .strategy-scores {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .mini-score {
            background: var(--surface-subtle);
            border: 1px solid var(--border-subtle);
            border-radius: 6px;
            padding: 7px 8px;
            text-align: center;
        }

        .mini-score-val {
            font-size: 16px;
            font-weight: 800;
            line-height: 1.1;
        }

        .mini-score-lbl {
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--ink-muted);
            margin-top: 3px;
        }

        /* Clean Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table th {
            background: var(--surface-subtle);
            color: var(--ink-muted);
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
            padding: 7px 10px;
            border-bottom: 1px solid var(--border-strong);
        }

        .data-table td {
            padding: 7px 10px;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--ink-secondary);
            vertical-align: top;
        }

        .data-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .metric-name-main {
            font-weight: 700;
            color: var(--ink-primary);
        }

        .metric-name-sub {
            font-size: 8.5px;
            color: var(--ink-muted);
        }

        /* Diagnostics List */
        .diag-list {
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            overflow: hidden;
        }

        .diag-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            padding: 7px 10px;
            border-bottom: 1px solid var(--border-subtle);
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .diag-row:last-child {
            border-bottom: none;
        }

        .diag-left {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            min-width: 0;
        }

        .diag-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-top: 4px;
            flex-shrink: 0;
        }

        .diag-title {
            font-size: 9.5px;
            font-weight: 600;
            color: var(--ink-primary);
        }

        .diag-val {
            font-size: 8.5px;
            color: var(--ink-muted);
            margin-top: 1px;
        }

        /* On-Page Spec & Checklist */
        .spec-value {
            font-size: 9.5px;
            color: var(--ink-primary);
            overflow-wrap: anywhere;
        }

        .spec-empty {
            font-size: 9.5px;
            color: var(--fail-ink);
            font-style: italic;
        }

        .checklist-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .check-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border: 1px solid var(--border-subtle);
            border-radius: 7px;
            padding: 8px 10px;
            background: var(--surface-card);
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .check-main {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .check-title {
            font-size: 9.5px;
            font-weight: 700;
            color: var(--ink-primary);
        }

        .check-sub {
            font-size: 8.5px;
            color: var(--ink-muted);
            overflow-wrap: anywhere;
        }

        /* Headings & Links */
        .headings-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .heading-card {
            border: 1px solid var(--border-subtle);
            border-radius: 7px;
            padding: 8px 10px;
            background: var(--surface-card);
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .heading-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .heading-tag {
            font-size: 10px;
            font-weight: 800;
            color: var(--ink-primary);
            letter-spacing: 0.03em;
        }

        .heading-samples {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .heading-samples li {
            font-size: 8.5px;
            color: var(--ink-secondary);
            padding-left: 8px;
            position: relative;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .heading-samples li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 5px;
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: var(--border-strong);
        }

        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 8px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .kpi-box {
            border: 1px solid var(--border-subtle);
            border-radius: 7px;
            padding: 8px 10px;
            background: var(--surface-subtle);
            text-align: center;
        }

        .kpi-num {
            font-size: 16px;
            font-weight: 800;
            color: var(--ink-primary);
            line-height: 1.1;
        }

        .kpi-lbl {
            font-size: 8.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--ink-muted);
            margin-top: 3px;
        }

        /* Structured Data & GBP */
        .info-panel {
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            padding: 11px 12px;
            background: var(--surface-card);
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .info-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding-bottom: 7px;
            margin-bottom: 8px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .info-panel-title {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--ink-primary);
        }

        .kv-list {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .kv-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 10px;
            font-size: 9.5px;
            padding-bottom: 4px;
            border-bottom: 1px dashed var(--border-subtle);
        }

        .kv-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .kv-key {
            color: var(--ink-muted);
            font-weight: 600;
            flex-shrink: 0;
        }

        .kv-val {
            color: var(--ink-primary);
            font-weight: 600;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 6px;
        }

        .chip {
            display: inline-block;
            background: var(--brand-soft);
            color: var(--brand-dark);
            border: 1px solid var(--brand-border);
            border-radius: 999px;
            padding: 1px 7px;
            font-size: 8.5px;
            font-weight: 600;
        }

        .chip-neutral {
            background: var(--neutral-bg);
            color: var(--ink-secondary);
            border-color: var(--border-subtle);
        }

        .code-panel {
            border: 1px solid #1e293b;
            border-radius: 7px;
            overflow: hidden;
            margin-top: 8px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .code-panel-head {
            background: #1e293b;
            color: #e2e8f0;
            padding: 6px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 8.5px;
            font-weight: 600;
        }

        .code-panel-body {
            background: #0f172a;
            color: #e2e8f0;
            padding: 8px 10px;
            font-family: 'JetBrains Mono', 'SFMono-Regular', Consolas, monospace;
            font-size: 8.5px;
            line-height: 1.45;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .code-line-highlight {
            background: #fef08a;
            color: #0f172a;
            font-weight: 600;
            border-radius: 2px;
            padding: 0 2px;
        }

        .evidence-callout {
            margin-top: 6px;
            background: var(--warn-bg);
            color: var(--warn-ink);
            border: 1px solid var(--warn-border);
            border-left: 3px solid var(--warn-accent);
            border-radius: 5px;
            padding: 6px 8px;
            font-family: 'JetBrains Mono', 'SFMono-Regular', Consolas, monospace;
            font-size: 8.5px;
            line-height: 1.4;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        /* Tech Stack */
        .tech-group {
            margin-bottom: 12px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .tech-group-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 9.5px;
            font-weight: 700;
            color: var(--ink-secondary);
        }

        .tech-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 7px;
        }

        .tech-card {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--border-subtle);
            border-radius: 7px;
            padding: 7px 9px;
            background: var(--surface-card);
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .tech-monogram {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: var(--brand-soft);
            color: var(--brand-dark);
            border: 1px solid var(--brand-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9.5px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .tech-info {
            min-width: 0;
            flex: 1;
        }

        .tech-name {
            font-size: 9.5px;
            font-weight: 700;
            color: var(--ink-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tech-meta {
            font-size: 8px;
            color: var(--ink-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 1px;
        }

        .empty-box {
            border: 1px dashed var(--border-strong);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            color: var(--ink-muted);
            font-size: 9.5px;
            background: var(--surface-subtle);
        }

        /* Footer */
        .report-footer {
            margin-top: 22px;
            padding-top: 10px;
            border-top: 1px solid var(--border-strong);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-size: 8.5px;
            color: var(--ink-muted);
            break-inside: avoid;
            page-break-inside: avoid;
        }

        @page {
            size: A4;
            margin: 12mm 13mm 14mm 13mm;
        }

        @media print {
            body {
                background: #ffffff;
            }
            .no-print {
                display: none !important;
            }
            .report-sheet {
                max-width: none;
                margin: 0;
                padding: 0;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <!-- Top Screen Action Bar -->
    <div class="no-print no-print-bar">
        <div class="toolbar-brand">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 3v18h18" />
                <path d="m19 9-5 5-4-4-3 3" />
            </svg>
            <span>SEO Analyst</span>
            <span class="toolbar-sub">&bull; Executive Report Preview ({{ $audit->domain }})</span>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('audits.show', $audit) }}" class="btn-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m12 19-7-7 7-7" />
                    <path d="M19 12H5" />
                </svg>
                Back to Audit
            </a>
            <button type="button" onclick="window.print()" class="btn-print">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="6 9 6 2 18 2 18 9" />
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                    <rect width="12" height="8" x="6" y="14" />
                </svg>
                Print / Save as PDF
            </button>
        </div>
    </div>

    <main class="report-sheet">
        <!-- Executive Masthead -->
        <header class="masthead">
            <div class="masthead-left">
                <div>
                    <div class="brand-kicker">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 3v18h18" />
                            <path d="m19 9-5 5-4-4-3 3" />
                        </svg>
                        SEO Intelligence &amp; Technical Audit
                    </div>
                    <h1 class="report-title">Executive SEO Audit Report</h1>
                    <p class="target-domain">{{ $audit->domain }}</p>
                    <p class="target-url">{{ $audit->url }}</p>
                </div>

                <div class="meta-strip">
                    <div class="meta-item">
                        <span class="meta-label">Audit Date</span>
                        <span class="meta-value tabular">{{ $audit->created_at->format('M d, Y · H:i T') }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Report ID</span>
                        <span class="meta-value tabular">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($audit->id, 0, 8)) }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Audit Status</span>
                        <span class="meta-value">{{ $audit->status->label() }}</span>
                    </div>
                </div>
            </div>

            <div class="masthead-score">
                <div class="gauge-wrap">
                    <svg width="82" height="82" viewBox="0 0 82 82" aria-hidden="true">
                        <circle cx="41" cy="41" r="34" fill="none" stroke="#e2e8f0" stroke-width="6" />
                        <circle
                            cx="41"
                            cy="41"
                            r="34"
                            fill="none"
                            stroke-width="6"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $overallRing['dasharray'] }}"
                            stroke-dashoffset="{{ $overallRing['dashoffset'] }}"
                            class="stroke-{{ $overallRing['tone'] }}"
                        />
                    </svg>
                    <span class="gauge-value tabular">{{ $overallRing['value'] }}</span>
                </div>
                <span class="gauge-caption">Overall Score</span>
                <span class="pill pill-{{ $overallRing['tone'] }}">{{ $overallRing['label'] }}</span>
            </div>
        </header>

        <!-- Weighted Category Scorecard -->
        <section class="scorecard-grid" aria-label="Category Scorecard">
            @foreach($categoryCards as $card)
                @php
                    $cardStatus = $report->scoreStatus($card['score']);
                    $cardPercent = is_numeric($card['score']) ? max(0, min(100, (int) round((float) $card['score']))) : 0;
                @endphp
                <div class="scorecard-item">
                    <div class="scorecard-top">
                        <span class="scorecard-title">{{ $card['title'] }}</span>
                        <span class="scorecard-weight">{{ $card['weight'] }}</span>
                    </div>
                    <div class="scorecard-number-row">
                        <span class="scorecard-number tabular">
                            {{ is_numeric($card['score']) ? $cardPercent : '—' }}<span class="scorecard-denom">/100</span>
                        </span>
                        <span class="pill pill-{{ $cardStatus['tone'] }}">{{ $cardStatus['label'] }}</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-bar fill-{{ $cardStatus['tone'] }}" style="width: {{ $cardPercent }}%;"></div>
                    </div>
                    <p class="scorecard-caption">{{ $card['caption'] }}</p>
                </div>
            @endforeach
        </section>

        <!-- Key Findings & Priority Actions -->
        <section class="findings-panel" aria-label="Executive Findings">
            <div class="panel-title">
                <span>Key Findings &amp; Priority Actions</span>
                <span style="font-weight: 500; text-transform: none; letter-spacing: 0; color: var(--ink-muted);">Sorted by business &amp; ranking impact</span>
            </div>
            <div class="findings-grid">
                @foreach($report->findings as $finding)
                    <div class="finding-card finding-{{ $finding['tone'] }}">
                        <div class="finding-head">
                            <span class="finding-cat">{{ $finding['category'] }}</span>
                            <span class="pill pill-{{ $finding['tone'] }}">
                                {{ $finding['tone'] === 'fail' ? 'Critical' : ($finding['tone'] === 'warn' ? 'Action' : 'Passed') }}
                            </span>
                        </div>
                        <p class="finding-title">{{ $finding['title'] }}</p>
                        <p class="finding-detail">{{ $finding['detail'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Section 01: Performance & Core Web Vitals -->
        @if($perf)
            <section class="section">
                <div class="section-header">
                    <div class="section-title-group">
                        <span class="section-num tabular">01</span>
                        <h2 class="section-title">Performance &amp; Core Web Vitals</h2>
                    </div>
                    <span class="section-sub">Lighthouse scores &amp; user-centric loading stability</span>
                </div>

                <div class="two-col">
                    @foreach(['mobile' => 'Mobile Experience', 'desktop' => 'Desktop Experience'] as $strategy => $label)
                        @if(isset($perf[$strategy]))
                            @php
                                $scores = $perf[$strategy]['scores'] ?? [];
                            @endphp
                            <div class="strategy-card">
                                <div class="strategy-head">
                                    <span class="strategy-name">
                                        @if($strategy === 'mobile')
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect width="14" height="20" x="5" y="2" rx="2" ry="2" />
                                                <path d="M12 18h.01" />
                                            </svg>
                                        @else
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect width="20" height="14" x="2" y="3" rx="2" />
                                                <line x1="8" x2="16" y1="21" y2="21" />
                                                <line x1="12" x2="12" y1="17" y2="21" />
                                            </svg>
                                        @endif
                                        {{ $label }}
                                    </span>
                                    <span class="strategy-source">{{ $report->sourceLabel($perf[$strategy]['source'] ?? null) }}</span>
                                </div>
                                <div class="strategy-scores">
                                    @foreach([
                                        'performance' => 'Performance',
                                        'seo' => 'SEO',
                                        'best_practices' => 'Best Practices',
                                    ] as $scoreKey => $scoreLabel)
                                        @php
                                            $val = $scores[$scoreKey] ?? null;
                                            $st = $report->scoreStatus($val);
                                        @endphp
                                        <div class="mini-score">
                                            <div class="mini-score-val tabular text-{{ $st['tone'] }}">
                                                {{ is_numeric($val) ? (int) round((float) $val) : '—' }}
                                            </div>
                                            <div class="mini-score-lbl">{{ $scoreLabel }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                @php
                    $cwvRows = $report->cwvRows();
                @endphp
                @if(!empty($cwvRows))
                    <h3 class="subsection-title">
                        <span>Core Web Vitals Benchmark</span>
                        <span style="font-weight: 400; color: var(--ink-muted); font-size: 9px;">Mobile vs Desktop against Google thresholds</span>
                    </h3>
                    <div class="card">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 34%;">Metric</th>
                                    <th style="width: 16%;">Mobile</th>
                                    <th style="width: 16%;">Desktop</th>
                                    <th style="width: 16%;">Good Target</th>
                                    <th style="width: 18%;">Mobile Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cwvRows as $row)
                                    <tr>
                                        <td>
                                            <span class="metric-name-main">{{ $row['label'] }}</span>
                                            <span class="metric-name-sub"> &middot; {{ $row['name'] }}</span>
                                        </td>
                                        <td class="tabular" style="font-weight: 700;">{{ $row['mobile'] }}</td>
                                        <td class="tabular">{{ $row['desktop'] }}</td>
                                        <td class="tabular" style="color: var(--ink-muted);">{{ $row['target'] }}</td>
                                        <td>
                                            <span class="pill pill-{{ $row['mobileStatus']['tone'] }}">{{ $row['mobileStatus']['label'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @php
                    $diagnostics = $perf['mobile']['diagnostics'] ?? ($perf['desktop']['diagnostics'] ?? []);
                @endphp
                @if(!empty($diagnostics))
                    <h3 class="subsection-title">
                        <span>Top Optimization Opportunities</span>
                        <span style="font-weight: 400; color: var(--ink-muted); font-size: 9px;">Showing top {{ min(6, count($diagnostics)) }} mobile diagnostics</span>
                    </h3>
                    <div class="diag-list">
                        @foreach(array_slice($diagnostics, 0, 6) as $diag)
                            @php
                                $dScore = $diag['score'] ?? null;
                                $dTone = ($dScore === null || $dScore == 0) ? 'fail' : ($dScore < 0.5 ? 'warn' : 'pass');
                            @endphp
                            <div class="diag-row">
                                <div class="diag-left">
                                    <span class="diag-dot fill-{{ $dTone }}"></span>
                                    <div>
                                        <div class="diag-title">{{ $diag['title'] ?? 'Diagnostic check' }}</div>
                                        @if(!empty($diag['displayValue']))
                                            <div class="diag-val tabular">{{ $diag['displayValue'] }}</div>
                                        @endif
                                    </div>
                                </div>
                                <span class="pill pill-{{ $dTone }}">
                                    {{ $dTone === 'fail' ? 'High Impact' : ($dTone === 'warn' ? 'Moderate' : 'Minor') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <!-- Section 02: On-Page SEO & Content Structure -->
        @if($onPage)
            @php
                $titleLen = (int) ($onPage['meta']['title_length'] ?? 0);
                $titleTone = $titleLen === 0 ? 'fail' : (($titleLen >= 30 && $titleLen <= 60) ? 'pass' : 'warn');
                $descLen = (int) ($onPage['meta']['description_length'] ?? 0);
                $descTone = $descLen === 0 ? 'fail' : (($descLen >= 120 && $descLen <= 160) ? 'pass' : 'warn');
                $canonical = $onPage['meta']['canonical'] ?? '';
                $hasOg = (bool) ($onPage['meta']['has_og_tags'] ?? false);
                $ogCount = is_array($onPage['meta']['og_tags'] ?? null) ? count($onPage['meta']['og_tags']) : 0;
            @endphp

            <section class="section">
                <div class="section-header">
                    <div class="section-title-group">
                        <span class="section-num tabular">02</span>
                        <h2 class="section-title">On-Page SEO &amp; Content Structure</h2>
                    </div>
                    <span class="section-sub">Metadata hygiene, crawlability, headings &amp; internal linking</span>
                </div>

                <!-- Meta & Social Inspection Table -->
                <div class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 22%;">Element</th>
                                <th style="width: 58%;">Detected Value</th>
                                <th style="width: 20%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="metric-name-main">Title Tag</div>
                                    <div class="metric-name-sub tabular">{{ $titleLen }} chars (30–60 ideal)</div>
                                </td>
                                <td>
                                    @if(!empty($onPage['meta']['title']))
                                        <span class="spec-value">{{ $onPage['meta']['title'] }}</span>
                                    @else
                                        <span class="spec-empty">Missing &lt;title&gt; tag</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="pill pill-{{ $titleTone }}">
                                        {{ $titleTone === 'pass' ? 'Optimal' : ($titleTone === 'warn' ? 'Check Length' : 'Missing') }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="metric-name-main">Meta Description</div>
                                    <div class="metric-name-sub tabular">{{ $descLen }} chars (120–160 ideal)</div>
                                </td>
                                <td>
                                    @if(!empty($onPage['meta']['description']))
                                        <span class="spec-value">{{ $onPage['meta']['description'] }}</span>
                                    @else
                                        <span class="spec-empty">Missing meta description</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="pill pill-{{ $descTone }}">
                                        {{ $descTone === 'pass' ? 'Optimal' : ($descTone === 'warn' ? 'Check Length' : 'Missing') }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="metric-name-main">Canonical URL</div>
                                    <div class="metric-name-sub">Duplicate content guard</div>
                                </td>
                                <td>
                                    @if(!empty($canonical))
                                        <span class="spec-value" style="font-family: 'JetBrains Mono', monospace; font-size: 9px;">{{ $canonical }}</span>
                                    @else
                                        <span class="spec-empty">No rel="canonical" link tag found</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="pill pill-{{ !empty($canonical) ? 'pass' : 'fail' }}">
                                        {{ !empty($canonical) ? 'Configured' : 'Missing' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="metric-name-main">Open Graph Tags</div>
                                    <div class="metric-name-sub">Social sharing previews</div>
                                </td>
                                <td>
                                    @if($hasOg)
                                        <span class="spec-value tabular">
                                            {{ $ogCount > 0 ? $ogCount . ' Open Graph tags detected' : 'Open Graph metadata detected' }}
                                            @if(!empty($onPage['meta']['og_tags']['og:title']))
                                                &mdash; {{ \Illuminate\Support\Str::limit($onPage['meta']['og_tags']['og:title'], 65) }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="spec-empty">No og:* meta properties found</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="pill pill-{{ $hasOg ? 'pass' : 'fail' }}">
                                        {{ $hasOg ? 'Detected' : 'Missing' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Technical Compliance Checklist -->
                <h3 class="subsection-title">Technical &amp; Media Compliance</h3>
                @php
                    $altCoverage = (int) ($onPage['images']['alt_coverage'] ?? 100);
                    $imgTotal = (int) ($onPage['images']['total'] ?? 0);
                    $imgWithoutAlt = (int) ($onPage['images']['without_alt'] ?? 0);
                    $schemaOk = (bool) ($onPage['schema_markup']['found'] ?? false) && (bool) ($onPage['schema_markup']['valid'] ?? true);
                    $gbpOk = (bool) ($onPage['google_business_profile']['exists'] ?? false);

                    $checks = [
                        [
                            'title' => 'SSL / HTTPS Encryption',
                            'sub' => ($onPage['ssl'] ?? false) ? 'Served securely over HTTPS' : 'Not served over HTTPS',
                            'tone' => ($onPage['ssl'] ?? false) ? 'pass' : 'fail',
                            'badge' => ($onPage['ssl'] ?? false) ? 'Active' : 'Missing',
                        ],
                        [
                            'title' => 'XML Sitemap (sitemap.xml)',
                            'sub' => ($onPage['sitemap']['exists'] ?? false)
                                ? 'Reachable' . (!empty($onPage['sitemap']['status_code']) ? ' (HTTP ' . $onPage['sitemap']['status_code'] . ')' : '')
                                : 'Not found at /sitemap.xml',
                            'tone' => ($onPage['sitemap']['exists'] ?? false) ? 'pass' : 'fail',
                            'badge' => ($onPage['sitemap']['exists'] ?? false) ? 'Found' : 'Missing',
                        ],
                        [
                            'title' => 'Robots.txt Directive',
                            'sub' => ($onPage['robots_txt']['exists'] ?? false) ? 'Reachable at /robots.txt' : 'Not found at /robots.txt',
                            'tone' => ($onPage['robots_txt']['exists'] ?? false) ? 'pass' : 'fail',
                            'badge' => ($onPage['robots_txt']['exists'] ?? false) ? 'Found' : 'Missing',
                        ],
                        [
                            'title' => 'Image Alt Attributes',
                            'sub' => $imgTotal > 0
                                ? "{$altCoverage}% coverage ({$imgWithoutAlt} of {$imgTotal} missing alt)"
                                : 'No <img> elements found on page',
                            'tone' => $altCoverage >= 80 ? 'pass' : ($altCoverage >= 50 ? 'warn' : 'fail'),
                            'badge' => "{$altCoverage}%",
                        ],
                        [
                            'title' => 'Schema.org Markup',
                            'sub' => $schemaOk
                                ? 'Primary: ' . ($onPage['schema_markup']['primary']['type'] ?? 'Structured data valid')
                                : (($onPage['schema_markup']['found'] ?? false) ? 'Detected with validation errors' : 'No structured data detected'),
                            'tone' => $schemaOk ? 'pass' : 'fail',
                            'badge' => $schemaOk ? 'Valid' : (($onPage['schema_markup']['found'] ?? false) ? 'Invalid' : 'Missing'),
                        ],
                        [
                            'title' => 'Google Business Profile',
                            'sub' => $gbpOk
                                ? 'Detected (' . ($onPage['google_business_profile']['confidence'] ?? 'medium') . ' confidence)'
                                : 'No local business or maps signals',
                            'tone' => $gbpOk ? 'pass' : 'warn',
                            'badge' => $gbpOk ? 'Detected' : 'Not Found',
                        ],
                    ];
                @endphp
                <div class="checklist-grid">
                    @foreach($checks as $check)
                        <div class="check-card">
                            <div class="check-main">
                                @if($check['tone'] === 'pass')
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#047857" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;" aria-hidden="true">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                        <polyline points="22 4 12 14.01 9 11.01" />
                                    </svg>
                                @elseif($check['tone'] === 'warn')
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;" aria-hidden="true">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                                        <path d="M12 9v4" />
                                        <path d="M12 17h.01" />
                                    </svg>
                                @else
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#be123c" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="m15 9-6 6" />
                                        <path d="m9 9 6 6" />
                                    </svg>
                                @endif
                                <div>
                                    <div class="check-title">{{ $check['title'] }}</div>
                                    <div class="check-sub">{{ $check['sub'] }}</div>
                                </div>
                            </div>
                            <span class="pill pill-{{ $check['tone'] }} tabular">{{ $check['badge'] }}</span>
                        </div>
                    @endforeach
                </div>

                <!-- Heading Structure -->
                @if(!empty($onPage['headings']))
                    <h3 class="subsection-title">Heading Hierarchy &amp; Link Profile</h3>
                    <div class="headings-grid">
                        @foreach(['h1', 'h2', 'h3'] as $tag)
                            @php
                                $hCount = (int) ($onPage['headings'][$tag]['count'] ?? 0);
                                $hTexts = $onPage['headings'][$tag]['texts'] ?? [];
                                $hTone = $tag === 'h1' ? ($hCount === 1 ? 'pass' : ($hCount > 1 ? 'warn' : 'fail')) : ($hCount > 0 ? 'pass' : 'neutral');
                            @endphp
                            <div class="heading-card">
                                <div class="heading-head">
                                    <span class="heading-tag">{{ strtoupper($tag) }} Tags</span>
                                    <span class="pill pill-{{ $hTone }} tabular">{{ $hCount }} found</span>
                                </div>
                                @if(!empty($hTexts))
                                    <ul class="heading-samples">
                                        @foreach(array_slice($hTexts, 0, 4) as $text)
                                            <li title="{{ $text }}">{{ $text }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p style="font-size: 8.5px; color: var(--ink-muted); font-style: italic;">No {{ strtoupper($tag) }} headings detected</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Link Distribution -->
                @if(!empty($onPage['links']))
                    <div class="kpi-strip">
                        <div class="kpi-box">
                            <div class="kpi-num tabular">{{ $onPage['links']['total'] ?? 0 }}</div>
                            <div class="kpi-lbl">Total Links</div>
                        </div>
                        <div class="kpi-box">
                            <div class="kpi-num tabular">{{ $onPage['links']['internal'] ?? 0 }}</div>
                            <div class="kpi-lbl">Internal Links</div>
                        </div>
                        <div class="kpi-box">
                            <div class="kpi-num tabular">{{ $onPage['links']['external'] ?? 0 }}</div>
                            <div class="kpi-lbl">External Links</div>
                        </div>
                        <div class="kpi-box">
                            <div class="kpi-num tabular">{{ $onPage['links']['nofollow'] ?? 0 }}</div>
                            <div class="kpi-lbl">Nofollow Links</div>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        <!-- Section 03: Structured Data & Local Presence -->
        @if($onPage && (!empty($onPage['schema_markup']) || !empty($onPage['google_business_profile'])))
            @php
                $schema = $onPage['schema_markup'] ?? [];
                $gbp = $onPage['google_business_profile'] ?? [];
                $formatEvidence = $schema['format_evidence'] ?? [];
                $jsonLdBlocks = $schema['json_ld_blocks'] ?? [];
                $jsonLdTypes = collect($jsonLdBlocks)->flatMap(fn ($block) => $block['types'] ?? [])->unique()->values();
                $primary = $schema['primary'] ?? ($schema['items'][0] ?? null);
            @endphp

            <section class="section">
                <div class="section-header">
                    <div class="section-title-group">
                        <span class="section-num tabular">03</span>
                        <h2 class="section-title">Structured Data &amp; Local Presence</h2>
                    </div>
                    <span class="section-sub">Entity markup validation &amp; Google Business Profile signals</span>
                </div>

                <div class="two-col">
                    <!-- Schema Summary Panel -->
                    <div class="info-panel">
                        <div class="info-panel-head">
                            <span class="info-panel-title">Schema Markup Summary</span>
                            <span class="pill pill-{{ ($schema['found'] ?? false) ? (($schema['valid'] ?? true) ? 'pass' : 'fail') : 'neutral' }}">
                                {{ ($schema['found'] ?? false) ? (($schema['valid'] ?? true) ? 'Valid Schema' : 'Validation Errors') : 'Not Detected' }}
                            </span>
                        </div>
                        <div class="kv-list">
                            @foreach(['json_ld' => 'JSON-LD', 'microdata' => 'Microdata', 'rdfa' => 'RDFa'] as $key => $label)
                                @php
                                    $count = (int) ($schema['formats'][$key] ?? 0);
                                @endphp
                                <div class="kv-row">
                                    <span class="kv-key">{{ $label }}</span>
                                    <span class="kv-val tabular">{{ $count > 0 ? "{$count} detected" : 'Not detected' }}</span>
                                </div>
                            @endforeach
                            @if($primary)
                                <div class="kv-row">
                                    <span class="kv-key">Primary Entity</span>
                                    <span class="kv-val">
                                        {{ $primary['type'] ?? 'Schema' }}@if(!empty($primary['name'])) &mdash; {{ \Illuminate\Support\Str::limit($primary['name'], 34) }}@endif
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if($jsonLdTypes->isNotEmpty())
                            <div class="chip-row">
                                @foreach($jsonLdTypes as $type)
                                    <span class="chip">{{ $type }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if(!($schema['valid'] ?? true) && !empty($schema['errors']))
                            <div class="evidence-callout" style="background: var(--fail-bg); color: var(--fail-ink); border-color: var(--fail-border); border-left-color: var(--fail-accent);">
                                {{ implode('; ', $schema['errors']) }}
                            </div>
                        @endif
                    </div>

                    <!-- GBP Summary Panel -->
                    <div class="info-panel">
                        <div class="info-panel-head">
                            <span class="info-panel-title">Google Business Profile</span>
                            <span class="pill pill-{{ ($gbp['exists'] ?? false) ? 'pass' : 'neutral' }}">
                                {{ ($gbp['exists'] ?? false) ? ucfirst($gbp['confidence'] ?? 'medium') . ' Confidence' : 'Not Detected' }}
                            </span>
                        </div>
                        <div class="kv-list">
                            <div class="kv-row">
                                <span class="kv-key">Presence</span>
                                <span class="kv-val">{{ ($gbp['exists'] ?? false) ? 'Available on page' : 'No GBP signals found' }}</span>
                            </div>
                            @if(!empty($gbp['name']))
                                <div class="kv-row">
                                    <span class="kv-key">Business Name</span>
                                    <span class="kv-val">{{ $gbp['name'] }}</span>
                                </div>
                            @endif
                            @if(!empty($gbp['address']))
                                <div class="kv-row">
                                    <span class="kv-key">Address</span>
                                    <span class="kv-val">{{ $gbp['address'] }}</span>
                                </div>
                            @endif
                            @if(!empty($gbp['phone']))
                                <div class="kv-row">
                                    <span class="kv-key">Phone</span>
                                    <span class="kv-val tabular">{{ $gbp['phone'] }}</span>
                                </div>
                            @endif
                            @if(($gbp['rating'] ?? null) !== null)
                                <div class="kv-row">
                                    <span class="kv-key">Rating</span>
                                    <span class="kv-val tabular">
                                        {{ $gbp['rating'] }} / 5.0
                                        @if(!empty($gbp['review_count']))
                                            ({{ $gbp['review_count'] }} reviews)
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if(!empty($gbp['signals']))
                            <div class="chip-row">
                                @foreach($gbp['signals'] as $signal)
                                    <span class="chip chip-neutral">{{ $signal }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                @foreach(['microdata' => 'Microdata Evidence', 'rdfa' => 'RDFa Evidence'] as $key => $title)
                    @if(!empty($formatEvidence[$key]))
                        <div class="evidence-callout"><strong>{{ $title }}:</strong> {{ $formatEvidence[$key] }}</div>
                    @endif
                @endforeach

                @if(!empty($jsonLdBlocks))
                    <h3 class="subsection-title">Detected JSON-LD Payload</h3>
                    @foreach($jsonLdBlocks as $index => $block)
                        <div class="code-panel">
                            <div class="code-panel-head">
                                <span>JSON-LD Block #{{ $index + 1 }}</span>
                                <span>{{ implode(' · ', !empty($block['types']) ? $block['types'] : ['Structured Data']) }}</span>
                            </div>
                            <div class="code-panel-body">
                                @foreach((preg_split('/\R/', \Illuminate\Support\Str::limit((string) ($block['code'] ?? ''), 2000)) ?: []) as $line)
                                    @php
                                        $highlight = (str_contains($line, '"@type"') || str_contains($line, "'@type'"))
                                            && collect($block['types'] ?? [])->contains(fn ($type) => str_contains($line, '"'.$type.'"'));
                                    @endphp
                                    <div class="{{ $highlight ? 'code-line-highlight' : '' }}">{{ $line === '' ? ' ' : $line }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @elseif(!empty($primary['code']) && ($primary['format'] ?? '') !== 'json_ld')
                    <h3 class="subsection-title">Primary Schema Snippet</h3>
                    <div class="code-panel">
                        <div class="code-panel-head">
                            <span>{{ strtoupper(str_replace('_', '-', (string) ($primary['format'] ?? 'schema'))) }}</span>
                            <span>{{ $primary['type'] ?? 'Structured Data' }}</span>
                        </div>
                        <div class="code-panel-body">{{ \Illuminate\Support\Str::limit((string) $primary['code'], 1200) }}</div>
                    </div>
                @endif
            </section>
        @endif

        <!-- Section 04: Technology Stack -->
        @if($tech)
            <section class="section">
                <div class="section-header">
                    <div class="section-title-group">
                        <span class="section-num tabular">04</span>
                        <h2 class="section-title">Technology Stack</h2>
                    </div>
                    <span class="section-sub tabular">{{ $tech['count'] ?? 0 }} detected &middot; Stack score {{ $tech['score'] ?? '—' }}/100</span>
                </div>

                @if(!empty($tech['grouped']))
                    @foreach($tech['grouped'] as $category => $techs)
                        <div class="tech-group">
                            <div class="tech-group-head">
                                <span>{{ $category }}</span>
                                <span class="tabular" style="font-weight: 500; color: var(--ink-muted);">{{ count($techs) }} {{ \Illuminate\Support\Str::plural('item', count($techs)) }}</span>
                            </div>
                            <div class="tech-grid">
                                @foreach($techs as $t)
                                    <div class="tech-card">
                                        <div class="tech-monogram">{{ strtoupper(\Illuminate\Support\Str::substr((string) ($t['name'] ?? '?'), 0, 2)) }}</div>
                                        <div class="tech-info">
                                            <div class="tech-name">{{ $t['name'] ?? 'Unknown' }}</div>
                                            <div class="tech-meta tabular">
                                                @if(!empty($t['version']))
                                                    <span>v{{ $t['version'] }}</span>
                                                @endif
                                                @if(isset($t['share']))
                                                    <span>{{ $t['share'] }}% share</span>
                                                @endif
                                                @if(empty($t['version']) && !isset($t['share']))
                                                    <span>Detected</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @elseif(!empty($tech['technologies']))
                    <div class="tech-grid">
                        @foreach($tech['technologies'] as $t)
                            <div class="tech-card">
                                <div class="tech-monogram">{{ strtoupper(\Illuminate\Support\Str::substr((string) ($t['name'] ?? '?'), 0, 2)) }}</div>
                                <div class="tech-info">
                                    <div class="tech-name">{{ $t['name'] ?? 'Unknown' }}</div>
                                    <div class="tech-meta">{{ implode(', ', !empty($t['categories']) ? $t['categories'] : ['Web Technology']) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-box">
                        No client or server technologies were detected from public headers and HTML markup.
                    </div>
                @endif
            </section>
        @endif

        <!-- Footer -->
        <footer class="report-footer">
            <span>Generated by <strong>SEO Analyst</strong> &bull; {{ $audit->domain }}</span>
            <span class="tabular">{{ now()->format('M d, Y H:i T') }} &bull; Confidential Technical Report</span>
        </footer>
    </main>
</body>
</html>
