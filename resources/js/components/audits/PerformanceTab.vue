<script setup lang="ts">
import { computed, ref } from 'vue';
import ScoreGauge from './ScoreGauge.vue';
import type { PerformanceSummary } from '@/types/audit';

const { summary } = defineProps<{
    summary: PerformanceSummary;
}>();

const activeStrategy = ref<'mobile' | 'desktop'>('mobile');

function formatMs(value: number | null): string {
    if (value === null || value === undefined) return '—';
    if (value < 1000) return `${Math.round(value)} ms`;
    return `${(value / 1000).toFixed(1)} s`;
}

function formatCls(value: number | null): string {
    if (value === null || value === undefined) return '—';
    return value.toFixed(3);
}

function cwvRating(metric: string, value: number | null): string {
    if (value === null) return 'text-muted-foreground';
    const thresholds: Record<string, [number, number]> = {
        fcp: [1800, 3000],
        lcp: [2500, 4000],
        cls: [0.1, 0.25],
        tbt: [200, 600],
        si: [3400, 5800],
        tti: [3800, 7300],
    };
    const [good, poor] = thresholds[metric] ?? [0, 0];
    if (value <= good) return 'text-green-600 dark:text-green-400';
    if (value <= poor) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
}

const currentData = computed(() => summary[activeStrategy.value]);
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Strategy Toggle -->
        <div class="flex gap-2">
            <button
                v-for="s in (['mobile', 'desktop'] as const)"
                :key="s"
                class="rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                :class="
                    activeStrategy === s
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-muted text-muted-foreground hover:text-foreground'
                "
                @click="activeStrategy = s"
            >
                {{ s === 'mobile' ? '📱 Mobile' : '🖥️ Desktop' }}
            </button>
        </div>

        <!-- Scores Overview -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div
                v-for="(value, key) in (currentData?.scores ?? {})"
                :key="key"
                class="flex flex-col items-center gap-2 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <ScoreGauge :score="value" :size="72" />
                <span class="text-muted-foreground text-sm capitalize">
                    {{ String(key).replace('_', ' ') }}
                </span>
            </div>
        </div>

        <!-- Core Web Vitals -->
        <div>
            <h3 class="mb-3 font-semibold">Core Web Vitals</h3>
            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <div
                    v-for="(label, metric) in {
                        fcp: 'FCP',
                        lcp: 'LCP',
                        cls: 'CLS',
                        tbt: 'TBT',
                        si: 'Speed Index',
                        tti: 'TTI',
                    }"
                    :key="metric"
                    class="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <p class="text-muted-foreground text-xs">{{ label }}</p>
                    <p
                        class="mt-1 text-lg font-semibold"
                        :class="cwvRating(metric, currentData?.core_web_vitals?.[metric as keyof typeof currentData.core_web_vitals] ?? null)"
                    >
                        {{
                            metric === 'cls'
                                ? formatCls(currentData?.core_web_vitals?.[metric as keyof typeof currentData.core_web_vitals] ?? null)
                                : formatMs(currentData?.core_web_vitals?.[metric as keyof typeof currentData.core_web_vitals] ?? null)
                        }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Diagnostics -->
        <div v-if="currentData?.diagnostics && currentData.diagnostics.length > 0">
            <h3 class="mb-3 font-semibold">Opportunities &amp; Diagnostics</h3>
            <div class="space-y-2">
                <div
                    v-for="(diag, i) in currentData.diagnostics"
                    :key="i"
                    class="flex items-start gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <div
                        class="mt-0.5 h-2 w-2 shrink-0 rounded-full"
                        :class="
                            diag.score === null || diag.score === 0
                                ? 'bg-red-500'
                                : diag.score < 0.5
                                  ? 'bg-yellow-500'
                                  : 'bg-green-500'
                        "
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium">{{ diag.title }}</p>
                        <p
                            v-if="diag.displayValue"
                            class="text-muted-foreground mt-0.5 text-xs"
                        >
                            {{ diag.displayValue }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

