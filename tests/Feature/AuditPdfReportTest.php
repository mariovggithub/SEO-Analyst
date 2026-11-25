<?php

use App\Enums\AuditCategory;
use App\Enums\AuditStatus;
use App\Models\Audit;
use App\Models\AuditResult;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders a structured executive report for a completed audit', function () {
    $user = User::factory()->create();

    $audit = Audit::create([
        'url' => 'https://example.com',
        'domain' => 'example.com',
        'status' => AuditStatus::Completed,
        'overall_score' => 84,
        'created_by' => $user->id,
    ]);

    AuditResult::create([
        'audit_id' => $audit->id,
        'category' => AuditCategory::Performance,
        'summary' => [
            'score' => 78,
            'mobile' => [
                'source' => 'google_pagespeed_api',
                'scores' => ['performance' => 72, 'seo' => 92, 'best_practices' => 88],
                'core_web_vitals' => ['fcp' => 1600, 'lcp' => 2900, 'cls' => 0.045, 'tbt' => 180, 'si' => 2700, 'tti' => 3500],
                'diagnostics' => [
                    ['title' => 'Eliminate render-blocking resources', 'score' => 0.35, 'displayValue' => 'Potential savings of 420 ms'],
                ],
            ],
            'desktop' => [
                'source' => 'google_pagespeed_api',
                'scores' => ['performance' => 94, 'seo' => 95, 'best_practices' => 92],
                'core_web_vitals' => ['fcp' => 800, 'lcp' => 1400, 'cls' => 0.012, 'tbt' => 40, 'si' => 1200, 'tti' => 1600],
                'diagnostics' => [],
            ],
        ],
    ]);

    AuditResult::create([
        'audit_id' => $audit->id,
        'category' => AuditCategory::OnPage,
        'summary' => [
            'score' => 88,
            'meta' => [
                'title' => 'Acme Dental Studio — Family Dental Care',
                'title_length' => 39,
                'description' => 'Comprehensive family and cosmetic dentistry services in Jakarta with modern digital diagnostics.',
                'description_length' => 96,
                'canonical' => 'https://example.com/',
                'has_og_tags' => true,
                'og_tags' => ['og:title' => 'Acme Dental Studio'],
            ],
            'headings' => [
                'h1' => ['count' => 1, 'texts' => ['Acme Dental Studio']],
                'h2' => ['count' => 2, 'texts' => ['Our Services', 'Book an Appointment']],
                'h3' => ['count' => 0, 'texts' => []],
            ],
            'ssl' => true,
            'sitemap' => ['exists' => true, 'url' => 'https://example.com/sitemap.xml', 'status_code' => 200],
            'robots_txt' => ['exists' => true, 'url' => 'https://example.com/robots.txt'],
            'images' => ['total' => 10, 'without_alt' => 1, 'alt_coverage' => 90],
            'links' => ['total' => 24, 'internal' => 18, 'external' => 6, 'nofollow' => 2],
            'schema_markup' => [
                'found' => true,
                'formats' => ['json_ld' => 1, 'microdata' => 0, 'rdfa' => 0],
                'format_evidence' => ['json_ld' => '<script type="application/ld+json">', 'microdata' => null, 'rdfa' => null],
                'json_ld_blocks' => [['types' => ['LocalBusiness'], 'code' => "{\n  \"@type\": \"LocalBusiness\"\n}"]],
                'primary' => ['format' => 'json_ld', 'type' => 'LocalBusiness', 'name' => 'Acme Dental Studio', 'code' => null],
                'valid' => true,
                'errors' => [],
            ],
            'google_business_profile' => [
                'exists' => true,
                'confidence' => 'high',
                'name' => 'Acme Dental Studio',
                'address' => 'Jl. Sudirman 1, Jakarta',
                'phone' => '+62 21 555 0100',
                'rating' => 4.8,
                'review_count' => 127,
                'signals' => ['LocalBusiness schema', 'Google Maps embed'],
            ],
        ],
    ]);

    AuditResult::create([
        'audit_id' => $audit->id,
        'category' => AuditCategory::TechStack,
        'summary' => [
            'count' => 2,
            'score' => 76,
            'grouped' => [
                'JavaScript Frameworks' => [
                    ['name' => 'Vue.js', 'version' => '3.5', 'share' => 100],
                ],
                'CSS Frameworks' => [
                    ['name' => 'Tailwind CSS', 'version' => null, 'share' => 100],
                ],
            ],
            'technologies' => [],
        ],
    ]);

    Auth::login($user);

    /** @var \Illuminate\Foundation\Testing\TestCase $testCase */
    $testCase = $this;
    $response = $testCase->get(route('audits.pdf', ['audit' => $audit, 'format' => 'html']));

    $response->assertOk()
        ->assertSee('Executive SEO Audit Report')
        ->assertSee('example.com')
        ->assertSee('Key Findings')
        ->assertSee('Core Web Vitals Benchmark')
        ->assertSee('Eliminate render-blocking resources')
        ->assertSee('Acme Dental Studio')
        ->assertSee('Tailwind CSS');
});

it('forbids viewing a report owned by another user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $audit = Audit::create([
        'url' => 'https://example.com',
        'domain' => 'example.com',
        'status' => AuditStatus::Completed,
        'overall_score' => 80,
        'created_by' => $owner->id,
    ]);

    Auth::login($otherUser);

    /** @var \Illuminate\Foundation\Testing\TestCase $testCase */
    $testCase = $this;
    $testCase->get(route('audits.pdf', $audit))
        ->assertForbidden();
});
