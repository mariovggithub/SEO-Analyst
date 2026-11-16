<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePoll } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import AuditCard from '@/components/audits/AuditCard.vue';
import StartAuditForm from '@/components/audits/StartAuditForm.vue';
import audits from '@/routes/audits';
import type { Audit, AuditStats } from '@/types/audit';

const { audits: auditList = [], stats } = defineProps<{
    audits: Audit[];
    stats?: AuditStats;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Audits',
                href: audits.index(),
            },
        ],
    },
});

const hasPendingOrProcessing = computed(
    () =>
        auditList.some(
            (a) => a.status === 'pending' || a.status === 'processing',
        ),
);

usePoll(3000, { only: ['audits', 'stats'] });
</script>

<template>
    <Head title="SEO Audits" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">SEO Audits</h1>
                <p class="text-muted-foreground text-sm">
                    Audit competitor & prospect websites for SEO, performance, and tech stack.
                </p>
            </div>
        </div>

        <!-- Start Audit Form -->
        <StartAuditForm />

        <!-- Stats Overview -->
        <div v-if="stats && stats.total > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-muted-foreground text-xs uppercase font-medium">Total Audits</p>
                <p class="mt-1 text-2xl font-bold">{{ stats.total }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-muted-foreground text-xs uppercase font-medium">Completed</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.completed }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-muted-foreground text-xs uppercase font-medium">In Progress</p>
                <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.processing }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-muted-foreground text-xs uppercase font-medium">Avg Score</p>
                <p
                    class="mt-1 text-2xl font-bold"
                    :class="stats.avg_score && stats.avg_score >= 80 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'"
                >
                    {{ stats.avg_score !== null ? stats.avg_score : '—' }}
                </p>
            </div>
        </div>

        <!-- Audit List -->
        <div v-if="auditList.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <AuditCard
                v-for="audit in auditList"
                :key="audit.id"
                :audit="audit"
            />
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-sidebar-border/70 p-12 dark:border-sidebar-border"
        >
            <div class="bg-muted flex h-12 w-12 items-center justify-center rounded-full">
                <Search class="text-muted-foreground h-6 w-6" />
            </div>
            <div class="text-center">
                <h3 class="font-medium">No audits yet</h3>
                <p class="text-muted-foreground mt-1 text-sm">
                    Enter a URL above to start your first SEO audit.
                </p>
            </div>
        </div>
    </div>
</template>
