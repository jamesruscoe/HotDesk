<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="bp-board flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
        <div class="w-full max-w-sm">
            <Link :href="route('home')" class="mb-8 flex justify-center text-white">
                <AppLogo />
            </Link>

            <div class="rounded-md border border-border bg-card p-7 shadow-2xl shadow-black/30">
                <div class="mb-6 space-y-1.5">
                    <h1 class="text-xl font-bold tracking-tight">{{ title }}</h1>
                    <p class="text-sm text-muted-foreground">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
