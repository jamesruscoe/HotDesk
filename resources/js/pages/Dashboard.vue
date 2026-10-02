<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import type { Organization } from '@/types/hotdesk';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Building2, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps<{
    organizations: Organization[];
}>();

const page = usePage<SharedData>();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

const greeting = computed(() => {
    const hour = new Date().getHours();

    return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
});

const today = new Intl.DateTimeFormat(undefined, { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: route('dashboard') }];

const canManage = (organization: Organization): boolean => organization.role === 'owner' || organization.role === 'admin';

const steps = [
    { title: 'Add your offices', text: 'Create an organization and the offices people can book in.' },
    { title: 'Draw floor plans', text: 'Lay out walls, rooms, desks and meeting rooms.' },
    { title: 'Let people book', text: 'By the hour, the full working day or several days.' },
    { title: 'Check in with QR', text: 'Optional per office. Frees up desks when people do not show.' },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-7xl space-y-10 px-4 py-10 md:px-8">
            <div>
                <p class="bp-label">{{ today }}</p>
                <h1 class="mt-2 text-[32px] font-bold leading-tight tracking-tight">{{ greeting }}, {{ firstName }}.</h1>
            </div>

            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="bp-label">Your organizations</h2>
                    <Link v-if="organizations.length" :href="route('manage.organizations.create')" class="inline-flex items-center gap-1 text-sm font-medium text-bp-cyan-deep hover:underline dark:text-bp-cyan">
                        <Plus class="size-3.5" /> New organization
                    </Link>
                </div>

                <EmptyState
                    v-if="organizations.length === 0"
                    :icon="Building2"
                    title="Set up your first workspace"
                    description="Create an organization, add an office and draw its floor plan. It takes a few minutes."
                >
                    <Link
                        :href="route('manage.organizations.create')"
                        class="inline-flex items-center gap-2 rounded-md bg-bp-cyan px-4 py-2 text-sm font-semibold text-bp-ink hover:bg-bp-cyan/90"
                    >
                        <Plus class="size-4" /> New organization
                    </Link>
                </EmptyState>

                <div v-else class="divide-y divide-border overflow-hidden rounded-md border border-border bg-card">
                    <component
                        :is="canManage(organization) ? Link : 'div'"
                        v-for="organization in organizations"
                        :key="organization.id"
                        :href="canManage(organization) ? route('manage.organizations.show', { organization: organization.slug }) : undefined"
                        class="group flex items-center gap-4 px-5 py-4 transition"
                        :class="canManage(organization) ? 'hover:bg-muted/60' : ''"
                    >
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-bp-ink font-mono text-sm font-semibold text-bp-cyan">
                            {{ organization.name.slice(0, 2).toUpperCase() }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ organization.name }}</p>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ organization.locations_count }} {{ organization.locations_count === 1 ? 'office' : 'offices' }} · {{ organization.role }}
                            </p>
                        </div>
                        <ArrowUpRight v-if="canManage(organization)" class="size-4 text-muted-foreground transition group-hover:text-foreground" />
                    </component>
                </div>
            </section>

            <section class="space-y-4">
                <h2 class="bp-label">Getting set up</h2>
                <ol class="grid gap-px overflow-hidden rounded-md border border-border bg-border sm:grid-cols-2 lg:grid-cols-4">
                    <li v-for="(step, index) in steps" :key="step.title" class="bg-card p-5">
                        <span class="font-mono text-xs font-medium text-bp-amber-deep dark:text-bp-amber">Step {{ index + 1 }}</span>
                        <p class="mt-2 font-semibold">{{ step.title }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ step.text }}</p>
                    </li>
                </ol>
            </section>
        </div>
    </AppLayout>
</template>
