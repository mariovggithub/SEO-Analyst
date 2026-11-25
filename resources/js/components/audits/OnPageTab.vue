<script setup lang="ts">
import { CheckCircle2, XCircle } from '@lucide/vue';
import { computed } from 'vue';
import ScoreGauge from './ScoreGauge.vue';
import type { OnPageSummary } from '@/types/audit';

const { summary } = defineProps<{
    summary: OnPageSummary;
}>();

// Audits stored before `primary` existed fall back to the first item.
const primarySchema = computed(
    () => summary.schema_markup.primary ?? summary.schema_markup.items?.[0] ?? null,
);

const schemaFormats = computed(() =>
    (
        [
            ['json_ld', 'JSON-LD'],
            ['microdata', 'Microdata'],
            ['rdfa', 'RDFa'],
        ] as const
    ).map(([key, label]) => ({
        key,
        label,
        count: summary.schema_markup.formats[key],
        evidence: summary.schema_markup.format_evidence?.[key] ?? null,
    })),
);

const jsonLdBlocks = computed(() =>
    (summary.schema_markup.json_ld_blocks ?? []).map((block) => {
        const parentTypeMarkers = block.types.map((type) => `"${type}"`);

        return {
            types: block.types,
            lines: block.code.split('\n').map((text) => ({
                text,
                highlight:
                    /"@type"\s*:/.test(text) &&
                    parentTypeMarkers.some((marker) => text.includes(marker)),
            })),
        };
    }),
);

