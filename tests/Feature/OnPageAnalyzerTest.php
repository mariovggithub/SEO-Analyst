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
        ->and($result['summary']['schema_markup']['types'])->toContain('LocalBusiness')
        ->and($result['summary']['schema_markup']['formats']['json_ld'])->toBe(1)
        ->and($result['summary']['schema_markup']['items'][0]['code'])->toContain('LocalBusiness')
        ->and($result['summary']['schema_markup']['has_local_business'])->toBeTrue()
        ->and($result['summary']['google_business_profile']['exists'])->toBeTrue()
        ->and($result['summary']['google_business_profile']['confidence'])->toBe('high')
        ->and($result['summary']['google_business_profile']['name'])->toBe('Acme Dental Studio')
        ->and($result['summary']['google_business_profile']['address'])->toContain('Jakarta')
        ->and($result['summary']['google_business_profile']['rating'])->toBe(4.8)
        ->and($result['summary']['google_business_profile']['review_count'])->toBe(127);
});

it('reports no schema markup or Google Business Profile when absent', function () {
    fakeOnPageSite('<html><head><title>Plain Site</title></head><body><h1>Hello</h1></body></html>');

    $result = (new OnPageAnalyzer)->analyze('https://example.com');

    expect($result['summary']['schema_markup']['found'])->toBeFalse()
        ->and($result['summary']['schema_markup']['total'])->toBe(0)
        ->and($result['summary']['google_business_profile']['exists'])->toBeFalse()
        ->and($result['summary']['google_business_profile']['confidence'])->toBe('none');
});

it('captures microdata markup code snippets', function () {
    fakeOnPageSite(<<<'HTML'
        <html>
        <head><title>Microdata</title></head>
        <body>
            <div itemscope itemtype="https://schema.org/Organization">
                <span itemprop="name">Acme Corp</span>
            </div>
        </body>
        </html>
        HTML);

    $result = (new OnPageAnalyzer)->analyze('https://example.com');
    $item = $result['summary']['schema_markup']['items'][0];

    expect($item['format'])->toBe('microdata')
        ->and($item['type'])->toBe('Organization')
        ->and($item['code'])->toContain('itemscope');
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
