<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Organization } from '@/types/hotdesk';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, Building2, Plus } from 'lucide-vue-next';

defineProps<{
    organizations: Organization[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Organizations', href: route('manage.organizations.index') }];

const canManage = (organization: Organization): boolean => organization.role === 'owner' || organization.role === 'admin';
</script>

<template>
    <Head title="Organizations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-7xl space-y-8 px-4 py-10 md:px-8">
            <PageHeader title="Organizations" eyebrow="Workspace" description="Companies you belong to. Owners and admins set up offices and floor plans.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('manage.organizations.create')"><Plus /> New organization</Link>
                    </Button>
                </template>
            </PageHeader>

            <EmptyState v-if="organizations.length === 0" :icon="Building2" title="No organizations yet" description="Create one to add your offices and draw their floor plans.">
                <Link
                    :href="route('manage.organizations.create')"
                    class="inline-flex items-center gap-2 rounded-md bg-bp-cyan px-4 py-2 text-sm font-semibold text-bp-ink hover:bg-bp-cyan/90"
                >
                    <Plus class="size-4" /> New organization
                </Link>
            </EmptyState>

            <div v-else class="overflow-hidden rounded-md border border-border bg-card">
                <div class="hidden grid-cols-[1fr_8rem_12rem_7rem_2rem] gap-4 border-b border-border bg-muted/50 px-5 py-2.5 sm:grid">
                    <span class="bp-label">Name</span>
                    <span class="bp-label">Offices</span>
                    <span class="bp-label">Timezone</span>
                    <span class="bp-label">Your role</span>
                    <span />
                </div>
                <component
                    :is="canManage(organization) ? Link : 'div'"
                    v-for="organization in organizations"
                    :key="organization.id"
                    :href="canManage(organization) ? route('manage.organizations.show', { organization: organization.slug }) : undefined"
                    class="group grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1 border-b border-border px-5 py-4 last:border-0 sm:grid-cols-[1fr_8rem_12rem_7rem_2rem]"
                    :class="canManage(organization) ? 'hover:bg-muted/50' : ''"
                >
                    <span class="flex min-w-0 items-center gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-md bg-bp-ink font-mono text-xs font-semibold text-bp-cyan">
                            {{ organization.name.slice(0, 2).toUpperCase() }}
                        </span>
                        <span class="truncate font-semibold">{{ organization.name }}</span>
                    </span>
                    <span class="font-mono text-sm tabular-nums text-muted-foreground">{{ organization.locations_count }}</span>
                    <span class="hidden font-mono text-sm text-muted-foreground sm:block">{{ organization.timezone }}</span>
                    <span>
                        <span
                            class="rounded px-2 py-0.5 font-mono text-[11px] font-medium uppercase tracking-wide"
                            :class="canManage(organization) ? 'bg-bp-amber/20 text-bp-amber-deep dark:text-bp-amber' : 'bg-muted text-muted-foreground'"
                        >
                            {{ organization.role }}
                        </span>
                    </span>
                    <ArrowUpRight v-if="canManage(organization)" class="hidden size-4 text-muted-foreground group-hover:text-foreground sm:block" />
                </component>
            </div>
        </div>
    </AppLayout>
</template>
