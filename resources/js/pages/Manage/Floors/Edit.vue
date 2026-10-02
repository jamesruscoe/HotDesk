<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FloorPlanCanvas from '@/components/FloorPlan/FloorPlanCanvas.vue';
import FloorPlanInspector from '@/components/FloorPlan/FloorPlanInspector.vue';
import FloorPlanToolbar from '@/components/FloorPlan/FloorPlanToolbar.vue';
import { useFloorPlanStore } from '@/stores/useFloorPlanStore';
import type { Floor, Location, LocationAddOnOffer, Organization, UserMinimal } from '@/types/hotdesk';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { AlertCircle, ArrowLeft, Check, Loader2, Redo2, Undo2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onBeforeUnmount } from 'vue';

const props = defineProps<{
    organization: Organization;
    location: Location;
    floor: Floor;
    members: UserMinimal[];
    addOnOffers: LocationAddOnOffer[];
}>();

const store = useFloorPlanStore();
store.load(props.floor);
const { isDirty, canUndo, canRedo } = storeToRefs(store);

// The payload is built from the store at submit time via transform().
const form = useForm({});
/** Desk keys in the order they were sent, to map "desks.3.label" errors back to a desk. */
let submittedKeys: string[] = [];

const backUrl = computed(() => route('manage.organizations.locations.show', [props.organization.slug, props.location.id]));

const deskErrorPattern = /^desks\.(\d+)\./;

const serverProblems = computed(() => {
    const result: Record<string, string[]> = {};

    for (const [field, message] of Object.entries(form.errors) as [string, string | undefined][]) {
        const match = deskErrorPattern.exec(field);
        const key = match ? submittedKeys[Number(match[1])] : undefined;

        if (key && message) {
            result[key] = [...(result[key] ?? []), message];
        }
    }

    return result;
});

const generalErrors = computed(() =>
    (Object.entries(form.errors) as [string, string | undefined][])
        .filter(([field, message]) => !deskErrorPattern.test(field) && message)
        .map(([, message]) => message as string),
);

const problems = computed(() => {
    const merged: Record<string, string[]> = { ...serverProblems.value };

    for (const [key, messages] of Object.entries(store.issues)) {
        merged[key] = [...(merged[key] ?? []), ...messages];
    }

    return merged;
});

const errorKeys = computed(() => new Set(Object.keys(problems.value)));
const problemCount = computed(() => Object.keys(problems.value).length + generalErrors.value.length);

const status = computed(() => {
    if (form.processing) {
        return { label: 'Saving…', tone: 'text-[#E9F1FB]/60' };
    }

    if (problemCount.value > 0) {
        return { label: `${problemCount.value} ${problemCount.value === 1 ? 'problem' : 'problems'} to fix`, tone: 'text-red-300' };
    }

    return isDirty.value ? { label: 'Unsaved changes', tone: 'text-bp-amber' } : { label: 'Saved', tone: 'text-[#E9F1FB]/60' };
});

function save(): void {
    const firstIssue = Object.keys(store.issues)[0];

    // Same checks as the server, so obvious mistakes never leave the browser.
    if (firstIssue !== undefined) {
        store.selectedKey = firstIssue;
        store.tool = 'select';
        return;
    }

    submittedKeys = store.desks.map((desk) => desk.key);

    form.transform(() => store.toPayload()).put(
        route('manage.organizations.floors.update', [props.organization.slug, props.location.id, props.floor.id]),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                store.load(page.props.floor as unknown as Floor);
            },
            onError: () => {
                const firstKey = Object.keys(serverProblems.value)[0];

                if (firstKey !== undefined) {
                    store.selectedKey = firstKey;
                }
            },
        },
    );
}

useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
        event.preventDefault();
        if (isDirty.value && !form.processing) {
            save();
        }
    }
});

useEventListener(window, 'beforeunload', (event: BeforeUnloadEvent) => {
    if (isDirty.value) {
        event.preventDefault();
    }
});

const removeNavigationGuard = router.on('before', (event) => {
    if (event.detail.visit.method === 'get' && isDirty.value && !window.confirm('You have unsaved changes to this floor plan. Leave without saving?')) {
        event.preventDefault();
    }
});

onBeforeUnmount(removeNavigationGuard);


const barButton =
    'flex size-8 items-center justify-center rounded text-[#E9F1FB]/70 transition hover:bg-white/10 hover:text-white disabled:pointer-events-none disabled:opacity-30';
