<script setup lang="ts">
import { CheckCircle2, XCircle } from '@lucide/vue';
import ScoreGauge from './ScoreGauge.vue';
import type { OnPageSummary } from '@/types/audit';

const { summary } = defineProps<{
    summary: OnPageSummary;
}>();
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Overall On-Page Score -->
        <div class="flex items-center gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <ScoreGauge :score="summary.score" :size="72" />
            <div>
                <p class="font-semibold">On-Page SEO Score</p>
                <p class="text-muted-foreground text-sm">
                    Based on meta tags, headings, SSL, sitemap, and image optimization.
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

