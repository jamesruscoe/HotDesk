<script setup lang="ts">
import FloorThumbnail from '@/components/FloorPlan/FloorThumbnail.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Floor, Location, Organization } from '@/types/hotdesk';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Clock, MapPin, PencilRuler, Plus, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    organization: Organization;
    location: Location;
    floors: Floor[];
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: route('manage.organizations.index') },
    { title: props.organization.name, href: route('manage.organizations.show', { organization: props.organization.slug }) },
    { title: props.location.name, href: route('manage.organizations.locations.show', [props.organization.slug, props.location.id]) },
];

const nextLevel = computed(() => (props.floors.length === 0 ? 0 : Math.max(...props.floors.map((floor) => floor.level)) + 1));
const isAdding = ref(props.floors.length === 0);

const form = useForm({
    name: props.floors.length === 0 ? 'Ground floor' : '',
    level: nextLevel.value,
});

const floorError = computed(() => (page.props.errors as Record<string, string | undefined>).floor);

const deskCount = (floor: Floor): number => (floor.desks ?? []).filter((desk) => desk.type === 'desk').length;
const roomCount = (floor: Floor): number => (floor.desks ?? []).filter((desk) => desk.type === 'meeting_room').length;

const submit = (): void => {
    form.post(route('manage.organizations.floors.store', [props.organization.slug, props.location.id]));
};

const destroy = (floor: Floor): void => {
    if (!window.confirm(`Delete "${floor.name}" and all of its desks?`)) {
        return;
    }

    router.delete(route('manage.organizations.floors.destroy', [props.organization.slug, props.location.id, floor.id]), { preserveScroll: true });
};
</script>

<template>
    <Head :title="location.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-7xl space-y-8 px-4 py-10 md:px-8">
            <PageHeader :title="location.name" :eyebrow="organization.name">
                <template #actions>
                    <Button v-if="!isAdding" @click="isAdding = true"><Plus /> Add floor</Button>
                </template>
            </PageHeader>

            <div class="-mt-4 flex flex-wrap gap-x-6 gap-y-1 font-mono text-xs text-muted-foreground">
                <span v-if="location.address" class="inline-flex items-center gap-1.5"><MapPin class="size-3.5" /> {{ location.address }}</span>
                <span class="inline-flex items-center gap-1.5"><Clock class="size-3.5" /> {{ location.timezone }}</span>
            </div>

            <p v-if="floorError" class="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">{{ floorError }}</p>

            <form v-if="isAdding" class="rounded-md border border-border bg-card" @submit.prevent="submit">
                <div class="flex items-center justify-between border-b border-border px-5 py-3">
                    <h2 class="bp-label">New floor</h2>
                    <button v-if="floors.length" type="button" class="text-muted-foreground hover:text-foreground" title="Cancel" @click="isAdding = false">
                        <X class="size-4" />
                    </button>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-[1fr_8rem_auto] sm:items-end">
                    <div class="grid gap-2">
                        <Label for="floor-name">Name</Label>
                        <Input id="floor-name" v-model="form.name" required placeholder="First floor" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="floor-level">Level</Label>
                        <Input id="floor-level" v-model.number="form.level" type="number" required class="font-mono" />
                        <InputError :message="form.errors.level" />
                    </div>
                    <Button :disabled="form.processing"><PencilRuler /> Create and draw</Button>
                </div>
            </form>

            <div v-if="floors.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="floor in floors" :key="floor.id" class="group overflow-hidden rounded-md border border-border bg-card transition hover:border-bp-cyan">
                    <Link :href="route('manage.organizations.floors.edit', [organization.slug, location.id, floor.id])" class="bp-board relative block aspect-[16/10] p-4">
                        <FloorThumbnail :floor="floor" />
                        <span class="absolute left-3 top-3 rounded bg-bp-ink/80 px-1.5 py-0.5 font-mono text-[10px] font-medium text-bp-cyan">L{{ floor.level }}</span>
                    </Link>

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-bold">{{ floor.name }}</p>
                                <p class="font-mono text-xs text-muted-foreground">
                                    {{ deskCount(floor) }} {{ deskCount(floor) === 1 ? 'desk' : 'desks' }} · {{ roomCount(floor) }} {{ roomCount(floor) === 1 ? 'room' : 'rooms' }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="rounded p-1.5 text-muted-foreground opacity-0 transition hover:bg-destructive/10 hover:text-destructive focus:opacity-100 group-hover:opacity-100"
                                title="Delete floor"
                                @click="destroy(floor)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>

                        <Button variant="outline" size="sm" class="mt-4 w-full" as-child>
                            <Link :href="route('manage.organizations.floors.edit', [organization.slug, location.id, floor.id])"><PencilRuler /> Edit floor plan</Link>
                        </Button>
                    </div>
                </article>
            </div>
        </div>
    </AppLayout>
</template>
