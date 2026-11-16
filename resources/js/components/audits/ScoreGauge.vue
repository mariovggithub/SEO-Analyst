<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    score: number;
    size?: number;
}>();

const size = computed(() => props.size ?? 64);
const radius = computed(() => (size.value - 8) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const offset = computed(
    () => circumference.value - (props.score / 100) * circumference.value,
);

const scoreColor = computed(() => {
    if (props.score >= 90) return 'text-green-500';
    if (props.score >= 50) return 'text-yellow-500';
    return 'text-red-500';
});

const strokeColor = computed(() => {
    if (props.score >= 90) return 'stroke-green-500';
    if (props.score >= 50) return 'stroke-yellow-500';
    return 'stroke-red-500';
});

const fontSize = computed(() => {
    if (size.value <= 48) return 'text-xs';
    if (size.value <= 64) return 'text-sm';
    return 'text-lg';
});
</script>

<template>
    <div class="relative inline-flex items-center justify-center">
        <svg
            :width="size"
            :height="size"
            class="-rotate-90 transform"
        >
            <!-- Background circle -->
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                stroke="currentColor"
                stroke-width="4"
                class="text-muted/30"
            />
            <!-- Progress circle -->
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                stroke-width="4"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="offset"
                :class="strokeColor"
                class="transition-all duration-700 ease-out"
            />
        </svg>
        <span
            class="absolute font-bold"
            :class="[scoreColor, fontSize]"
        >
            {{ score }}
        </span>
    </div>
</template>

