<script setup lang="ts">
import { useFloorPlanStore, type FloorPlanTool } from '@/stores/useFloorPlanStore';
import { Grid3x3, Monitor, MousePointer2, PenLine, Square, Type, Users } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import type { Component } from 'vue';

const store = useFloorPlanStore();
const { tool, snapToGrid } = storeToRefs(store);

const groups: { value: FloorPlanTool; label: string; shortcut: string; icon: Component }[][] = [
    [{ value: 'select', label: 'Select', shortcut: 'V', icon: MousePointer2 }],
    [
        { value: 'wall', label: 'Wall', shortcut: 'W', icon: PenLine },
        { value: 'room', label: 'Room', shortcut: 'R', icon: Square },
        { value: 'label', label: 'Label', shortcut: 'T', icon: Type },
    ],
    [
        { value: 'desk', label: 'Desk', shortcut: 'D', icon: Monitor },
        { value: 'meeting_room', label: 'Meeting room', shortcut: 'M', icon: Users },
    ],
];

const tooltip =
    'pointer-events-none absolute left-full top-1/2 ml-3 flex -translate-y-1/2 items-center gap-2 whitespace-nowrap rounded bg-bp-ink px-2 py-1 text-xs font-medium text-white opacity-0 shadow-lg ring-1 ring-white/10 transition group-hover:opacity-100';
</script>

<template>
    <div class="flex flex-col gap-1 rounded-md border border-white/10 bg-bp-ink/90 p-1.5 shadow-xl backdrop-blur" role="toolbar" aria-label="Drawing tools" aria-orientation="vertical">
        <template v-for="(group, groupIndex) in groups" :key="groupIndex">
            <div v-if="groupIndex > 0" class="mx-1.5 my-0.5 h-px bg-white/10" />
            <button
                v-for="item in group"
                :key="item.value"
                type="button"
                :aria-pressed="tool === item.value"
                :aria-label="item.label"
                class="group relative flex size-9 items-center justify-center rounded transition"
                :class="tool === item.value ? 'bg-bp-cyan text-bp-ink' : 'text-[#E9F1FB]/70 hover:bg-white/10 hover:text-white'"
                @click="tool = item.value"
            >
                <component :is="item.icon" class="size-[18px]" />
                <span :class="tooltip">
                    {{ item.label }}
                    <kbd class="rounded bg-white/10 px-1 font-mono text-[10px] text-bp-cyan">{{ item.shortcut }}</kbd>
                </span>
            </button>
        </template>

        <div class="mx-1.5 my-0.5 h-px bg-white/10" />

        <button
            type="button"
            :aria-pressed="snapToGrid"
            aria-label="Snap to grid"
            class="group relative flex size-9 items-center justify-center rounded transition"
            :class="snapToGrid ? 'text-bp-amber' : 'text-[#E9F1FB]/40 hover:bg-white/10 hover:text-white'"
            @click="snapToGrid = !snapToGrid"
        >
            <Grid3x3 class="size-[18px]" />
            <span :class="tooltip">Snap to grid · {{ snapToGrid ? 'on' : 'off' }}</span>
        </button>
    </div>
</template>
