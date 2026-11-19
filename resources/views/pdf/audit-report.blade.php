<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Audit Report — {{ $audit->domain }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1a1a1a;
            font-size: 12px;
            line-height: 1.5;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            color: #2563eb;
        }
        .header .meta {
            text-align: right;
            color: #666;
            font-size: 11px;
        }
        .score-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            font-size: 28px;
            font-weight: bold;
            color: white;
        }
        .score-green { background: #22c55e; }
        .score-yellow { background: #eab308; }
        .score-red { background: #ef4444; }
        .section {
            margin-bottom: 24px;
        }
        .section h2 {
            font-size: 16px;
            color: #2563eb;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .section h3 {
            font-size: 13px;
            margin-bottom: 8px;
            color: #374151;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .metric-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .metric-card .value {
            font-size: 20px;
            font-weight: bold;
        }
        .metric-card .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        .check-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .check-pass { color: #22c55e; }
        .check-fail { color: #ef4444; }
        .tech-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .tech-item {
            background: #f9fafb;
            border-radius: 6px;
            padding: 8px;
            font-size: 11px;
        }
        .tech-item .name { font-weight: 600; }
        .tech-item .cat { color: #666; font-size: 10px; }
        .schema-code {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            font-size: 9px;
            line-height: 1.4;
            white-space: pre-wrap;
            word-break: break-word;
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #999;
            font-size: 10px;
        }
        .page-break { page-break-before: always; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        @page {
            size: A4;
            margin: 15mm;
        }
        .no-print-bar {
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
            margin: -40px -40px 30px -40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }
        .btn-print {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
        }
        .btn-print:hover { background: #1d4ed8; }
        .btn-back {
            color: #94a3b8;
            text-decoration: none;
        }
        .btn-back:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="no-print no-print-bar">
        <span>Competitor & Lead SEO Intelligence Tool &bull; Executive Summary</span>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="{{ route('audits.show', $audit) }}" class="btn-back">&larr; Back to Audit</a>
            <button onclick="window.print()" class="btn-print">&#128438; Print / Save as PDF</button>
        </div>
    </div>
    <!-- Page 1: Executive Summary -->
    <div class="header">
        <div>
            <h1>Executive SEO Report</h1>
            <p style="color: #666; margin-top: 4px;">{{ $audit->domain }}</p>
            <p style="color: #999; font-size: 10px; margin-top: 2px;">{{ $audit->url }}</p>
        </div>
        <div class="meta">
            <div class="score-badge {{ $audit->overall_score >= 90 ? 'score-green' : ($audit->overall_score >= 50 ? 'score-yellow' : 'score-red') }}">
                {{ $audit->overall_score ?? '—' }}
            </div>
            <p style="margin-top: 4px;">Overall Score</p>
            <p>{{ $audit->created_at->format('M d, Y H:i') }}</p>
        </div>
    </div>

    @php
        $perf = $results->get('performance');
        $tech = $results->get('tech_stack');
        $onPage = $results->get('on_page');
    @endphp

    <!-- Performance Section -->
    @if($perf)
    <div class="section">
        <h2>Performance & Core Web Vitals</h2>

        <div class="metrics-grid">
            @foreach(['mobile', 'desktop'] as $strategy)
                @if(isset($perf[$strategy]))
                <div class="metric-card">
                    <div class="label">{{ ucfirst($strategy) }} Performance</div>
                    <div class="value" style="color: {{ ($perf[$strategy]['scores']['performance'] ?? 0) >= 90 ? '#22c55e' : (($perf[$strategy]['scores']['performance'] ?? 0) >= 50 ? '#eab308' : '#ef4444') }}">
                        {{ $perf[$strategy]['scores']['performance'] ?? 0 }}
                    </div>
                </div>
                @endif
            @endforeach
        </div>

        @if(isset($perf['mobile']['core_web_vitals']))
        <h3>Mobile Core Web Vitals</h3>
        <div class="metrics-grid">
            @foreach([
                'fcp' => 'FCP',
                'lcp' => 'LCP',
                'cls' => 'CLS',
                'tbt' => 'TBT',
            ] as $key => $label)
                <div class="metric-card">
                    <div class="label">{{ $label }}</div>
                    <div class="value">
                        @if($key === 'cls')
                            {{ number_format((float) ($perf['mobile']['core_web_vitals'][$key] ?? 0), 3) }}
                        @else
                            {{ number_format(((float) ($perf['mobile']['core_web_vitals'][$key] ?? 0)) / 1000, 1) }}s
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    <!-- On-Page SEO Section -->
    @if($onPage)
    <div class="section">
        <h2>On-Page SEO Analysis</h2>

        <div class="check-item">
            <span class="{{ ($onPage['meta']['title_length'] ?? 0) > 0 ? 'check-pass' : 'check-fail' }}">
                {{ ($onPage['meta']['title_length'] ?? 0) > 0 ? '✓' : '✗' }}
            </span>
            <span>Title Tag: "{{ Str::limit($onPage['meta']['title'] ?? '—', 60) }}" ({{ $onPage['meta']['title_length'] ?? 0 }} chars)</span>
        </div>
        <div class="check-item">
            <span class="{{ ($onPage['meta']['description_length'] ?? 0) > 0 ? 'check-pass' : 'check-fail' }}">
                {{ ($onPage['meta']['description_length'] ?? 0) > 0 ? '✓' : '✗' }}
            </span>
            <span>Meta Description ({{ $onPage['meta']['description_length'] ?? 0 }} chars)</span>
        </div>
        <div class="check-item">
            <span class="{{ ($onPage['ssl'] ?? false) ? 'check-pass' : 'check-fail' }}">
                {{ ($onPage['ssl'] ?? false) ? '✓' : '✗' }}
            </span>
            <span>SSL / HTTPS</span>
        </div>
        <div class="check-item">
            <span class="{{ ($onPage['sitemap']['exists'] ?? false) ? 'check-pass' : 'check-fail' }}">
                {{ ($onPage['sitemap']['exists'] ?? false) ? '✓' : '✗' }}
            </span>
            <span>Sitemap.xml</span>
        </div>
        <div class="check-item">
            <span class="{{ ($onPage['robots_txt']['exists'] ?? false) ? 'check-pass' : 'check-fail' }}">
                {{ ($onPage['robots_txt']['exists'] ?? false) ? '✓' : '✗' }}
            </span>
            <span>Robots.txt</span>
        </div>
        <div class="check-item">
            <span class="{{ ($onPage['meta']['has_og_tags'] ?? false) ? 'check-pass' : 'check-fail' }}">
                {{ ($onPage['meta']['has_og_tags'] ?? false) ? '✓' : '✗' }}
            </span>
            <span>Open Graph Tags</span>
        </div>
        <div class="check-item">
            <span class="{{ !empty($onPage['meta']['canonical']) ? 'check-pass' : 'check-fail' }}">
                {{ !empty($onPage['meta']['canonical']) ? '✓' : '✗' }}
            </span>
            <span>Canonical Tag</span>
        </div>
        <div class="check-item">
            <span class="{{ ($onPage['schema_markup']['found'] ?? false) ? 'check-pass' : 'check-fail' }}">
                {{ ($onPage['schema_markup']['found'] ?? false) ? '✓' : '✗' }}
            </span>
            <span>
                Schema Markup
                @if($onPage['schema_markup']['found'] ?? false)
                    ({{ $onPage['schema_markup']['total'] ?? 0 }} item(s)@if(!empty($onPage['schema_markup']['types'])), {{ implode(', ', array_slice($onPage['schema_markup']['types'], 0, 5)) }}@endif)
                @endif
            </span>
        </div>
        <div class="check-item">
            <span class="{{ ($onPage['google_business_profile']['exists'] ?? false) ? 'check-pass' : 'check-fail' }}">
                {{ ($onPage['google_business_profile']['exists'] ?? false) ? '✓' : '✗' }}
            </span>
            <span>
                Google Business Profile:
                {{ ($onPage['google_business_profile']['exists'] ?? false) ? 'Available' : 'Not available' }}
            </span>
        </div>
    </div>
    @endif

    @if($onPage && (!empty($onPage['schema_markup']) || !empty($onPage['google_business_profile'])))
    <div class="section">
        <h2>Structured Data &amp; Local Presence</h2>

        @php($schema = $onPage['schema_markup'] ?? [])
        @php($gbp = $onPage['google_business_profile'] ?? [])

        <h3>Schema Markup</h3>
        <p style="margin-bottom: 10px;">
            {{ ($schema['found'] ?? false) ? 'Structured data detected on the page.' : 'No structured data detected on the page.' }}
        </p>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="value">{{ $schema['total'] ?? 0 }}</div>
                <div class="label">Total Items</div>
            </div>
            <div class="metric-card">
                <div class="value">{{ $schema['formats']['json_ld'] ?? 0 }}</div>
                <div class="label">JSON-LD</div>
            </div>
            <div class="metric-card">
                <div class="value">{{ ($schema['formats']['microdata'] ?? 0) + ($schema['formats']['rdfa'] ?? 0) }}</div>
                <div class="label">Microdata / RDFa</div>
            </div>
        </div>

        @if(!empty($schema['types']))
            <div class="check-item" style="flex-wrap: wrap;">
                <strong>Types:</strong>
                <span>{{ implode(', ', $schema['types']) }}</span>
            </div>
        @endif

        @if(!empty($schema['items']))
            <div class="tech-list" style="margin-top: 10px;">
                @foreach($schema['items'] as $item)
                <div class="tech-item">
                    <div class="name">{{ $item['type'] }}</div>
                    @if(!empty($item['name']))
                    <div class="cat">{{ Str::limit($item['name'], 40) }}</div>
                    @endif
                    <div class="cat">{{ strtoupper(str_replace('_', '-', $item['format'])) }}</div>
                </div>
                @endforeach
            </div>
        @endif

        @php($codeItems = collect($schema['items'] ?? [])->filter(fn ($item) => ! empty($item['code']))->take(3))
        @if($codeItems->isNotEmpty())
            <div style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-top: 10px;">
                <strong style="font-size: 11px;">ProfessionalService vs WebSite</strong>
                <p style="color: #444; font-size: 10px; margin-top: 4px;">
                    <strong>WebSite</strong> describes the website itself (name, URL, language, optional site search) and is usually placed once on the homepage.
                    <strong>ProfessionalService</strong> describes the business behind the site (a LocalBusiness subtype) with name, address, phone, area served, and price range.
                    Both can coexist: WebSite is not a Google Business Profile signal, while ProfessionalService is.
                </p>
            </div>

            <h3 style="margin-top: 16px;">Schema Code Samples</h3>
            @foreach($codeItems as $item)
                <p style="font-size: 10px; color: #666; margin-bottom: 4px;">
                    {{ $item['type'] }} &middot; {{ strtoupper(str_replace('_', '-', $item['format'])) }}
                </p>
                <pre class="schema-code">{{ Str::limit($item['code'], 1200) }}</pre>
            @endforeach
        @endif

        @if(!($schema['valid'] ?? true))
            <p style="color: #ef4444; margin-top: 8px;">{{ implode('; ', $schema['errors'] ?? []) }}</p>
        @endif

        <h3 style="margin-top: 16px;">Google Business Profile</h3>
        <div class="check-item">
            <span class="{{ ($gbp['exists'] ?? false) ? 'check-pass' : 'check-fail' }}">
                {{ ($gbp['exists'] ?? false) ? '✓' : '✗' }}
            </span>
            <span>
                <strong>{{ ($gbp['exists'] ?? false) ? 'Available' : 'Not available' }}</strong>
                <span style="color: #666;">({{ $gbp['confidence'] ?? 'none' }} confidence)</span>
            </span>
        </div>

        @if(!empty($gbp['name']))
            <div class="check-item"><strong>Name:</strong> <span>{{ $gbp['name'] }}</span></div>
        @endif
        @if(!empty($gbp['address']))
            <div class="check-item"><strong>Address:</strong> <span>{{ $gbp['address'] }}</span></div>
        @endif
        @if(!empty($gbp['phone']))
            <div class="check-item"><strong>Phone:</strong> <span>{{ $gbp['phone'] }}</span></div>
        @endif
        @if(($gbp['rating'] ?? null) !== null)
            <div class="check-item">
                <strong>Rating:</strong>
                <span>{{ $gbp['rating'] }}@if(!empty($gbp['review_count'])) ({{ $gbp['review_count'] }} reviews)@endif</span>
            </div>
        @endif
        @if(!empty($gbp['signals']))
            <div class="check-item" style="flex-wrap: wrap;">
                <strong>Signals:</strong>
                <span>{{ implode(', ', $gbp['signals']) }}</span>
            </div>
        @endif
    </div>
    @endif

    <div class="page-break"></div>

    <!-- Page 2: Tech Stack -->
    @if($tech)
    <div class="section">
        <h2>Technology Stack ({{ $tech['count'] ?? 0 }} detected)</h2>

        @if(!empty($tech['grouped']))
            @foreach($tech['grouped'] as $category => $techs)
            <h3>{{ $category }}</h3>
            <div class="tech-list">
                @foreach($techs as $t)
                <div class="tech-item">
                    <div class="name">{{ $t['name'] }}</div>
                    @if(!empty($t['version']))
                    <div class="cat">v{{ $t['version'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endforeach
        @elseif(!empty($tech['technologies']))
            <div class="tech-list">
                @foreach($tech['technologies'] as $t)
                <div class="tech-item">
                    <div class="name">{{ $t['name'] }}</div>
                    <div class="cat">{{ implode(', ', $t['categories'] ?? []) }}</div>
                </div>
                @endforeach
            </div>
        @else
            <p style="color: #666;">No technologies detected.</p>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>Generated by SEO Analyst — {{ now()->format('M d, Y H:i T') }}</p>
        <p>This report is confidential and intended for internal use only.</p>
    </div>
</body>
</html>

