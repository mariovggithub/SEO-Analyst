<script setup lang="ts">
import type { TechStackSummary } from '@/types/audit';

const { summary } = defineProps<{
    summary: TechStackSummary;
}>();
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Overview -->
        <div class="flex items-center gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div class="text-center">
                <p class="text-3xl font-bold">{{ summary.count }}</p>
                <p class="text-muted-foreground text-sm">Technologies Detected</p>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="summary.count === 0"
            class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-sidebar-border/70 p-8 dark:border-sidebar-border"
        >
            <p class="text-muted-foreground text-sm">
                No technologies detected. Wappalyzer CLI may not be installed.
            </p>
        </div>

        <!-- Composition note -->
        <p class="text-muted-foreground text-xs">
            Percentages show each technology's share within its category. Every category totals 100%.
        </p>

        <!-- Grouped Technologies -->
        <template v-if="summary.grouped && Object.keys(summary.grouped).length > 0">
            <div
                v-for="(techs, category) in summary.grouped"
                :key="category"
            >
                <h3 class="mb-3 flex items-center justify-between font-semibold">
                    <span>{{ category }}</span>
                    <span class="text-muted-foreground text-xs font-normal">
                        {{ techs.length }} detected &middot; 100%
                    </span>
                </h3>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="tech in techs"
                        :key="tech.name"
                        class="flex items-center gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                    >
                        <div
                            class="bg-muted flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-sm font-bold"
                        >
                            {{ tech.name.substring(0, 2).toUpperCase() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ tech.name }}
                            </p>
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="tech.version"
                                    class="text-muted-foreground text-xs"
                                >
                                    v{{ tech.version }}
                                </span>
                                <span
                                    v-if="tech.share !== undefined"
                                    class="text-muted-foreground text-xs"
                                >
                                    {{ tech.share }}% of {{ category }}
                                </span>
                            </div>
                        </div>
                        <a
                            v-if="tech.website"
                            :href="tech.website"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-muted-foreground shrink-0 text-xs hover:underline"
                        >
                            ↗
                        </a>
                    </div>
                </div>
            </div>
        </template>

        <!-- Flat list fallback -->
        <template v-else-if="summary.technologies.length > 0">
            <h3 class="font-semibold">All Technologies</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="tech in summary.technologies"
                    :key="tech.name"
                    class="flex items-center gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <div
                        class="bg-muted flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-sm font-bold"
                    >
                        {{ tech.name.substring(0, 2).toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-sm font-medium">{{ tech.name }}</p>
                        <p class="text-muted-foreground text-xs">
                            {{ tech.categories.join(', ') }}
                            <span v-if="tech.share !== undefined">
                                &middot; {{ tech.share }}% of {{ tech.categories[0] ?? 'Other' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