const jsonLdTypes = computed(() => [
    ...new Set((summary.schema_markup.json_ld_blocks ?? []).flatMap((block) => block.types)),
]);
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Overall On-Page Score -->
        <div class="flex items-center gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <ScoreGauge :score="summary.score" :size="72" />
            <div>
                <p class="font-semibold">On-Page SEO Score</p>
                <p class="text-muted-foreground text-sm">
                    Based on meta tags, headings, SSL, sitemap, image optimization, schema markup, and local presence.
                </p>
            </div>
        </div>

        <!-- Meta Tags -->
        <div>
            <h3 class="mb-3 font-semibold">Meta Tags</h3>
            <div class="space-y-3">
                <!-- Title -->
                <div class="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">Title Tag</span>
                        <span class="text-muted-foreground text-xs">
                            {{ summary.meta.title_length }} characters
                        </span>
                    </div>
                    <p v-if="summary.meta.title" class="mt-1 text-sm">
                        {{ summary.meta.title }}
                    </p>
                    <p v-else class="mt-1 text-sm text-red-500">Missing</p>
                    <p
                        v-if="summary.meta.title_length > 0"
                        class="mt-1 text-xs"
                        :class="
                            summary.meta.title_length >= 30 && summary.meta.title_length <= 60
                                ? 'text-green-600 dark:text-green-400'
                                : 'text-yellow-600 dark:text-yellow-400'
                        "
                    >
                        {{
                            summary.meta.title_length >= 30 && summary.meta.title_length <= 60
                                ? '✓ Good length (30-60 chars)'
                                : `⚠ Recommended: 30-60 characters`
                        }}
                    </p>
                </div>

                <!-- Description -->
                <div class="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">Meta Description</span>
                        <span class="text-muted-foreground text-xs">
                            {{ summary.meta.description_length }} characters
                        </span>
                    </div>
                    <p v-if="summary.meta.description" class="mt-1 text-sm">
                        {{ summary.meta.description }}
                    </p>
                    <p v-else class="mt-1 text-sm text-red-500">Missing</p>
                    <p
                        v-if="summary.meta.description_length > 0"
                        class="mt-1 text-xs"
                        :class="
                            summary.meta.description_length >= 120 && summary.meta.description_length <= 160
                                ? 'text-green-600 dark:text-green-400'
                                : 'text-yellow-600 dark:text-yellow-400'
                        "
                    >
                        {{
                            summary.meta.description_length >= 120 && summary.meta.description_length <= 160
                                ? '✓ Good length (120-160 chars)'
                                : `⚠ Recommended: 120-160 characters`
                        }}
                    </p>
                </div>

                <!-- Canonical -->
                <div class="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                    <div class="flex items-center gap-2">
                        <component
                            :is="summary.meta.canonical ? CheckCircle2 : XCircle"
                            class="h-4 w-4"
                            :class="summary.meta.canonical ? 'text-green-500' : 'text-red-500'"
                        />
                        <span class="text-sm font-medium">Canonical Tag</span>
                    </div>
                    <p v-if="summary.meta.canonical" class="text-muted-foreground mt-1 text-xs">
                        {{ summary.meta.canonical }}
                    </p>
                </div>

                <!-- OG Tags -->
                <div class="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                    <div class="flex items-center gap-2">
                        <component
                            :is="summary.meta.has_og_tags ? CheckCircle2 : XCircle"
                            class="h-4 w-4"
                            :class="summary.meta.has_og_tags ? 'text-green-500' : 'text-red-500'"
                        />
                        <span class="text-sm font-medium">Open Graph Tags</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schema Markup -->
        <div v-if="summary.schema_markup">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h3 class="font-semibold">Schema Markup (Structured Data)</h3>
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="
                        summary.schema_markup.found
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                    "
                >
                    {{ summary.schema_markup.found ? 'Detected' : 'Not detected' }}
                </span>
            </div>

            <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <div
                        v-for="format in schemaFormats"
                        :key="format.key"
                        class="p-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium">{{ format.label }}</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    format.count
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{ format.count ? `${format.count} detected` : 'Not detected' }}
                            </span>
                        </div>
                        <div
                            v-if="format.key === 'json_ld' && jsonLdTypes.length > 0"
                            class="mt-2 flex flex-wrap gap-1"
                        >
                            <span
                                v-for="type in jsonLdTypes"
                                :key="type"
                                class="bg-muted rounded-full px-2 py-0.5 text-xs font-medium"
                            >
                                {{ type }}
                            </span>
                        </div>
                        <pre
                            v-if="format.key !== 'json_ld' && format.evidence"
                            class="mt-2 overflow-x-auto rounded border-l-4 border-yellow-400 bg-yellow-100 p-2 text-[11px] leading-relaxed whitespace-pre-wrap text-yellow-900 [overflow-wrap:anywhere] dark:bg-yellow-900/20 dark:text-yellow-200"
                        ><code>{{ format.evidence }}</code></pre>
                    </div>
                </div>

                <!-- JSON-LD code, one block per unique script -->
                <div
                    v-if="jsonLdBlocks.length > 0"
                    class="flex flex-col gap-3 border-t border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <div
                        v-for="(block, bi) in jsonLdBlocks"
                        :key="bi"
                        class="overflow-hidden rounded-md border border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <div class="flex flex-wrap gap-1 border-b border-sidebar-border/70 p-2 dark:border-sidebar-border">
                            <span
                                v-for="type in block.types"
                                :key="type"
                                class="bg-muted rounded-full px-2 py-0.5 text-xs font-medium"
                            >
                                {{ type }}
                            </span>
                        </div>
                        <pre
                            class="max-h-80 overflow-auto bg-slate-950 p-3 text-[11px] leading-relaxed whitespace-pre-wrap text-slate-100 [overflow-wrap:anywhere]"
                        ><code><span
                            v-for="(line, li) in block.lines"
                            :key="li"
                            class="block"
                            :class="line.highlight ? 'bg-yellow-300/90 text-slate-900' : ''"
                        >{{ line.text || ' ' }}</span></code></pre>
                    </div>
                </div>

                <div
                    v-if="primarySchema"
                    class="border-t border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <div class="bg-muted/40 rounded-md p-2">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-medium">{{ primarySchema.type }}</p>
                                <p v-if="primarySchema.name" class="text-muted-foreground truncate text-xs">
                                    {{ primarySchema.name }}
                                </p>
                            </div>
                            <span class="text-muted-foreground shrink-0 text-xs uppercase">
                                {{ primarySchema.format.replace('_', '-') }}
                            </span>
                        </div>

                        <details
                            v-if="
                                primarySchema.code &&
                                (primarySchema.format !== 'json_ld' || jsonLdBlocks.length === 0)
                            "
                            class="mt-2"
                        >
                            <summary class="text-primary cursor-pointer text-xs font-medium">
                                View schema code
                            </summary>
                            <pre
                                class="mt-2 rounded bg-slate-950 p-3 text-[11px] leading-relaxed whitespace-pre-wrap text-slate-100 [overflow-wrap:anywhere]"
                            ><code>{{ primarySchema.code }}</code></pre>
                        </details>
                    </div>
                </div>

                <p
                    v-if="!primarySchema"
                    class="text-muted-foreground border-t border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border"
                >
                    No structured data found. Add JSON-LD (e.g. Organization, WebSite, Article, LocalBusiness) to help search engines understand the page.
                </p>

                <div
                    v-if="!summary.schema_markup.valid"
                    class="border-t border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950/20"
                >
                    <p
                        v-for="(error, i) in summary.schema_markup.errors"
                        :key="i"
                        class="text-xs text-red-700 dark:text-red-400"
                    >
                        {{ error }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Google Business Profile -->
        <div v-if="summary.google_business_profile">
            <h3 class="mb-3 font-semibold">Google Business Profile</h3>
            <div
                class="rounded-lg border p-4"
                :class="
                    summary.google_business_profile.exists
                        ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/20'
                        : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/20'
                "
            >
                <div class="flex items-center gap-2">
                    <component
                        :is="summary.google_business_profile.exists ? CheckCircle2 : XCircle"
                        class="h-5 w-5 shrink-0"
                        :class="
                            summary.google_business_profile.exists
                                ? 'text-green-500'
                                : 'text-red-500'
                        "
                    />
                    <p class="font-semibold">
                        {{
                            summary.google_business_profile.exists
                                ? 'Google Business Profile detected'
                                : 'Google Business Profile not detected'
                        }}
                    </p>
                    <span
                        v-if="summary.google_business_profile.exists"
                        class="text-muted-foreground ml-auto text-xs capitalize"
                    >
                        {{ summary.google_business_profile.confidence }} confidence
                    </span>
                </div>

                <div
                    v-if="
                        summary.google_business_profile.name ||
                        summary.google_business_profile.address ||
                        summary.google_business_profile.phone ||
                        summary.google_business_profile.rating !== null
                    "
                    class="mt-3 space-y-1 text-sm"
                >
                    <p v-if="summary.google_business_profile.name">
                        <span class="font-medium">Name:</span>
                        {{ summary.google_business_profile.name }}
                    </p>
                    <p v-if="summary.google_business_profile.address">
                        <span class="font-medium">Address:</span>
                        {{ summary.google_business_profile.address }}
                    </p>
                    <p v-if="summary.google_business_profile.phone">
                        <span class="font-medium">Phone:</span>
                        {{ summary.google_business_profile.phone }}
                    </p>
                    <p v-if="summary.google_business_profile.rating !== null">
                        <span class="font-medium">Rating:</span>
                        {{ summary.google_business_profile.rating }}
                        <span v-if="summary.google_business_profile.review_count">
                            ({{ summary.google_business_profile.review_count }} reviews)
                        </span>
                    </p>
                </div>

                <div
                    v-if="summary.google_business_profile.signals.length > 0"
                    class="mt-3 flex flex-wrap gap-2"
                >
                    <span
                        v-for="signal in summary.google_business_profile.signals"
                        :key="signal"
                        class="rounded-full bg-white/70 px-2 py-0.5 text-xs font-medium text-foreground dark:bg-black/30"
                    >
                        {{ signal }}
                    </span>
                </div>

                <p class="text-muted-foreground mt-3 text-xs">
                    {{
                        summary.google_business_profile.exists
                            ? 'Detected from public page signals such as LocalBusiness structured data, Google Maps embeds, or profile links.'
                            : 'No public Google Business Profile signals (LocalBusiness schema, Google Maps embed, or profile link) were found on this page.'
                    }}
                </p>
            </div>
        </div>

        <!-- Headings -->
        <div>
            <h3 class="mb-3 font-semibold">Heading Structure</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                <div
                    v-for="tag in (['h1', 'h2', 'h3'] as const)"
                    :key="tag"
                    class="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium uppercase">{{ tag }}</span>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="
                                summary.headings[tag].count > 0
                                    ? tag === 'h1' && summary.headings[tag].count === 1
                                      ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                      : tag === 'h1' && summary.headings[tag].count > 1
                                        ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                        : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                            "
                        >
                            {{ summary.headings[tag].count }}
                        </span>
                    </div>
                    <ul v-if="summary.headings[tag].texts.length > 0" class="mt-2 space-y-1">
                        <li
                            v-for="(text, i) in summary.headings[tag].texts"
                            :key="i"
                            class="text-muted-foreground truncate text-xs"
                        >
                            {{ text }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Checklist -->
        <div>
            <h3 class="mb-3 font-semibold">SEO Checklist</h3>
            <div class="grid gap-2 sm:grid-cols-2">
                <div
                    v-for="item in [
                        { label: 'SSL / HTTPS', ok: summary.ssl },
                        { label: 'Sitemap.xml', ok: summary.sitemap.exists },
                        { label: 'Robots.txt', ok: summary.robots_txt.exists },
                        {
                            label: `Image Alt Tags (${summary.images.alt_coverage}% coverage)`,
                            ok: summary.images.alt_coverage >= 80,
                        },
                        {
                            label: 'Schema Markup',
                            ok: (summary.schema_markup?.found ?? false) && (summary.schema_markup?.valid ?? true),
                        },
                        {
                            label: 'Google Business Profile',
                            ok: summary.google_business_profile?.exists ?? false,
                        },
                    ]"
                    :key="item.label"
                    class="flex items-center gap-2 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <component
                        :is="item.ok ? CheckCircle2 : XCircle"
                        class="h-4 w-4 shrink-0"
                        :class="item.ok ? 'text-green-500' : 'text-red-500'"
                    />
                    <span class="text-sm">{{ item.label }}</span>
                </div>
            </div>
        </div>

        <!-- Links -->
        <div>
            <h3 class="mb-3 font-semibold">Link Analysis</h3>
            <div class="grid gap-3 sm:grid-cols-4">
                <div
                    v-for="(value, label) in {
                        'Total Links': summary.links.total,
                        'Internal': summary.links.internal,
                        'External': summary.links.external,
                        'Nofollow': summary.links.nofollow,
                    }"
                    :key="label"
                    class="rounded-lg border border-sidebar-border/70 p-3 text-center dark:border-sidebar-border"
                >
                    <p class="text-2xl font-bold">{{ value }}</p>
                    <p class="text-muted-foreground text-xs">{{ label }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

