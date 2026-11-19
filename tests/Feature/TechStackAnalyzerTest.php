<?php

use App\Services\TechStackAnalyzer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

it('distributes technology shares so each category totals 100 percent', function () {
    Process::fake();

    Http::fake([
        'https://example.com' => Http::response(<<<'HTML'
            <!DOCTYPE html>
            <html>
            <head><title>App</title></head>
            <body>
                <div id="__next"></div>
                <script src="/_next/static/chunks/main.js"></script>
            </body>
            </html>
            HTML),
    ]);

    $result = (new TechStackAnalyzer)->analyze('https://example.com');
    $grouped = $result['summary']['grouped'];

    foreach ($grouped as $technologies) {
        expect(array_sum(array_column($technologies, 'share')))->toBe(100);
    }

    $frameworks = collect($grouped['JavaScript Frameworks'] ?? []);
    $next = $frameworks->firstWhere('name', 'Next.js');
    $react = $frameworks->firstWhere('name', 'React');

    expect($next['share'])->toBe(50)
        ->and($react['share'])->toBe(50);
});

it('does not flag React just because the word react appears in markup', function () {
    Process::fake();

    Http::fake([
        'https://example.com' => Http::response(<<<'HTML'
            <!DOCTYPE html>
            <html>
            <head><title>Vue App</title></head>
            <body>
                <div id="app" data-v-app></div>
                <p>We react quickly to customer feedback.</p>
            </body>
            </html>
            HTML),
    ]);

    $result = (new TechStackAnalyzer)->analyze('https://example.com');
    $names = collect($result['summary']['technologies'])->pluck('name');

    expect($names)->toContain('Vue.js')
        ->and($names)->not->toContain('React');
});
