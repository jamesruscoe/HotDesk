<script setup lang="ts">
import type { Floor, LabelElement, RoomElement, WallElement } from '@/types/hotdesk';
import { computed } from 'vue';

/**
 * A static SVG blueprint of a floor plan for cards and lists. No Konva, so
 * it costs nothing on pages that only need a picture. Always drawn on navy.
 */
const props = defineProps<{
    floor: Floor;
}>();

const walls = computed(() => props.floor.layout.elements.filter((element): element is WallElement => element.type === 'wall'));
const rooms = computed(() => props.floor.layout.elements.filter((element): element is RoomElement => element.type === 'room'));
const labels = computed(() => props.floor.layout.elements.filter((element): element is LabelElement => element.type === 'label'));
const desks = computed(() => props.floor.desks ?? []);
const isEmpty = computed(() => props.floor.layout.elements.length === 0 && desks.value.length === 0);

function pointsAttr(points: number[]): string {
    const pairs: string[] = [];

    for (let index = 0; index < points.length; index += 2) {
        pairs.push(`${points[index]},${points[index + 1]}`);
    }

    return pairs.join(' ');
}
</script>

<template>
    <svg :viewBox="`0 0 ${floor.width} ${floor.height}`" preserveAspectRatio="xMidYMid meet" class="h-full w-full" role="img" :aria-label="`Plan of ${floor.name}`">
        <template v-if="!isEmpty">
            <rect :width="floor.width" :height="floor.height" rx="10" fill="#12305A" fill-opacity="0.7" />
            <rect
                v-for="room in rooms"
                :key="room.id"
                :x="room.x"
                :y="room.y"
                :width="room.width"
                :height="room.height"
                :transform="`rotate(${room.rotation} ${room.x} ${room.y})`"
                rx="6"
                fill="rgba(255,255,255,0.04)"
                stroke="rgba(233,241,251,0.35)"
                stroke-width="2"
                stroke-dasharray="8 5"
            />
            <polyline
                v-for="wall in walls"
                :key="wall.id"
                :points="pointsAttr(wall.points)"
                fill="none"
                stroke="#E9F1FB"
                :stroke-width="wall.thickness"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            <rect
                v-for="desk in desks"
                :key="desk.id"
                :x="desk.x"
                :y="desk.y"
                :width="desk.width"
                :height="desk.height"
                :transform="`rotate(${desk.rotation} ${desk.x} ${desk.y})`"
                rx="5"
                stroke-width="3"
                :fill="!desk.is_bookable ? 'rgba(255,255,255,0.06)' : desk.type === 'meeting_room' ? 'rgba(255,194,75,0.10)' : 'rgba(76,195,230,0.16)'"
                :stroke="!desk.is_bookable ? 'rgba(233,241,251,0.25)' : desk.type === 'meeting_room' ? 'rgba(255,194,75,0.8)' : '#4CC3E6'"
            />
            <text
                v-for="label in labels"
                :key="label.id"
                :x="label.x"
                :y="label.y + label.font_size"
                :font-size="label.font_size"
                fill="rgba(233,241,251,0.55)"
                font-weight="600"
            >
                {{ label.text }}
            </text>
        </template>
        <text
            v-else
            :x="floor.width / 2"
            :y="floor.height / 2"
            text-anchor="middle"
            dominant-baseline="middle"
            :font-size="Math.max(floor.width, floor.height) / 26"
            fill="rgba(233,241,251,0.5)"
            font-family="IBM Plex Mono, monospace"
        >
            NOT DRAWN YET
        </text>
    </svg>
</template>
