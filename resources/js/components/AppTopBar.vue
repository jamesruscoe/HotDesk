<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useInitials } from '@/composables/useInitials';
import type { SharedData, User } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user as User);
const { getInitials } = useInitials();

const links = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Organizations', href: route('manage.organizations.index') },
];

const isActive = (href: string): boolean => {
    const path = new URL(href, window.location.origin).pathname;

    return page.url === path || page.url.startsWith(`${path}/`);
};
</script>

<template>
    <header class="sticky top-0 z-30 bg-bp-ink text-[#E9F1FB]">
        <div class="mx-auto flex h-14 max-w-7xl items-center gap-8 px-4 md:px-8">
            <Link :href="route('dashboard')" class="shrink-0 text-white">
                <AppLogo />
            </Link>

            <nav class="flex h-full items-stretch gap-1 text-sm">
                <Link
                    v-for="link in links"
                    :key="link.title"
                    :href="link.href"
                    class="relative flex items-center px-3 font-medium transition"
                    :class="isActive(link.href) ? 'text-white' : 'text-[#E9F1FB]/60 hover:text-white'"
                >
                    {{ link.title }}
                    <span v-if="isActive(link.href)" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-bp-cyan" />
                </Link>
            </nav>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button type="button" class="ml-auto flex items-center gap-2 rounded-md py-1 pl-1 pr-2 text-sm transition hover:bg-white/10">
                        <span class="flex size-7 items-center justify-center rounded-md bg-bp-navy font-mono text-[11px] font-semibold text-bp-cyan ring-1 ring-white/10">
                            {{ getInitials(user.name) }}
                        </span>
                        <span class="hidden font-medium sm:inline">{{ user.name }}</span>
                        <ChevronDown class="size-4 opacity-60" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-56" align="end" :side-offset="8">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
