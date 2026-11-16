<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Globe } from '@lucide/vue';
import AuditStatusBadge from './AuditStatusBadge.vue';
import ScoreGauge from './ScoreGauge.vue';
import audits from '@/routes/audits';
import type { Audit } from '@/types/audit';

const { audit } = defineProps<{
    audit: Audit;
}>();
</script>

<template>
    <Link
        :href="audits.show(audit)"
        class="group flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 transition-all hover:border-sidebar-border hover:shadow-sm dark:border-sidebar-border dark:hover:border-sidebar-border/90"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <Globe class="text-muted-foreground h-4 w-4 shrink-0" />
                    <span class="truncate font-medium">{{ audit.domain }}</span>
                </div>
                <p class="text-muted-foreground mt-1 truncate text-xs">
                    {{ audit.url }}
                </p>
            </div>
            <AuditStatusBadge :status="audit.status" :label="audit.status_label" />
        </div>

        <div class="flex items-end justify-between">
            <div>
                <p class="text-muted-foreground text-xs">{{ audit.created_at }}</p>
            </div>

            <div class="flex items-center gap-3">
                <ScoreGauge
                    v-if="audit.overall_score !== null"
                    :score="audit.overall_score"
                    :size="48"
                />
                <div
                    v-else-if="audit.status === 'processing' || audit.status === 'pending'"
                    class="flex items-center gap-1"
                >
                    <div class="h-2 w-2 animate-pulse rounded-full bg-blue-500" />
                    <span class="text-muted-foreground text-xs">Analyzing...</span>
                </div>

                <ArrowRight
                    class="text-muted-foreground h-4 w-4 opacity-0 transition-opacity group-hover:opacity-100"
                />
            </div>
        </div>
    </Link>
</template>
