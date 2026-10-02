<script setup lang="ts">
import ToggleSwitch from '@/components/ToggleSwitch.vue';
import { useFloorPlanStore } from '@/stores/useFloorPlanStore';
import type { AddOnAvailability, DeskAddOnAttachment, DeskType, LocationAddOnOffer, UserMinimal } from '@/types/hotdesk';
import { AlertCircle, Copy, Layers, Monitor, PenLine, Square, Trash2, Type, Users } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, type Component } from 'vue';

const props = defineProps<{
    members: UserMinimal[];
    addOnOffers: LocationAddOnOffer[];
    /** Messages per desk key, from client checks and server validation. */
    problems: Record<string, string[]>;
}>();

const store = useFloorPlanStore();
const { selectedDesk, selectedElement, width, height, desks, elements } = storeToRefs(store);

const field =
    'block w-full rounded border border-input bg-background px-2.5 py-1.5 text-sm text-foreground transition placeholder:text-muted-foreground focus:border-bp-cyan focus:outline-none focus:ring-2 focus:ring-bp-cyan/25';
const labelClass = 'bp-label mb-1 block';
const section = 'space-y-4 border-t border-border px-5 py-5';

const counts = computed(() => ({
    desks: desks.value.filter((desk) => desk.type === 'desk').length,
    meetingRooms: desks.value.filter((desk) => desk.type === 'meeting_room').length,
    walls: elements.value.filter((element) => element.type === 'wall').length,
    rooms: elements.value.filter((element) => element.type === 'room').length,
}));

const header = computed<{ icon: Component; title: string; tint: string }>(() => {
    if (selectedDesk.value) {
        return selectedDesk.value.type === 'desk'
            ? { icon: Monitor, title: 'Desk', tint: 'bg-bp-ink text-bp-cyan' }
            : { icon: Users, title: 'Meeting room', tint: 'bg-bp-ink text-bp-amber' };
    }

    switch (selectedElement.value?.type) {
        case 'wall':
            return { icon: PenLine, title: 'Wall', tint: 'bg-muted text-foreground' };
        case 'room':
            return { icon: Square, title: 'Room', tint: 'bg-muted text-foreground' };
        case 'label':
            return { icon: Type, title: 'Label', tint: 'bg-muted text-foreground' };
        default:
            return { icon: Layers, title: 'Floor', tint: 'bg-bp-ink text-bp-amber' };
    }
});

const deskProblems = computed(() => (selectedDesk.value ? (props.problems[selectedDesk.value.key] ?? []) : []));
const hasSelection = computed(() => selectedDesk.value !== null || selectedElement.value !== null);

function numberFrom(event: Event): number {
    return Number((event.target as HTMLInputElement).value);
}

function textFrom(event: Event): string {
    return (event.target as HTMLInputElement).value;
}

function updateDesk(patch: Parameters<typeof store.updateDesk>[1]): void {
    if (selectedDesk.value) {
        store.updateDesk(selectedDesk.value.key, patch);
    }
}

function changeDesk(patch: Parameters<typeof store.updateDesk>[1]): void {
    store.checkpoint();
    updateDesk(patch);
}

function updateElement(patch: Parameters<typeof store.updateElement>[1]): void {
    if (selectedElement.value) {
        store.updateElement(selectedElement.value.id, patch);
    }
}

function changeElement(patch: Parameters<typeof store.updateElement>[1]): void {
    store.checkpoint();
    updateElement(patch);
}

function setCanvas(dimension: 'width' | 'height', value: number): void {
    if (!Number.isFinite(value) || value < 200) {
        return;
    }

    store.checkpoint();
    store.setCanvasSize(dimension === 'width' ? value : width.value, dimension === 'height' ? value : height.value);
}

// ---- Add-ons -----------------------------------------------------------

function attachment(offerId: number): DeskAddOnAttachment | undefined {
    return selectedDesk.value?.add_ons.find((addOn) => addOn.location_add_on_id === offerId);
}

function toggleAddOn(offer: LocationAddOnOffer, enabled: boolean): void {
    const desk = selectedDesk.value;

    if (!desk) {
        return;
    }

    const others = desk.add_ons.filter((addOn) => addOn.location_add_on_id !== offer.id);
    changeDesk({ add_ons: enabled ? [...others, { location_add_on_id: offer.id, availability: null, price_cents: null }] : others });
}

