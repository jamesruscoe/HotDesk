<script setup lang="ts">
import AppTopBar from '@/components/AppTopBar.vue';
import type { BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/vue3';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <AppTopBar />

        <div v-if="breadcrumbs.length > 1" class="border-b border-border bg-card">
            <nav class="mx-auto flex h-10 max-w-7xl items-center gap-2 overflow-x-auto px-4 font-mono text-xs md:px-8" aria-label="Breadcrumb">
                <template v-for="(item, index) in breadcrumbs" :key="index">
                    <span v-if="index > 0" class="text-muted-foreground/50">/</span>
                    <span v-if="index === breadcrumbs.length - 1" class="whitespace-nowrap font-medium text-foreground">{{ item.title }}</span>
                    <Link v-else :href="item.href" class="whitespace-nowrap text-muted-foreground transition hover:text-foreground">{{ item.title }}</Link>
                </template>
            </nav>
        </div>

        <main class="flex flex-1 flex-col">
            <slot />
        </main>
    </div>
</template>
