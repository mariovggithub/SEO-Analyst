<?php

namespace App\Services;

use App\Services\Concerns\ConfiguresHttpClient;
use Illuminate\Support\Facades\Log;

class OnPageAnalyzer
{
    use ConfiguresHttpClient;

    /**
     * Analyze on-page SEO factors by fetching and parsing HTML.
     *
     * @return array{raw: array<string, mixed>, summary: array<string, mixed>}
     */
    public function analyze(string $url): array
    {
        $rawData = [];
        $summary = [];

        try {
            $response = $this->client(30)->get($url);

            if (! $response->successful()) {
                Log::warning('OnPage: Failed to fetch URL', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return $this->fallbackResult();
            }

            $html = $response->body();
            $rawData['status_code'] = $response->status();
            $rawData['headers'] = $response->headers();

            $dom = new \DOMDocument;
            @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

            $summary['meta'] = $this->extractMeta($dom);
            $summary['headings'] = $this->extractHeadings($dom);
            $summary['ssl'] = $this->checkSsl($url);
            $summary['sitemap'] = $this->checkSitemap($url);
            $summary['robots_txt'] = $this->checkRobotsTxt($url);
            $summary['images'] = $this->analyzeImages($dom);
            $summary['links'] = $this->analyzeLinks($dom, $url);
            $summary['score'] = $this->calculateScore($summary);

            $rawData['html_length'] = strlen($html);
        } catch (\Throwable $e) {
            Log::error('OnPage analysis exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackResult();
        }

        return [
            'raw' => $rawData,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractMeta(\DOMDocument $dom): array
    {
        $title = '';
        $titleTags = $dom->getElementsByTagName('title');
        if ($titleTags->length > 0) {
            $title = trim($titleTags->item(0)?->textContent ?? '');
        }

        $description = '';
        $canonical = '';
        $ogTags = [];

        $metaTags = $dom->getElementsByTagName('meta');
        foreach ($metaTags as $meta) {
            $name = strtolower($meta->getAttribute('name'));
            $property = strtolower($meta->getAttribute('property'));
            $content = $meta->getAttribute('content');

            if ($name === 'description' && empty($description)) {
                $description = $content;
            }

            if (str_starts_with($property, 'og:')) {
                $ogTags[$property] = $content;
            }
        }

        $linkTags = $dom->getElementsByTagName('link');
        foreach ($linkTags as $link) {
            if (strtolower($link->getAttribute('rel')) === 'canonical') {
                $canonical = $link->getAttribute('href');
            }
        }

        return [
            'title' => $title,
            'title_length' => mb_strlen($title),
            'description' => $description,
            'description_length' => mb_strlen($description),
            'canonical' => $canonical,
            'has_og_tags' => ! empty($ogTags),
            'og_tags' => $ogTags,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractHeadings(\DOMDocument $dom): array
    {
        $headings = [];

        foreach (['h1', 'h2', 'h3'] as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            $texts = [];
            foreach ($elements as $el) {
                $trimmed = trim(preg_replace('/\s+/', ' ', $el->textContent ?? ''));
                if (! empty($trimmed)) {
                    $texts[] = $trimmed;
                }
            }
            $headings[$tag] = [
                'count' => $elements->length,
                'texts' => array_slice($texts, 0, 5),
            ];
        }

        return $headings;
    }

    private function checkSsl(string $url): bool
    {
        return str_starts_with(strtolower($url), 'https://');
    }

    /**
     * @return array<string, mixed>
     */
    private function checkSitemap(string $url): array
    {
        $parsed = parse_url($url);
        $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        $sitemapUrl = $baseUrl . '/sitemap.xml';

        try {
            $response = $this->client(10)->get($sitemapUrl);

            return [
                'exists' => $response->successful(),
                'url' => $sitemapUrl,
                'status_code' => $response->status(),
            ];
        } catch (\Throwable) {
            return [
                'exists' => false,
                'url' => $sitemapUrl,
                'status_code' => null,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkRobotsTxt(string $url): array
    {
        $parsed = parse_url($url);
        $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        $robotsUrl = $baseUrl . '/robots.txt';

        try {
            $response = $this->client(10)->get($robotsUrl);

            return [
                'exists' => $response->successful(),
                'url' => $robotsUrl,
            ];
        } catch (\Throwable) {
            return [
                'exists' => false,
                'url' => $robotsUrl,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeImages(\DOMDocument $dom): array
    {
        $images = $dom->getElementsByTagName('img');
        $total = $images->length;
        $withoutAlt = 0;

        foreach ($images as $img) {
            if (! $img->hasAttribute('alt') || trim($img->getAttribute('alt')) === '') {
                $withoutAlt++;
            }
        }

        return [
            'total' => $total,
            'without_alt' => $withoutAlt,
            'alt_coverage' => $total > 0 ? (int) round((($total - $withoutAlt) / $total) * 100) : 100,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeLinks(\DOMDocument $dom, string $url): array
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        $links = $dom->getElementsByTagName('a');
        $internal = 0;
        $external = 0;
        $nofollow = 0;

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            $linkHost = parse_url($href, PHP_URL_HOST);

            if ($linkHost === null || $linkHost === $host) {
                $internal++;
            } else {
                $external++;
            }

            $rel = strtolower($link->getAttribute('rel'));
            if (str_contains($rel, 'nofollow')) {
                $nofollow++;
            }
        }

        return [
            'internal' => $internal,
            'external' => $external,
            'nofollow' => $nofollow,
            'total' => $internal + $external,
        ];
    }

    /**
     * Calculate on-page SEO score based on analyzed factors.
     *
     * @param  array<string, mixed>  $summary
     */
    private function calculateScore(array $summary): int
    {
        $score = 0;
        $maxScore = 0;

        // Title tag (15 pts)
        $maxScore += 15;
        $titleLength = $summary['meta']['title_length'] ?? 0;
        if ($titleLength >= 25 && $titleLength <= 70) {
            $score += 15;
        } elseif ($titleLength > 0) {
            $score += 8;
        }

        // Meta description (15 pts)
        $maxScore += 15;
        $descLength = $summary['meta']['description_length'] ?? 0;
        if ($descLength >= 100 && $descLength <= 170) {
            $score += 15;
        } elseif ($descLength > 0) {
            $score += 8;
        }

        // H1 tag (15 pts)
        $maxScore += 15;
        $h1Count = $summary['headings']['h1']['count'] ?? 0;
        if ($h1Count === 1) {
            $score += 15;
        } elseif ($h1Count > 1) {
            $score += 8;
        }

        // SSL (15 pts)
        $maxScore += 15;
        if ($summary['ssl'] ?? false) {
            $score += 15;
        }

        // Sitemap (10 pts)
        $maxScore += 10;
        if ($summary['sitemap']['exists'] ?? false) {
            $score += 10;
        }

        // Robots.txt (10 pts)
        $maxScore += 10;
        if ($summary['robots_txt']['exists'] ?? false) {
            $score += 10;
        }

        // Image alt coverage (10 pts)
        $maxScore += 10;
        $altCoverage = $summary['images']['alt_coverage'] ?? 100;
        $score += (int) round(($altCoverage / 100) * 10);

        // Open Graph tags (5 pts)
        $maxScore += 5;
        if ($summary['meta']['has_og_tags'] ?? false) {
            $score += 5;
        }

        // Canonical tag (5 pts)
        $maxScore += 5;
        if (! empty($summary['meta']['canonical'])) {
            $score += 5;
        }

        return $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0;
    }

    /**
     * @return array{raw: array<string, mixed>, summary: array<string, mixed>}
     */
    private function fallbackResult(): array
    {
        return [
            'raw' => [],
            'summary' => [
                'meta' => [
                    'title' => '',
                    'title_length' => 0,
                    'description' => '',
                    'description_length' => 0,
                    'canonical' => '',
                    'has_og_tags' => false,
                    'og_tags' => [],
                ],
                'headings' => [
                    'h1' => ['count' => 0, 'texts' => []],
                    'h2' => ['count' => 0, 'texts' => []],
                    'h3' => ['count' => 0, 'texts' => []],
                ],
                'ssl' => false,
                'sitemap' => ['exists' => false, 'url' => '', 'status_code' => null],
                'robots_txt' => ['exists' => false, 'url' => ''],
                'images' => ['total' => 0, 'without_alt' => 0, 'alt_coverage' => 100],
                'links' => ['internal' => 0, 'external' => 0, 'nofollow' => 0, 'total' => 0],
                'score' => 0,
            ],
        ];
    }
}
