<script setup lang="ts">
import type { AuditStatus } from '@/types/audit';

const { status, label } = defineProps<{
    status: AuditStatus;
    label: string;
}>();

const colorClasses: Record<AuditStatus, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
        :class="colorClasses[status]"
    >
        <span
            v-if="status === 'processing'"
            class="h-1.5 w-1.5 animate-pulse rounded-full bg-current"
        />
        {{ label }}
    </span>
</template>