function patchAttachment(offerId: number, patch: Partial<DeskAddOnAttachment>): void {
    const desk = selectedDesk.value;

    if (!desk) {
        return;
    }

    changeDesk({
        add_ons: desk.add_ons.map((addOn) => (addOn.location_add_on_id === offerId ? { ...addOn, ...patch } : addOn)),
    });
}

function onAvailabilityChange(offerId: number, event: Event): void {
    const value = textFrom(event);
    patchAttachment(offerId, { availability: value === '' ? null : (value as AddOnAvailability) });
}

function onPriceChange(offerId: number, event: Event): void {
    const value = textFrom(event).trim();
    patchAttachment(offerId, { price_cents: value === '' ? null : Math.max(0, Math.round(Number(value) * 100)) });
}

function priceInput(cents: number | null): string {
    return cents === null ? '' : (cents / 100).toFixed(2);
}

const deskTypes: { value: DeskType; label: string }[] = [
    { value: 'desk', label: 'Desk' },
    { value: 'meeting_room', label: 'Meeting room' },
];

const pricingUnits: Record<LocationAddOnOffer['pricing_unit'], string> = {
    per_booking: '/ booking',
    per_day: '/ day',
    per_hour: '/ hour',
};

function offerSummary(offer: LocationAddOnOffer): string {
    if (offer.availability === 'included') {
        return 'Included';
    }

    return offer.price_cents === null || offer.price_cents === 0 ? 'Optional · free' : `Optional · ${(offer.price_cents / 100).toFixed(2)} ${pricingUnits[offer.pricing_unit]}`;
}

const shortcuts: [string, string][] = [
    ['V', 'Select'],
    ['W', 'Wall'],
    ['R', 'Room'],
    ['D', 'Desk'],
    ['M', 'Meeting room'],
    ['Ctrl D', 'Duplicate'],
    ['Ctrl Z', 'Undo'],
    ['Ctrl S', 'Save'],
    ['0', 'Fit to screen'],
];
</script>

