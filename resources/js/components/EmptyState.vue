<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    icon: Component;
    title: string;
    description: string;
}>();
</script>

<template>
    <div class="bp-board relative overflow-hidden rounded-md px-6 py-14 text-center text-[#E9F1FB]">
        <div class="mx-auto flex size-12 items-center justify-center rounded-md border-2 border-dashed border-bp-cyan/70">
            <component :is="icon" class="size-5 text-bp-cyan" />
        </div>
        <h2 class="mt-5 text-lg font-bold">{{ title }}</h2>
        <p class="mx-auto mt-1 max-w-sm text-sm text-[#E9F1FB]/70">{{ description }}</p>
        <div v-if="$slots.default" class="mt-6 flex justify-center">
            <slot />
        </div>
    </div>
</template>
