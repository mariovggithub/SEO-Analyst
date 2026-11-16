<?php

namespace App\Services;

use App\Services\Concerns\ConfiguresHttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class TechStackAnalyzer
{
    use ConfiguresHttpClient;

    /**
     * Detect the technology stack of a given URL.
     * Uses built-in intelligent fingerprinting + CLI if available.
     *
     * @return array{raw: array<string, mixed>, summary: array<string, mixed>}
     */
    public function analyze(string $url): array
    {
        $technologies = [];
        $raw = [];

        // 1. First, fetch headers and HTML directly using our resilient client
        try {
            $response = $this->client(20)->get($url);
            $headers = $response->headers();
            $html = $response->body();
            $statusCode = $response->status();

            $technologies = $this->detectTechnologies($url, $headers, $html);
            $raw['native_detection'] = [
                'status' => $statusCode,
                'detected_count' => count($technologies),
            ];
        } catch (\Throwable $e) {
            Log::warning("Native tech stack detection error: " . $e->getMessage());
        }

        // 2. Try Wappalyzer CLI if available (without blocking or failing)
        try {
            $result = Process::timeout(15)
                ->command(['npx', '--yes', 'wappalyzer-cli', $url])
                ->run();

            if ($result->successful()) {
                $cliData = json_decode($result->output(), true);
                if (! empty($cliData['technologies'])) {
                    $cliTechs = collect($cliData['technologies'])
                        ->map(fn (array $t) => [
                            'name' => $t['name'] ?? 'Unknown',
                            'categories' => collect($t['categories'] ?? [])->pluck('name')->all(),
                            'website' => $t['website'] ?? null,
                            'icon' => $t['icon'] ?? null,
                            'confidence' => $t['confidence'] ?? 100,
                            'version' => $t['version'] ?? null,
                        ])
                        ->all();

                    // Merge CLI results with native detections
                    $existingNames = collect($technologies)->pluck('name')->map(fn ($n) => strtolower($n))->all();
                    foreach ($cliTechs as $cTech) {
                        if (! in_array(strtolower($cTech['name']), $existingNames, true)) {
                            $technologies[] = $cTech;
                        }
                    }
                    $raw['wappalyzer_cli'] = $cliData;
                }
            }
        } catch (\Throwable) {
            // CLI not installed or failed; native detections handle 100% of the job
        }

        return [
            'raw' => $raw,
            'summary' => $this->formatSummary($technologies),
        ];
    }

    /**
     * In-depth native fingerprinting of web technologies.
     *
     * @param  array<string, array<int, string>>  $headers
     * @return array<int, array<string, mixed>>
     */
    private function detectTechnologies(string $url, array $headers, string $html): array
    {
        $techs = [];
        $headerString = strtolower(json_encode($headers));
        $htmlLower = strtolower($html);

        // Helper to add technology
        $add = function (string $name, array $categories, ?string $version = null, int $confidence = 100, ?string $website = null) use (&$techs) {
            foreach ($techs as $existing) {
                if (strtolower($existing['name']) === strtolower($name)) {
                    return;
                }
            }
            $techs[] = [
                'name' => $name,
                'categories' => $categories,
                'version' => $version,
                'confidence' => $confidence,
                'website' => $website,
            ];
        };

        // --- 1. CMS & Website Builders ---
        if (str_contains($htmlLower, 'wp-content') || str_contains($htmlLower, 'wp-includes') || str_contains($headerString, 'x-powered-by: wordpress')) {
            $version = null;
            if (preg_match('/content="wordpress\s*([0-9\.]+)"/i', $html, $m)) {
                $version = $m[1];
            }
            $add('WordPress', ['CMS', 'Blogs'], $version, 100, 'https://wordpress.org');
        }

        if (str_contains($htmlLower, 'cdn.shopify.com') || str_contains($htmlLower, 'shopify.theme') || str_contains($headerString, 'x-shopid')) {
            $add('Shopify', ['E-commerce', 'CMS'], null, 100, 'https://shopify.com');
        }

        if (str_contains($htmlLower, 'data-wf-page') || str_contains($htmlLower, 'data-wf-site') || str_contains($htmlLower, 'webflow.com')) {
            $add('Webflow', ['Website Builders', 'CMS'], null, 100, 'https://webflow.com');
        }

        if (str_contains($htmlLower, 'wix.com') || str_contains($headerString, 'x-wix-')) {
            $add('Wix', ['Website Builders', 'CMS'], null, 100, 'https://wix.com');
        }

        if (str_contains($htmlLower, 'squarespace.com') || str_contains($headerString, 'x-servedby: squarespace')) {
            $add('Squarespace', ['Website Builders', 'CMS'], null, 100, 'https://squarespace.com');
        }

        if (str_contains($headerString, 'x-drupal-cache') || str_contains($htmlLower, 'drupal.js')) {
            $add('Drupal', ['CMS'], null, 100, 'https://drupal.org');
        }

        // --- 2. JavaScript Frameworks & Libraries ---
        if (str_contains($htmlLower, '__next') || str_contains($htmlLower, '/_next/static')) {
            $add('Next.js', ['JavaScript Frameworks', 'Web Frameworks'], null, 100, 'https://nextjs.org');
            $add('React', ['JavaScript Frameworks'], null, 100, 'https://react.dev');
        } elseif (str_contains($htmlLower, 'react') || str_contains($htmlLower, 'react-dom') || str_contains($htmlLower, '_reactRootContainer')) {
            $add('React', ['JavaScript Frameworks'], null, 90, 'https://react.dev');
        }

        if (str_contains($htmlLower, '__nuxt') || str_contains($htmlLower, '/_nuxt/')) {
            $add('Nuxt.js', ['JavaScript Frameworks', 'Web Frameworks'], null, 100, 'https://nuxt.com');
            $add('Vue.js', ['JavaScript Frameworks'], null, 100, 'https://vuejs.org');
        } elseif (str_contains($htmlLower, 'data-v-') || str_contains($htmlLower, 'vue.global') || str_contains($htmlLower, '__vue_app__')) {
            $add('Vue.js', ['JavaScript Frameworks'], null, 95, 'https://vuejs.org');
        }

        if (str_contains($htmlLower, 'data-page=') || str_contains($htmlLower, 'inertiajs')) {
            $add('Inertia.js', ['JavaScript Frameworks'], null, 100, 'https://inertiajs.com');
        }

        if (str_contains($htmlLower, 'x-data=') || str_contains($htmlLower, 'alpinejs') || str_contains($htmlLower, 'alpine.min.js')) {
            $add('Alpine.js', ['JavaScript Frameworks'], null, 100, 'https://alpinejs.dev');
        }

        if (str_contains($htmlLower, 'jquery') || str_contains($htmlLower, 'jquery.min.js')) {
            $version = null;
            if (preg_match('/jquery[.-]([0-9\.]+)/i', $html, $m)) {
                $version = $m[1];
            }
            $add('jQuery', ['JavaScript Libraries'], $version, 95, 'https://jquery.com');
        }

        // --- 3. CSS Frameworks ---
        if (str_contains($htmlLower, 'tailwind') || str_contains($htmlLower, 'tailwindcss') || preg_match('/class="[^"]*(?:flex|grid|items-center|justify-between|text-center|py-|px-|bg-)[^"]*"/i', $html)) {
            $add('Tailwind CSS', ['CSS Frameworks'], null, 90, 'https://tailwindcss.com');
        }

        if (str_contains($htmlLower, 'bootstrap.min.css') || str_contains($htmlLower, 'bootstrap.bundle') || str_contains($htmlLower, 'navbar-expand')) {
            $add('Bootstrap', ['CSS Frameworks'], null, 95, 'https://getbootstrap.com');
        }

        // --- 4. Analytics & Tracking ---
        if (str_contains($htmlLower, 'googletagmanager.com/gtm.js') || str_contains($htmlLower, 'gtm-')) {
            $add('Google Tag Manager', ['Tag Managers'], null, 100, 'https://tagmanager.google.com');
        }

        if (str_contains($htmlLower, 'googletagmanager.com/gtag/js') || str_contains($htmlLower, 'google-analytics.com') || str_contains($htmlLower, "gtag('config'")) {
            $add('Google Analytics', ['Analytics'], 'GA4', 100, 'https://analytics.google.com');
        }

        if (str_contains($htmlLower, 'connect.facebook.net') || str_contains($htmlLower, 'fbq(')) {
            $add('Meta Pixel', ['Analytics', 'Advertising'], null, 100, 'https://facebook.com');
        }

        if (str_contains($htmlLower, 'static.hotjar.com') || str_contains($htmlLower, 'hjid')) {
            $add('Hotjar', ['Analytics'], null, 100, 'https://hotjar.com');
        }

        if (str_contains($htmlLower, 'clarity.ms')) {
            $add('Microsoft Clarity', ['Analytics'], null, 100, 'https://clarity.microsoft.com');
        }

        // --- 5. CDN, Proxy & Hosting ---
        if (str_contains($headerString, 'cloudflare') || str_contains($headerString, 'cf-ray') || str_contains($headerString, 'cf-cache-status')) {
            $add('Cloudflare', ['CDN', 'Security'], null, 100, 'https://cloudflare.com');
        }

        if (str_contains($headerString, 'cloudfront') || str_contains($headerString, 'x-amz-cf-id')) {
            $add('Amazon CloudFront', ['CDN'], null, 100, 'https://aws.amazon.com/cloudfront');
        }

        if (str_contains($headerString, 'x-vercel-id')) {
            $add('Vercel', ['PaaS', 'Hosting'], null, 100, 'https://vercel.com');
        }

        if (str_contains($headerString, 'x-nf-request-id')) {
            $add('Netlify', ['PaaS', 'Hosting'], null, 100, 'https://netlify.com');
        }

        // --- 6. Web Servers & Backend ---
        if (str_contains($headerString, 'server: nginx') || str_contains($headerString, 'nginx')) {
            $add('Nginx', ['Web Servers'], null, 90, 'https://nginx.org');
        }

        if (str_contains($headerString, 'server: apache') || str_contains($headerString, 'apache')) {
            $add('Apache', ['Web Servers'], null, 90, 'https://httpd.apache.org');
        }

        if (str_contains($headerString, 'litespeed')) {
            $add('LiteSpeed', ['Web Servers'], null, 100, 'https://litespeedtech.com');
        }

        if (str_contains($headerString, 'php') || str_contains($headerString, 'phpsessid')) {
            $add('PHP', ['Programming Languages'], null, 90, 'https://php.net');
        }

        if (str_contains($headerString, 'laravel_session') || str_contains($htmlLower, 'laravel')) {
            $add('Laravel', ['Web Frameworks'], null, 100, 'https://laravel.com');
        }

        // --- 7. Fonts & Icons ---
        if (str_contains($htmlLower, 'fonts.googleapis.com') || str_contains($htmlLower, 'fonts.gstatic.com')) {
            $add('Google Fonts', ['Font Scripts'], null, 100, 'https://fonts.google.com');
        }

        if (str_contains($htmlLower, 'font-awesome') || str_contains($htmlLower, 'fontawesome') || str_contains($htmlLower, 'fa-')) {
            $add('Font Awesome', ['Font Scripts'], null, 90, 'https://fontawesome.com');
        }

        return $techs;
    }

    /**
     * Format list of technologies into UI summary structure.
     *
     * @param  array<int, array<string, mixed>>  $technologies
     * @return array<string, mixed>
     */
    private function formatSummary(array $technologies): array
    {
        $grouped = collect($technologies)
            ->flatMap(function (array $tech) {
                if (empty($tech['categories'])) {
                    return [['category' => 'Other', 'tech' => $tech]];
                }

                return collect($tech['categories'])
                    ->map(fn (string $cat) => ['category' => $cat, 'tech' => $tech])
                    ->all();
            })
            ->groupBy('category')
            ->map(fn ($items) => $items->pluck('tech')->unique('name')->values()->all())
            ->all();

        return [
            'technologies' => $technologies,
            'grouped' => $grouped,
            'count' => count($technologies),
            'score' => $this->calculateScore($technologies),
        ];
    }

    /**
     * Calculate tech modernity & stack completeness score (0-100).
     *
     * @param  array<int, array<string, mixed>>  $technologies
     */
    private function calculateScore(array $technologies): int
    {
        if (empty($technologies)) {
            return 50;
        }

        $score = 50;

        $categories = collect($technologies)
            ->flatMap(fn (array $t) => $t['categories'] ?? [])
            ->map(fn (string $c) => strtolower($c))
            ->unique()
            ->all();

        // Bonus points for best-practice stack components
        if (in_array('cdn', $categories, true)) {
            $score += 12;
        }
        if (in_array('analytics', $categories, true)) {
            $score += 10;
        }
        if (in_array('tag managers', $categories, true)) {
            $score += 8;
        }
        if (in_array('javascript frameworks', $categories, true)) {
            $score += 10;
        }
        if (in_array('css frameworks', $categories, true)) {
            $score += 6;
        }
        if (in_array('security', $categories, true)) {
            $score += 8;
        }

        return (int) min(100, max(40, $score));
    }
}
