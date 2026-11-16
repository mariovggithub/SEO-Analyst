<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePoll } from '@inertiajs/vue3';
import { ArrowLeft, Download, Globe } from '@lucide/vue';
import AuditStatusBadge from '@/components/audits/AuditStatusBadge.vue';
import OnPageTab from '@/components/audits/OnPageTab.vue';
import PerformanceTab from '@/components/audits/PerformanceTab.vue';
import ScoreGauge from '@/components/audits/ScoreGauge.vue';
import TechStackTab from '@/components/audits/TechStackTab.vue';
import audits from '@/routes/audits';
import type {
    Audit,
    AuditResults,
    OnPageSummary,
    PerformanceSummary,
    TechStackSummary,
} from '@/types/audit';

const props = defineProps<{
    audit: Audit;
    results: AuditResults;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Audits', href: audits.index() },
            { title: 'Audit Detail', href: '#' },
        ],
    },
});

const activeTab = ref<'performance' | 'tech_stack' | 'on_page'>('performance');

const tabs = [
    { key: 'performance' as const, label: 'Performance' },
    { key: 'tech_stack' as const, label: 'Tech Stack' },
    { key: 'on_page' as const, label: 'On-Page SEO' },
];

const isProcessing = computed(
    () =>
        props.audit.status === 'pending' ||
        props.audit.status === 'processing',
);

usePoll(3000, { only: ['audit', 'results'] });
</script>

<template>
    <Head :title="`Audit: ${audit.domain}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                <Link
                    :href="audits.index()"
                    class="bg-muted mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors hover:bg-sidebar-border"
                >
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1
                            class="text-2xl font-semibold tracking-tight"
                        >
                            {{ audit.domain }}
                        </h1>
                        <AuditStatusBadge :status="audit.status" :label="audit.status_label" />
                    </div>
                    <a
                        :href="audit.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-muted-foreground mt-1 flex items-center gap-1 text-sm hover:underline"
                    >
                        <Globe class="h-3 w-3" />
                        {{ audit.url }}
                    </a>
                    <p class="text-muted-foreground mt-1 text-xs">
                        Started {{ audit.created_at }}
                    </p>
                </div>
            </div>

            <div v-if="audit.status === 'completed'" class="flex items-center gap-3">
                <ScoreGauge :score="audit.overall_score ?? 0" :size="80" />
                <a
                    :href="audits.pdf.url(audit)"
                    target="_blank"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                >
                    <Download class="h-4 w-4" />
                    Export PDF
                </a>
            </div>
        </div>

        <!-- Processing State -->
        <div
            v-if="isProcessing"
            class="flex flex-col items-center justify-center gap-4 rounded-xl border border-sidebar-border/70 p-16 dark:border-sidebar-border"
        >
            <div class="h-10 w-10 animate-spin rounded-full border-4 border-blue-500 border-t-transparent" />
            <div class="text-center">
                <h3 class="font-medium">Analyzing {{ audit.domain }}...</h3>
                <p class="text-muted-foreground mt-1 text-sm">
                    This usually takes 10-60 seconds. The page updates automatically.
                </p>
            </div>
        </div>

        <!-- Results -->
        <template v-if="audit.status === 'completed'">
            <!-- Tab Navigation -->
            <div class="border-b border-sidebar-border/70 dark:border-sidebar-border">
                <nav class="-mb-px flex gap-4">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        class="border-b-2 px-1 pb-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === tab.key
                                ? 'border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground border-transparent'
                        "
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <PerformanceTab
                v-if="activeTab === 'performance' && results.performance"
                :summary="results.performance.summary as PerformanceSummary"
            />
            <TechStackTab
                v-if="activeTab === 'tech_stack' && results.tech_stack"
                :summary="results.tech_stack.summary as TechStackSummary"
            />
            <OnPageTab
                v-if="activeTab === 'on_page' && results.on_page"
                :summary="results.on_page.summary as OnPageSummary"
            />
        </template>

        <!-- Failed State -->
        <div
            v-if="audit.status === 'failed'"
            class="flex flex-col items-center justify-center gap-4 rounded-xl border border-red-200 bg-red-50 p-12 dark:border-red-800 dark:bg-red-950/20"
        >
            <div class="text-center">
                <h3 class="font-medium text-red-700 dark:text-red-400">Audit Failed</h3>
                <p class="text-muted-foreground mt-1 text-sm">
                    Something went wrong while analyzing {{ audit.domain }}. Please try again.
                </p>
            </div>
        </div>
    </div>
</template>
