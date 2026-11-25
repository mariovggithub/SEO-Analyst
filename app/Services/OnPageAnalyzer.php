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
            $summary['schema_markup'] = $this->extractSchemaMarkup($dom);
            $summary['google_business_profile'] = $this->detectGoogleBusinessProfile($html, $summary['schema_markup']);
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
            $title = trim($titleTags->item(0)->textContent ?? '');
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
        $baseUrl = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');
        $sitemapUrl = $baseUrl.'/sitemap.xml';

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
        $baseUrl = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');
        $robotsUrl = $baseUrl.'/robots.txt';

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
     * Extract structured data (JSON-LD, microdata, and RDFa) from the document.
     *
     * @return array<string, mixed>
     */
    private function extractSchemaMarkup(\DOMDocument $dom): array
    {
        $items = [];
        $errors = [];
        $formats = ['json_ld' => 0, 'microdata' => 0, 'rdfa' => 0];
        $formatEvidence = ['json_ld' => null, 'microdata' => null, 'rdfa' => null];
        $jsonLdBlocks = [];

        foreach ($dom->getElementsByTagName('script') as $script) {
            if (strtolower(trim($script->getAttribute('type'))) !== 'application/ld+json') {
                continue;
            }

            $json = trim($script->textContent ?? '');
            if ($json === '') {
                continue;
            }

            $decoded = json_decode($json, true);

            if (! is_array($decoded)) {
                $errors[] = 'Invalid JSON-LD syntax: '.json_last_error_msg();

                continue;
            }

            $blockTypes = [];

            foreach ($this->flattenSchemaNodes($decoded) as $node) {
                $types = $this->schemaTypes($node['@type'] ?? null);
                if ($types === []) {
                    continue;
                }

                foreach ($types as $type) {
                    $blockTypes[$type] = $type;
                }

                $items[] = $this->schemaItem('json_ld', $node, $types, $json);
                $formatEvidence['json_ld'] ??= $this->openingTag($script);
            }

            if ($blockTypes !== []) {
                $jsonLdBlocks[md5($json)] = [
                    'code' => $this->truncateMarkup($json),
                    'types' => array_values($blockTypes),
                ];
            }
        }

        $formats['json_ld'] = collect($jsonLdBlocks)
            ->flatMap(fn (array $block) => $block['types'])
            ->unique()
            ->count();

        foreach ($dom->getElementsByTagName('*') as $element) {
            // Microdata: itemscope + itemtype
            if ($element->hasAttribute('itemscope') && $element->hasAttribute('itemtype')) {
                $types = $this->schemaTypes($element->getAttribute('itemtype'));
                if ($types !== []) {
                    $items[] = [
                        'format' => 'microdata',
                        'type' => $types[0],
                        'types' => $types,
                        'name' => $this->microdataValue($element, 'name'),
                        'url' => $this->microdataValue($element, 'url'),
                        'phone' => $this->microdataValue($element, 'telephone'),
                        'address' => $this->microdataValue($element, 'address'),
                        'rating' => null,
                        'review_count' => null,
                        'code' => $this->truncateMarkup($dom->saveHTML($element) ?: null),
                    ];
                    $formats['microdata']++;
                    $formatEvidence['microdata'] ??= $this->openingTag($element);
                }
            }

            // RDFa: typeof attribute
            $typeof = trim($element->getAttribute('typeof'));
            if ($typeof !== '') {
                $types = $this->schemaTypes($typeof);
                if ($types !== []) {
                    $items[] = [
                        'format' => 'rdfa',
                        'type' => $types[0],
                        'types' => $types,
                        'name' => trim($element->getAttribute('property') === 'name' ? $element->textContent : '') ?: null,
                        'url' => $element->getAttribute('about') ?: null,
                        'phone' => null,
                        'address' => null,
                        'rating' => null,
                        'review_count' => null,
                        'code' => $this->truncateMarkup($dom->saveHTML($element) ?: null),
                    ];
                    $formats['rdfa']++;
                    $formatEvidence['rdfa'] ??= $this->openingTag($element);
                }
            }
        }

        // Remove duplicates caused by the same item being described twice.
        $items = collect($items)
            ->unique(fn (array $item) => $item['format'].'|'.strtolower($item['type']).'|'.strtolower((string) $item['name']))
            ->values()
            ->all();

        $primary = collect($items)->first(fn (array $item) => collect($item['types'])->contains(
            fn (string $type) => $this->isLocalBusinessType($type)
        )) ?? collect($items)->first(fn (array $item) => in_array('Organization', $item['types'], true))
            ?? collect($items)->first(fn (array $item) => in_array('WebSite', $item['types'], true))
            ?? ($items[0] ?? null);

        return [
            'found' => $primary !== null,
            'formats' => $formats,
            'format_evidence' => $formatEvidence,
            'json_ld_blocks' => array_values($jsonLdBlocks),
            'primary' => $primary,
            'valid' => $errors === [],
            'errors' => $errors,
            'has_local_business' => collect($items)->contains(fn (array $item) => collect($item['types'])->contains(
                fn (string $type) => $this->isLocalBusinessType($type)
            )),
        ];
    }

    /**
     * Capture the opening tag that proves a schema format is present.
     */
    private function openingTag(\DOMElement $element): ?string
    {
        $html = trim($element->ownerDocument?->saveHTML($element) ?: '');

        if ($html === '' || ! preg_match('/^<[^>]+>/', $html, $matches)) {
            return null;
        }

        return $this->truncateMarkup($matches[0], 300);
    }

    /**
     * Flatten a decoded JSON-LD payload into individual schema nodes.
     *
     * @return array<int, array<string, mixed>>
     */
    private function flattenSchemaNodes(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            $nodes = [];
            foreach ($data as $node) {
                $nodes = array_merge($nodes, $this->flattenSchemaNodes($node));
            }

            return $nodes;
        }

        $nodes = [];

        if (isset($data['@graph']) && is_array($data['@graph'])) {
            $nodes = array_merge($nodes, $this->flattenSchemaNodes($data['@graph']));
        }

        if (isset($data['@type'])) {
            $nodes[] = $data;
        }

        return $nodes;
    }

    /**
     * Normalize a schema type value into a list of plain type names.
     *
     * @return array<int, string>
     */
    private function schemaTypes(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\s+/', trim($value)) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($type) => is_string($type) && trim($type) !== '')
            ->map(fn (string $type) => trim(preg_replace('#^https?://(?:www\.)?schema\.org/#i', '', $type)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Build a normalized schema item from a JSON-LD node.
     *
     * @param  array<string, mixed>  $node
     * @param  array<int, string>  $types
     * @return array<string, mixed>
     */
    private function schemaItem(string $format, array $node, array $types, ?string $code = null): array
    {
        $rating = null;
        $reviewCount = null;

        if (isset($node['aggregateRating']) && is_array($node['aggregateRating'])) {
            $rating = is_numeric($node['aggregateRating']['ratingValue'] ?? null)
                ? (float) $node['aggregateRating']['ratingValue']
                : null;
            $reviewCount = is_numeric($node['aggregateRating']['reviewCount'] ?? $node['aggregateRating']['ratingCount'] ?? null)
                ? (int) ($node['aggregateRating']['reviewCount'] ?? $node['aggregateRating']['ratingCount'])
                : null;
        }

        return [
            'format' => $format,
            'type' => $types[0],
            'types' => $types,
            'name' => $this->stringValue($node['name'] ?? $node['headline'] ?? null),
            'url' => $this->stringValue($node['url'] ?? $node['@id'] ?? null),
            'phone' => $this->stringValue($node['telephone'] ?? null),
            'address' => $this->formatAddress($node['address'] ?? null),
            'rating' => $rating,
            'review_count' => $reviewCount,
            'code' => $this->truncateMarkup($code),
        ];
    }

    /**
     * Limit stored markup so a single audit does not become excessively large.
     */
    private function truncateMarkup(?string $markup, int $limit = 5000): ?string
    {
        if ($markup === null) {
            return null;
        }

        $markup = trim($markup);

        if ($markup === '') {
            return null;
        }

        return mb_strlen($markup) > $limit
            ? mb_substr($markup, 0, $limit).'…'
            : $markup;
    }

    private function stringValue(mixed $value): ?string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (is_array($value) && isset($value['name']) && is_string($value['name'])) {
            return trim($value['name']);
        }

        return null;
    }

    /**
     * Format a schema.org PostalAddress (or plain string) into one line.
     */
    private function formatAddress(mixed $address): ?string
    {
        if (is_string($address)) {
            return trim($address) !== '' ? trim($address) : null;
        }

        if (! is_array($address)) {
            return null;
        }

        $parts = collect([
            $this->stringValue($address['streetAddress'] ?? null),
            $this->stringValue($address['addressLocality'] ?? null),
            $this->stringValue($address['addressRegion'] ?? null),
            $this->stringValue($address['postalCode'] ?? null),
            $this->stringValue($address['addressCountry'] ?? null),
        ])->filter()->values();

        return $parts->isEmpty() ? null : $parts->implode(', ');
    }

    /**
     * Read a nested microdata property value from an itemscope element.
     */
    private function microdataValue(\DOMElement $root, string $property): ?string
    {
        $elements = [$root];

        foreach ($root->getElementsByTagName('*') as $element) {
            $elements[] = $element;
        }

        foreach ($elements as $element) {
            if ($element->getAttribute('itemprop') !== $property) {
                continue;
            }

            $value = trim(
                $element->getAttribute('content')
                ?: $element->getAttribute('href')
                ?: $element->getAttribute('datetime')
                ?: $element->textContent
            );

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function isLocalBusinessType(string $type): bool
    {
        $type = strtolower($type);

        $knownTypes = [
            'localbusiness',
            'restaurant',
            'store',
            'dentist',
            'medicalbusiness',
            'professionalservice',
            'legalservice',
            'healthandbeautybusiness',
            'homeandconstructionbusiness',
            'automotivebusiness',
            'financialservice',
            'foodestablishment',
            'lodgingbusiness',
            'realestateagent',
            'travelagency',
        ];

        return in_array($type, $knownTypes, true)
            || str_ends_with($type, 'business')
            || str_ends_with($type, 'restaurant')
            || str_ends_with($type, 'store');
    }

    /**
     * Detect whether the page exposes signals of a Google Business Profile.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function detectGoogleBusinessProfile(string $html, array $schema): array
    {
        $htmlLower = strtolower($html);
        $signals = [];
        $confidence = 0;
        $profile = [
            'name' => null,
            'address' => null,
            'phone' => null,
            'url' => null,
            'rating' => null,
            'review_count' => null,
            'place_id' => null,
        ];

        $localBusiness = null;
        $primary = $schema['primary'] ?? ($schema['items'][0] ?? null);

        if (is_array($primary) && collect($primary['types'] ?? [$primary['type'] ?? ''])->contains(
            fn ($type) => $this->isLocalBusinessType((string) $type)
        )) {
            $localBusiness = $primary;
        }

        if ($localBusiness !== null) {
            $signals[] = 'LocalBusiness structured data';
            $confidence = max($confidence, 80);

            foreach (['name', 'address', 'phone', 'url', 'rating', 'review_count'] as $key) {
                $profile[$key] = $localBusiness[$key] ?? null;
            }
        }

        if (str_contains($htmlLower, 'google.com/maps/embed') || str_contains($htmlLower, 'maps.google.com/maps')) {
            $signals[] = 'Google Maps embed';
            $confidence = max($confidence, 60);
        } elseif (str_contains($htmlLower, 'google.com/maps') || str_contains($htmlLower, 'maps.app.goo.gl')) {
            $signals[] = 'Google Maps link';
            $confidence = max($confidence, 55);
        }

        if (str_contains($htmlLower, 'g.page/') || str_contains($htmlLower, 'business.google.com')) {
            $signals[] = 'Google Business Profile link';
            $confidence = max($confidence, 65);
        }

        if (preg_match('/!1s(0x[0-9a-f]+:0x[0-9a-f]+)/i', $html, $matches)) {
            $profile['place_id'] = $matches[1];
            $signals[] = 'Google Maps place identifier';
            $confidence = max($confidence, 70);
        } elseif (preg_match('/place_id[=:"\']+([a-z0-9_\-]{10,})/i', $html, $matches)) {
            $profile['place_id'] = $matches[1];
            $signals[] = 'Google Maps Place ID';
            $confidence = max($confidence, 60);
        }

        if (str_contains($htmlLower, 'google reviews') || str_contains($htmlLower, 'google review')) {
            $signals[] = 'Google Reviews widget';
            $confidence = max($confidence, 45);
        }

        if (preg_match('/google\s+(business\s+profile|my\s+business)/i', $html)) {
            $signals[] = 'Google Business Profile mention';
            $confidence = max($confidence, 25);
        }

        $signals = array_values(array_unique($signals));

        return [
            'exists' => $confidence >= 40,
            'confidence' => match (true) {
                $confidence >= 70 => 'high',
                $confidence >= 40 => 'medium',
                $confidence > 0 => 'low',
                default => 'none',
            },
            'signals' => $signals,
            ...$profile,
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

        // Schema markup (5 pts)
        $maxScore += 5;
        if ($summary['schema_markup']['found'] ?? false) {
            $score += ($summary['schema_markup']['valid'] ?? true) ? 5 : 3;
        }

        // Google Business Profile (5 pts)
        $maxScore += 5;
        if ($summary['google_business_profile']['exists'] ?? false) {
            $score += 5;
        }

        return (int) round(($score / $maxScore) * 100);
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
                'schema_markup' => [
                    'found' => false,
                    'formats' => ['json_ld' => 0, 'microdata' => 0, 'rdfa' => 0],
                    'format_evidence' => ['json_ld' => null, 'microdata' => null, 'rdfa' => null],
                    'json_ld_blocks' => [],
                    'primary' => null,
                    'valid' => true,
                    'errors' => [],
                    'has_local_business' => false,
                ],
                'google_business_profile' => [
                    'exists' => false,
                    'confidence' => 'none',
                    'signals' => [],
                    'name' => null,
                    'address' => null,
                    'phone' => null,
                    'url' => null,
                    'rating' => null,
                    'review_count' => null,
                    'place_id' => null,
                ],
                'score' => 0,
            ],
        ];
    }
}
