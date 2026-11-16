<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Globe, Loader2 } from '@lucide/vue';
import audits from '@/routes/audits';

const form = useForm({
    url: '',
});

function submit() {
    form.post(audits.store.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <form
        class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border sm:flex-row sm:items-start"
        @submit.prevent="submit"
    >
        <div class="flex-1">
            <div class="relative">
                <Globe
                    class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
                />
                <input
                    v-model="form.url"
                    type="url"
                    placeholder="https://example.com"
                    class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring w-full rounded-lg border py-2 pr-4 pl-10 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    :disabled="form.processing"
                />
            </div>
            <p v-if="form.errors.url" class="mt-1 text-sm text-red-500">
                {{ form.errors.url }}
            </p>
        </div>
        <button
            type="submit"
            :disabled="form.processing || !form.url"
            class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center justify-center gap-2 rounded-lg px-6 py-2 text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50"
        >
            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
            {{ form.processing ? 'Starting...' : 'Start Audit' }}
        </button>
    </form>
</template>