</script>

<template>
    <Head :title="`${floor.name} · ${location.name}`" />

    <div class="flex h-svh w-full flex-col overflow-hidden bg-bp-navy font-sans">
        <!-- Top bar -->
        <header class="flex h-14 shrink-0 items-center gap-3 border-b border-white/10 bg-bp-ink px-3 text-[#E9F1FB]">
            <Link :href="backUrl" :class="barButton" title="Back to office"><ArrowLeft class="size-[18px]" /></Link>
            <AppLogoIcon class="size-6 shrink-0" />
            <nav class="flex min-w-0 items-center gap-2 text-sm" aria-label="Breadcrumb">
                <span class="hidden truncate text-[#E9F1FB]/60 md:inline">{{ organization.name }}</span>
                <span class="hidden text-[#E9F1FB]/30 md:inline">/</span>
                <Link :href="backUrl" class="truncate text-[#E9F1FB]/60 hover:text-white">{{ location.name }}</Link>
                <span class="text-[#E9F1FB]/30">/</span>
                <span class="truncate font-semibold text-white">{{ floor.name }}</span>
            </nav>

            <div class="ml-auto flex items-center gap-1">
                <span class="mr-2 hidden items-center gap-1.5 font-mono text-xs sm:flex" :class="status.tone">
                    <Loader2 v-if="form.processing" class="size-3.5 animate-spin" />
                    <AlertCircle v-else-if="problemCount > 0" class="size-3.5" />
                    <Check v-else-if="!isDirty" class="size-3.5" />
                    <span v-else class="size-1.5 rounded-full bg-bp-amber" />
                    {{ status.label }}
                </span>
                <span class="mr-2 hidden font-mono text-xs text-bp-cyan lg:inline">{{ store.width }} × {{ store.height }}</span>
                <button type="button" :class="barButton" title="Undo (Ctrl+Z)" :disabled="!canUndo" @click="store.undo()"><Undo2 class="size-[18px]" /></button>
                <button type="button" :class="barButton" title="Redo (Ctrl+Shift+Z)" :disabled="!canRedo" @click="store.redo()"><Redo2 class="size-[18px]" /></button>
                <button
                    type="button"
                    class="ml-2 inline-flex h-8 items-center gap-2 rounded bg-bp-cyan px-3.5 text-sm font-semibold text-bp-ink transition hover:bg-bp-cyan/90 disabled:bg-white/10 disabled:text-[#E9F1FB]/40"
                    :disabled="form.processing || !isDirty"
                    @click="save"
                >
                    Save
                    <kbd class="hidden rounded bg-bp-ink/15 px-1 font-mono text-[10px] font-medium lg:inline">Ctrl S</kbd>
                </button>
            </div>
        </header>

        <div class="flex min-h-0 flex-1">
            <div class="relative min-w-0 flex-1">
                <FloorPlanCanvas :error-keys="errorKeys" :members="members" />

                <!-- Tool dock -->
                <div class="absolute left-4 top-1/2 -translate-y-1/2">
                    <FloorPlanToolbar />
                </div>

                <!-- Messages -->
                <div class="pointer-events-none absolute inset-x-0 top-4 flex justify-center px-4">
                    <Transition
                        enter-active-class="transition duration-200"
                        enter-from-class="-translate-y-2 opacity-0"
                        leave-active-class="transition duration-150"
                        leave-to-class="opacity-0"
                    >
                        <div
                            v-if="generalErrors.length"
                            class="pointer-events-auto flex max-w-xl gap-2 rounded-md border border-red-400/40 bg-bp-ink/95 px-4 py-3 text-sm text-red-300 shadow-lg"
                        >
                            <AlertCircle class="mt-0.5 size-4 shrink-0" />
                            <ul class="space-y-0.5">
                                <li v-for="message in generalErrors" :key="message">{{ message }}</li>
                            </ul>
                        </div>
                        <div
                            v-else-if="form.recentlySuccessful"
                            class="flex items-center gap-2 rounded-md border border-bp-cyan/40 bg-bp-ink/95 px-3.5 py-2 text-sm font-medium text-white shadow-lg"
                        >
                            <Check class="size-4 text-bp-cyan" /> Floor plan saved
                        </div>
                    </Transition>
                </div>
            </div>

            <FloorPlanInspector :members="members" :add-on-offers="addOnOffers" :problems="problems" />
        </div>
    </div>
</template>
