<?php

use App\Services\OnPageAnalyzer;
use Illuminate\Support\Facades\Http;

function fakeOnPageSite(string $html): void
{
    Http::fake([
        'https://example.com/sitemap.xml' => Http::response('<urlset></urlset>', 200),
        'https://example.com/robots.txt' => Http::response("User-agent: *\nAllow: /", 200),
        'https://example.com' => Http::response($html, 200),
    ]);
}

it('reads schema markup and detects a Google Business Profile', function () {
    fakeOnPageSite(<<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <title>Acme Dental Studio</title>
            <meta name="description" content="Family dental care in Jakarta.">
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "LocalBusiness",
                "name": "Acme Dental Studio",
                "url": "https://example.com",
                "telephone": "+62 21 555 0100",
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": "Jl. Sudirman 1",
                    "addressLocality": "Jakarta",
                    "postalCode": "10220",
                    "addressCountry": "ID"
                },
                "aggregateRating": {
                    "@type": "AggregateRating",
                    "ratingValue": "4.8",
                    "reviewCount": "127"
                }
            }
            </script>
        </head>
        <body>
            <h1>Acme Dental Studio</h1>
            <iframe src="https://www.google.com/maps/embed?pb=abc"></iframe>
        </body>
        </html>
        HTML);

    $result = (new OnPageAnalyzer)->analyze('https://example.com');

    expect($result['summary']['schema_markup']['found'])->toBeTrue()
        ->and($result['summary']['schema_markup']['valid'])->toBeTrue()
        ->and($result['summary']['schema_markup']['formats']['json_ld'])->toBe(1)
        ->and($result['summary']['schema_markup']['format_evidence']['json_ld'])->toContain('application/ld+json')
        ->and($result['summary']['schema_markup']['json_ld_blocks'][0]['types'])->toContain('LocalBusiness')
        ->and($result['summary']['schema_markup']['primary']['code'])->toContain('LocalBusiness')
        ->and($result['summary']['schema_markup']['primary']['type'])->toBe('LocalBusiness')
        ->and($result['summary']['schema_markup']['has_local_business'])->toBeTrue()
        ->and($result['summary']['google_business_profile']['exists'])->toBeTrue()
        ->and($result['summary']['google_business_profile']['confidence'])->toBe('high')
        ->and($result['summary']['google_business_profile']['name'])->toBe('Acme Dental Studio')
        ->and($result['summary']['google_business_profile']['address'])->toContain('Jakarta')
        ->and($result['summary']['google_business_profile']['rating'])->toBe(4.8)
        ->and($result['summary']['google_business_profile']['review_count'])->toBe(127);
});

it('picks one primary schema item when several are present', function () {
    fakeOnPageSite(<<<'HTML'
        <html>
        <head>
            <title>Multi Schema</title>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@graph": [
                    { "@type": "WebSite", "name": "Acme", "url": "https://example.com" },
                    { "@type": "Organization", "name": "Acme Corp" },
                    { "@type": "LocalBusiness", "name": "Acme Jakarta" }
                ]
            }
            </script>
        </head>
        <body><h1>Multi Schema</h1></body>
        </html>
        HTML);

    $result = (new OnPageAnalyzer)->analyze('https://example.com');

    expect($result['summary']['schema_markup']['primary']['type'])->toBe('LocalBusiness')
        ->and($result['summary']['schema_markup']['primary']['name'])->toBe('Acme Jakarta')
        ->and($result['summary']['schema_markup']['json_ld_blocks'])->toHaveCount(1)
        ->and($result['summary']['schema_markup']['json_ld_blocks'][0]['types'])->toContain('WebSite', 'Organization', 'LocalBusiness')
        ->and($result['summary']['schema_markup']['formats']['json_ld'])->toBe(3)
        ->and(array_key_exists('items', $result['summary']['schema_markup']))->toBeFalse();
});

it('lists only parent JSON-LD types once per unique code block', function () {
    $json = '{"@context":"https://schema.org","@graph":[{"@type":"WebPage","name":"Home","primaryImageOfPage":{"@type":"ImageObject","url":"https://example.com/a.jpg"}},{"@type":"BreadcrumbList","itemListElement":[]}]}';

    fakeOnPageSite(
        '<html><head><title>Types</title>'
        .'<script type="application/ld+json">'.$json.'</script>'
        .'<script type="application/ld+json">'.$json.'</script>'
        .'</head><body><h1>Types</h1></body></html>'
    );

    $schema = (new OnPageAnalyzer)->analyze('https://example.com')['summary']['schema_markup'];

    expect($schema['json_ld_blocks'])->toHaveCount(1)
        ->and($schema['json_ld_blocks'][0]['types'])->toBe(['WebPage', 'BreadcrumbList'])
        ->and($schema['json_ld_blocks'][0]['types'])->not->toContain('ImageObject')
        ->and($schema['formats']['json_ld'])->toBe(2);
});

it('reports no schema markup or Google Business Profile when absent', function () {
    fakeOnPageSite('<html><head><title>Plain Site</title></head><body><h1>Hello</h1></body></html>');

    $result = (new OnPageAnalyzer)->analyze('https://example.com');

    expect($result['summary']['schema_markup']['found'])->toBeFalse()
        ->and($result['summary']['schema_markup']['primary'])->toBeNull()
        ->and($result['summary']['google_business_profile']['exists'])->toBeFalse()
        ->and($result['summary']['google_business_profile']['confidence'])->toBe('none');
});

it('captures microdata and RDFa detection evidence', function () {
    fakeOnPageSite(<<<'HTML'
        <html>
        <head><title>Microdata</title></head>
        <body>
            <div itemscope itemtype="https://schema.org/Organization">
                <span itemprop="name">Acme Corp</span>
            </div>
            <div typeof="https://schema.org/Person">Jane</div>
        </body>
        </html>
        HTML);

    $result = (new OnPageAnalyzer)->analyze('https://example.com');
    $schema = $result['summary']['schema_markup'];

    expect($schema['primary']['format'])->toBe('microdata')
        ->and($schema['primary']['type'])->toBe('Organization')
        ->and($schema['primary']['code'])->toContain('itemscope')
        ->and($schema['format_evidence']['microdata'])->toContain('itemscope')
        ->and($schema['format_evidence']['microdata'])->toContain('itemtype')
        ->and($schema['format_evidence']['rdfa'])->toContain('typeof');
});

it('records invalid JSON-LD without failing the analysis', function () {
    fakeOnPageSite(<<<'HTML'
        <html>
        <head>
            <title>Broken Schema</title>
            <script type="application/ld+json">{ "@type": "Organization", }</script>
        </head>
        <body><h1>Broken Schema</h1></body>
        </html>
        HTML);

    $result = (new OnPageAnalyzer)->analyze('https://example.com');

    expect($result['summary']['schema_markup']['valid'])->toBeFalse()
        ->and($result['summary']['schema_markup']['errors'])->not->toBeEmpty()
        ->and($result['summary']['schema_markup']['found'])->toBeFalse();
});
