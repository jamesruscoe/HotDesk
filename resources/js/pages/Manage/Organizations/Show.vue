<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Location, Organization } from '@/types/hotdesk';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, MapPin, Plus } from 'lucide-vue-next';

const props = defineProps<{
    organization: Organization;
    locations: Location[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: route('manage.organizations.index') },
    { title: props.organization.name, href: route('manage.organizations.show', { organization: props.organization.slug }) },
];
</script>

<template>
    <Head :title="organization.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-7xl space-y-8 px-4 py-10 md:px-8">
            <PageHeader :title="organization.name" eyebrow="Organization" description="Offices and locations people can book desks in.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('manage.organizations.locations.create', { organization: organization.slug })"><Plus /> Add office</Link>
                    </Button>
                </template>
            </PageHeader>

            <EmptyState v-if="locations.length === 0" :icon="MapPin" title="No offices yet" description="Add an office, such as Office A or Manchester, then draw its floors.">
                <Link
                    :href="route('manage.organizations.locations.create', { organization: organization.slug })"
                    class="inline-flex items-center gap-2 rounded-md bg-bp-cyan px-4 py-2 text-sm font-semibold text-bp-ink hover:bg-bp-cyan/90"
                >
                    <Plus class="size-4" /> Add office
                </Link>
            </EmptyState>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="location in locations"
                    :key="location.id"
                    :href="route('manage.organizations.locations.show', [organization.slug, location.id])"
                    class="group flex flex-col rounded-md border border-border bg-card p-5 transition hover:border-bp-cyan"
                >
                    <div class="flex items-start justify-between">
                        <span class="flex size-10 items-center justify-center rounded-md bg-bp-ink text-bp-cyan">
                            <MapPin class="size-[18px]" />
                        </span>
                        <ArrowUpRight class="size-4 text-muted-foreground transition group-hover:text-foreground" />
                    </div>
                    <p class="mt-4 truncate text-lg font-bold">{{ location.name }}</p>
                    <p class="truncate text-sm text-muted-foreground">{{ location.address || 'No address yet' }}</p>

                    <dl class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded border border-border bg-border">
                        <div class="bg-card px-3 py-2">
                            <dt class="bp-label">Floors</dt>
                            <dd class="font-mono text-lg font-semibold tabular-nums">{{ location.floors_count }}</dd>
                        </div>
                        <div class="bg-card px-3 py-2">
                            <dt class="bp-label">Timezone</dt>
                            <dd class="truncate pt-1 font-mono text-xs font-medium">{{ location.timezone }}</dd>
                        </div>
                    </dl>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