<template>
    <aside
        class="flex h-full w-[300px] flex-col overflow-hidden border-l border-border bg-card"
    >
        <!-- Header -->
        <div class="flex items-center gap-3 px-5 py-4">
            <div class="flex size-9 items-center justify-center rounded" :class="header.tint">
                <component :is="header.icon" class="size-[18px]" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-foreground">
                    {{ selectedDesk ? selectedDesk.label || 'Untitled' : header.title }}
                </p>
                <p class="text-xs text-muted-foreground">
                    <template v-if="selectedDesk">{{ header.title }}<template v-if="selectedDesk.id === null"> · not saved yet</template></template>
                    <template v-else-if="selectedElement">Drawing</template>
                    <template v-else>{{ width }} × {{ height }}</template>
                </p>
            </div>
            <div v-if="hasSelection" class="flex">
                <button
                    type="button"
                    class="rounded p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    title="Duplicate (Ctrl+D)"
                    @click="store.duplicateSelected()"
                >
                    <Copy class="size-4" />
                </button>
                <button
                    type="button"
                    class="rounded p-2 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                    title="Delete (Del)"
                    @click="store.removeSelected()"
                >
                    <Trash2 class="size-4" />
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <!-- Desk or meeting room -->
            <template v-if="selectedDesk">
                <div v-if="deskProblems.length" class="mx-5 mb-4 flex gap-2 rounded border border-destructive/30 bg-destructive/10 p-3 text-xs text-destructive">
                    <AlertCircle class="mt-px size-4 shrink-0" />
                    <ul class="space-y-0.5">
                        <li v-for="problem in deskProblems" :key="problem">{{ problem }}</li>
                    </ul>
                </div>

                <div :class="section">
                    <div>
                        <label :class="labelClass" for="desk-label">Name</label>
                        <input id="desk-label" :class="field" :value="selectedDesk.label" maxlength="40" @focus="store.checkpoint()" @input="updateDesk({ label: textFrom($event) })" />
                    </div>

                    <div class="grid grid-cols-2 gap-1 rounded bg-muted p-1">
                        <button
                            v-for="option in deskTypes"
                            :key="option.value"
                            type="button"
                            class="rounded-sm py-1.5 text-xs font-medium transition"
                            :class="
                                selectedDesk.type === option.value
                                    ? 'bg-bp-ink text-bp-cyan shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="selectedDesk.type !== option.value && changeDesk({ type: option.value })"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-foreground">Bookable</p>
                            <p class="text-xs text-muted-foreground">Turn off to keep it on the plan but unavailable.</p>
                        </div>
                        <ToggleSwitch :model-value="selectedDesk.is_bookable" label="Bookable" @update:model-value="changeDesk({ is_bookable: $event })" />
                    </div>

                    <div>
                        <label :class="labelClass" for="desk-assigned">Permanently assigned to</label>
                        <select
                            id="desk-assigned"
                            :class="field"
                            :value="selectedDesk.assigned_user_id ?? ''"
                            @change="changeDesk({ assigned_user_id: textFrom($event) === '' ? null : Number(textFrom($event)) })"
                        >
                            <option value="">Nobody · hot desk</option>
                            <option v-for="member in members" :key="member.id" :value="member.id">{{ member.name }}</option>
                        </select>
                        <p class="mt-1.5 text-xs text-muted-foreground">Only this person can book an assigned desk.</p>
                    </div>
                </div>

                <div :class="section">
                    <p class="bp-label">Size</p>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label :class="labelClass" for="desk-width">W</label>
                            <input id="desk-width" type="number" min="10" step="10" :class="field" :value="selectedDesk.width" @change="changeDesk({ width: Math.max(10, numberFrom($event)) })" />
                        </div>
                        <div>
                            <label :class="labelClass" for="desk-height">H</label>
                            <input id="desk-height" type="number" min="10" step="10" :class="field" :value="selectedDesk.height" @change="changeDesk({ height: Math.max(10, numberFrom($event)) })" />
                        </div>
                        <div>
                            <label :class="labelClass" for="desk-rotation">Angle</label>
                            <input id="desk-rotation" type="number" step="15" :class="field" :value="selectedDesk.rotation" @change="changeDesk({ rotation: numberFrom($event) % 360 })" />
                        </div>
                    </div>
                </div>

                <div :class="section">
                    <div>
                        <p class="bp-label">Add-ons</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <template v-if="addOnOffers.length">What comes with this {{ selectedDesk.type === 'desk' ? 'desk' : 'room' }}.</template>
                            <template v-else>This office has no add-ons yet. Add them in the office settings, then attach them here.</template>
                        </p>
                    </div>

                    <ul class="space-y-2">
                        <li
                            v-for="offer in addOnOffers"
                            :key="offer.id"
                            class="rounded border p-3 transition"
                            :class="attachment(offer.id) ? 'border-bp-cyan bg-bp-cyan/5' : 'border-border'"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">{{ offer.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ offerSummary(offer) }}</p>
                                </div>
                                <ToggleSwitch :model-value="attachment(offer.id) !== undefined" :label="offer.name" @update:model-value="toggleAddOn(offer, $event)" />
                            </div>

                            <div v-if="attachment(offer.id)" class="mt-3 grid grid-cols-2 gap-2">
                                <div>
                                    <label :class="labelClass" :for="`addon-${offer.id}-availability`">On this desk</label>
                                    <select :id="`addon-${offer.id}-availability`" :class="field" :value="attachment(offer.id)?.availability ?? ''" @change="onAvailabilityChange(offer.id, $event)">
                                        <option value="">Default</option>
                                        <option value="included">Included</option>
                                        <option value="optional">Optional</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass" :for="`addon-${offer.id}-price`">Price</label>
                                    <input
                                        :id="`addon-${offer.id}-price`"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="Default"
                                        :class="field"
                                        :value="priceInput(attachment(offer.id)?.price_cents ?? null)"
                                        @change="onPriceChange(offer.id, $event)"
                                    />
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </template>

            <!-- Wall, room or label -->
            <template v-else-if="selectedElement">
                <div :class="section">
                    <template v-if="selectedElement.type === 'wall'">
                        <div>
                            <label :class="labelClass" for="wall-thickness">Thickness</label>
                            <input
                                id="wall-thickness"
                                type="range"
                                min="2"
                                max="30"
                                class="w-full accent-bp-amber"
                                :value="selectedElement.thickness"
                                @pointerdown="store.checkpoint()"
                                @input="updateElement({ thickness: numberFrom($event) })"
                            />
                            <p class="mt-1 text-right text-xs tabular-nums text-muted-foreground">{{ selectedElement.thickness }} px</p>
                        </div>
                        <p class="text-xs text-muted-foreground">Drag the round handles to move corners, or drag the wall to move it.</p>
                    </template>

                    <template v-else-if="selectedElement.type === 'room'">
                        <div>
                            <label :class="labelClass" for="room-label">Name</label>
                            <input
                                id="room-label"
                                :class="field"
                                :value="selectedElement.label"
                                placeholder="e.g. Kitchen"
                                maxlength="120"
                                @focus="store.checkpoint()"
                                @input="updateElement({ label: textFrom($event) })"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label :class="labelClass" for="room-width">W</label>
                                <input id="room-width" type="number" min="10" step="10" :class="field" :value="selectedElement.width" @change="changeElement({ width: Math.max(10, numberFrom($event)) })" />
                            </div>
                            <div>
                                <label :class="labelClass" for="room-height">H</label>
                                <input id="room-height" type="number" min="10" step="10" :class="field" :value="selectedElement.height" @change="changeElement({ height: Math.max(10, numberFrom($event)) })" />
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">Rooms mark areas like a kitchen. For a bookable room, use the Meeting room tool.</p>
                    </template>

                    <template v-else-if="selectedElement.type === 'label'">
                        <div>
                            <label :class="labelClass" for="label-text">Text</label>
                            <input id="label-text" :class="field" :value="selectedElement.text" maxlength="255" @focus="store.checkpoint()" @input="updateElement({ text: textFrom($event) })" />
                        </div>
                        <div>
                            <label :class="labelClass" for="label-size">Size</label>
                            <input
                                id="label-size"
                                type="number"
                                min="6"
                                max="200"
                                :class="field"
                                :value="selectedElement.font_size"
                                @change="changeElement({ font_size: Math.min(200, Math.max(6, numberFrom($event))) })"
                            />
                        </div>
                    </template>
                </div>
            </template>

            <!-- Nothing selected: floor overview -->
            <template v-else>
                <div :class="section">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded bg-bp-ink p-3">
                            <p class="font-mono text-2xl font-semibold tabular-nums text-bp-cyan">{{ counts.desks }}</p>
                            <p class="bp-label !text-[#E9F1FB]/60">Desks</p>
                        </div>
                        <div class="rounded bg-bp-ink p-3">
                            <p class="font-mono text-2xl font-semibold tabular-nums text-bp-amber">{{ counts.meetingRooms }}</p>
                            <p class="bp-label !text-[#E9F1FB]/60">Meeting rooms</p>
                        </div>
                        <div class="rounded border border-border p-3">
                            <p class="font-mono text-2xl font-semibold tabular-nums">{{ counts.walls }}</p>
                            <p class="bp-label">Walls</p>
                        </div>
                        <div class="rounded border border-border p-3">
                            <p class="font-mono text-2xl font-semibold tabular-nums">{{ counts.rooms }}</p>
                            <p class="bp-label">Rooms</p>
                        </div>
                    </div>
                </div>

                <div :class="section">
                    <p class="bp-label">Canvas size</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label :class="labelClass" for="floor-width">Width</label>
                            <input id="floor-width" type="number" min="200" max="10000" step="100" :class="field" :value="width" @change="setCanvas('width', numberFrom($event))" />
                        </div>
                        <div>
                            <label :class="labelClass" for="floor-height">Height</label>
                            <input id="floor-height" type="number" min="200" max="10000" step="100" :class="field" :value="height" @change="setCanvas('height', numberFrom($event))" />
                        </div>
                    </div>
                </div>

                <div :class="section">
                    <p class="bp-label">Shortcuts</p>
                    <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-2 text-xs">
                        <template v-for="[keys, action] in shortcuts" :key="keys">
                            <dt>
                                <kbd class="rounded border border-border bg-muted px-1.5 py-0.5 font-mono text-[11px] font-medium">
                                    {{ keys }}
                                </kbd>
                            </dt>
                            <dd class="self-center text-muted-foreground">{{ action }}</dd>
                        </template>
                    </dl>
                    <p class="text-xs text-muted-foreground">Dashed desks are permanently assigned. Grey desks are not bookable.</p>
                </div>
            </template>
        </div>
    </aside>
</template>
