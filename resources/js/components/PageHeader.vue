<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
    eyebrow?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p v-if="eyebrow" class="bp-label mb-2">{{ eyebrow }}</p>
            <h1 class="truncate text-[28px] font-bold leading-tight tracking-tight text-foreground">{{ title }}</h1>
            <p v-if="description" class="mt-1.5 max-w-2xl text-sm text-muted-foreground">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
