<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
}>();

const page = usePage();

const isActive = (href: string): boolean => {
    const path = new URL(href, window.location.origin).pathname;

    return page.url === path || page.url.startsWith(`${path}/`);
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Workspace</SidebarGroupLabel>
        <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isActive(item.href)"
                    :tooltip="item.title"
                    class="font-medium text-zinc-600 data-[active=true]:bg-white data-[active=true]:text-zinc-900 data-[active=true]:shadow-sm data-[active=true]:ring-1 data-[active=true]:ring-zinc-200 dark:text-zinc-400 dark:data-[active=true]:bg-zinc-800 dark:data-[active=true]:text-white dark:data-[active=true]:ring-zinc-700"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
