<script setup lang="ts">
import { GRID_SIZE, useFloorPlanStore, type EditorDesk } from '@/stores/useFloorPlanStore';
import type { LabelElement, RoomElement, UserMinimal, WallElement } from '@/types/hotdesk';
import { useElementSize, useEventListener } from '@vueuse/core';
import type Konva from 'konva';
import type { KonvaEventObject } from 'konva/lib/Node';
import { Maximize, Minus, Plus } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import {
    Circle as VCircle,
    Group as VGroup,
    Layer as VLayer,
    Line as VLine,
    Rect as VRect,
    Stage as VStage,
    Text as VText,
    Transformer as VTransformer,
    type VueKonvaRef,
} from 'vue-konva';
import { computed, reactive, ref, watch } from 'vue';

interface Point {
    x: number;
    y: number;
}

const props = defineProps<{
    /** Desk keys with validation problems, outlined in red. */
    errorKeys: Set<string>;
    members: UserMinimal[];
}>();

const MIN_SCALE = 0.2;
const MAX_SCALE = 4;
const FONT = 'DM Sans, ui-sans-serif, system-ui, sans-serif';
const MONO = 'IBM Plex Mono, ui-monospace, monospace';
// Room left around the plan when fitting, clear of the tool dock and zoom controls.
const FIT_INSETS = { top: 40, right: 40, bottom: 80, left: 88 };

const store = useFloorPlanStore();
const { width, height, elements, desks, selectedKey, tool } = storeToRefs(store);

const container = ref<HTMLDivElement | null>(null);
const { width: viewWidth, height: viewHeight } = useElementSize(container);
const stageRef = ref<VueKonvaRef<Konva.Stage> | null>(null);
const transformerRef = ref<VueKonvaRef<Konva.Transformer> | null>(null);

const view = reactive({ scale: 1, x: FIT_INSETS.left, y: FIT_INSETS.top });
const pointer = ref<Point | null>(null);
const draftWall = ref<number[] | null>(null);
const draftRoom = ref<{ x0: number; y0: number; x1: number; y1: number } | null>(null);
const hasFitted = ref(false);
let lastClickAddedCorner = false;

const isSelectTool = computed(() => tool.value === 'select');

const initialsById = computed(() => {
    const map = new Map<number, string>();

    for (const member of props.members) {
        const parts = member.name.trim().split(/\s+/);
        map.set(member.id, ((parts[0]?.[0] ?? '') + (parts.length > 1 ? (parts[parts.length - 1][0] ?? '') : '')).toUpperCase());
    }

    return map;
});

// ---- Theme -------------------------------------------------------------

// The board is always a navy drawing board, in light and dark mode alike.
const palette = {
    floor: 'rgba(18, 48, 90, 0.72)',
    floorBorder: 'rgba(233, 241, 251, 0.18)',
    wall: '#E9F1FB',
    roomFill: 'rgba(255, 255, 255, 0.035)',
    roomStroke: 'rgba(233, 241, 251, 0.35)',
    roomText: 'rgba(233, 241, 251, 0.6)',
    labelText: 'rgba(233, 241, 251, 0.7)',
    chair: 'rgba(76, 195, 230, 0.35)',
    desk: { fill: 'rgba(76, 195, 230, 0.12)', stroke: '#4CC3E6', text: '#C9EFFF' },
    meeting: { fill: 'rgba(255, 194, 75, 0.08)', stroke: 'rgba(255, 194, 75, 0.75)', text: '#FFD98A' },
    disabled: { fill: 'rgba(255, 255, 255, 0.05)', stroke: 'rgba(233, 241, 251, 0.25)', text: 'rgba(233, 241, 251, 0.45)' },
    selected: { fill: '#FFC24B', stroke: '#FFC24B', text: '#0B1A2F' },
    badge: '#4CC3E6',
    badgeText: '#0B1A2F',
    selection: '#FFC24B',
    handleFill: '#0B1A2F',
    dimension: 'rgba(201, 239, 255, 0.85)',
    error: '#F87171',
    draft: '#FFC24B',
};

// Grid lines on the board move and scale with the plan so panning feels anchored.
const backdropStyle = computed(() => {
    const minor = GRID_SIZE * 2 * view.scale;
    const major = GRID_SIZE * 10 * view.scale;
    const line = (colour: string) => `linear-gradient(${colour} 1px, transparent 1px), linear-gradient(90deg, ${colour} 1px, transparent 1px)`;

    return {
        backgroundImage: `${line('rgba(233, 241, 251, 0.07)')}, ${line('rgba(233, 241, 251, 0.035)')}`,
        backgroundSize: `${major}px ${major}px, ${major}px ${major}px, ${minor}px ${minor}px, ${minor}px ${minor}px`,
        backgroundPosition: `${view.x}px ${view.y}px`,
    };
});

// ---- View: zoom, pan, fit ----------------------------------------------

const stageConfig = computed(() => ({
    width: viewWidth.value,
    height: viewHeight.value,
    scaleX: view.scale,
    scaleY: view.scale,
    x: view.x,
    y: view.y,
    draggable: isSelectTool.value,
}));

function clampScale(scale: number): number {
    return Math.min(MAX_SCALE, Math.max(MIN_SCALE, scale));
}

function fit(): void {
    const availableWidth = viewWidth.value - FIT_INSETS.left - FIT_INSETS.right;
    const availableHeight = viewHeight.value - FIT_INSETS.top - FIT_INSETS.bottom;

    if (availableWidth <= 0 || availableHeight <= 0) {
        return;
    }

    const scale = clampScale(Math.min(availableWidth / width.value, availableHeight / height.value, 1.5));
    view.scale = scale;
    view.x = FIT_INSETS.left + (availableWidth - width.value * scale) / 2;
    view.y = FIT_INSETS.top + (availableHeight - height.value * scale) / 2;
}

function zoomAround(screenPoint: Point, factor: number): void {
    const oldScale = view.scale;
    const newScale = clampScale(oldScale * factor);
    const floorPoint = { x: (screenPoint.x - view.x) / oldScale, y: (screenPoint.y - view.y) / oldScale };

    view.scale = newScale;
    view.x = screenPoint.x - floorPoint.x * newScale;
    view.y = screenPoint.y - floorPoint.y * newScale;
}

function zoomBy(factor: number): void {
    zoomAround({ x: viewWidth.value / 2, y: viewHeight.value / 2 }, factor);
}

watch([viewWidth, viewHeight], () => {
    if (!hasFitted.value && viewWidth.value > 0 && viewHeight.value > 0) {
        fit();
        hasFitted.value = true;
    }
});

function onWheel(event: KonvaEventObject<WheelEvent>): void {
    event.evt.preventDefault();
    const position = event.target.getStage()?.getPointerPosition();

    if (position) {
        zoomAround(position, event.evt.deltaY > 0 ? 1 / 1.1 : 1.1);
    }
}

function onStageDragEnd(event: KonvaEventObject<DragEvent>): void {
    const stage = event.target.getStage();

    if (stage && event.target === stage) {
        view.x = stage.x();
        view.y = stage.y();
    }
}

function onStageDragMove(event: KonvaEventObject<DragEvent>): void {
    onStageDragEnd(event);
}

// ---- Drawing -----------------------------------------------------------

function floorPointer(): Point | null {
    const position = stageRef.value?.getNode().getRelativePointerPosition();

    return position ? { x: store.snap(position.x), y: store.snap(position.y) } : null;
}

/** Holding Shift keeps the next wall segment horizontal or vertical. */
function constrain(point: Point, shiftKey: boolean): Point {
    const points = draftWall.value;

    if (!shiftKey || !points || points.length < 2) {
        return point;
    }

    const lastX = points[points.length - 2];
    const lastY = points[points.length - 1];

    return Math.abs(point.x - lastX) >= Math.abs(point.y - lastY) ? { x: point.x, y: lastY } : { x: lastX, y: point.y };
}

function isEmptySpace(event: KonvaEventObject<PointerEvent>): boolean {
    return event.target === event.target.getStage() || event.target.name() === 'floor-background';
}

function onPointerDown(event: KonvaEventObject<PointerEvent>): void {
    if (event.evt.button !== undefined && event.evt.button !== 0) {
        return;
    }

    const raw = floorPointer();

    if (!raw) {
        return;
    }

    switch (tool.value) {
        case 'select':
            if (isEmptySpace(event)) {
                selectedKey.value = null;
            }
            break;
        case 'wall': {
            const point = constrain(raw, event.evt.shiftKey);
            const points = draftWall.value;

            if (!points) {
                draftWall.value = [point.x, point.y];
                lastClickAddedCorner = true;
            } else if (points[points.length - 2] !== point.x || points[points.length - 1] !== point.y) {
                draftWall.value = [...points, point.x, point.y];
                lastClickAddedCorner = true;
            } else {
                lastClickAddedCorner = false;
            }
            break;
        }
        case 'room':
            draftRoom.value = { x0: raw.x, y0: raw.y, x1: raw.x, y1: raw.y };
            break;
        case 'label':
            store.addLabel(raw.x, raw.y);
            tool.value = 'select';
            break;
        case 'desk':
        case 'meeting_room':
            store.addDesk(tool.value, raw.x, raw.y);
            break;
    }
}

function onPointerMove(event: KonvaEventObject<PointerEvent>): void {
    const raw = floorPointer();

    if (!raw) {
        return;
    }

    pointer.value = tool.value === 'wall' ? constrain(raw, event.evt.shiftKey) : raw;

    if (draftRoom.value) {
        draftRoom.value = { ...draftRoom.value, x1: raw.x, y1: raw.y };
    }
}

function onPointerUp(): void {
    const room = draftRoom.value;

    if (!room) {
        return;
    }

    draftRoom.value = null;
    const roomWidth = Math.abs(room.x1 - room.x0);
    const roomHeight = Math.abs(room.y1 - room.y0);

    if (roomWidth >= GRID_SIZE * 2 && roomHeight >= GRID_SIZE * 2) {
        store.addRoom(Math.min(room.x0, room.x1), Math.min(room.y0, room.y1), roomWidth, roomHeight);
        tool.value = 'select';
    }
}

function finishWall(): void {
    const points = draftWall.value;
    draftWall.value = null;

    if (points && points.length >= 4) {
        store.addWall(points);
    }
}

/**
 * Konva reports any two quick clicks as a double-click, even far apart. Only
 * finish the wall when the second click landed on the corner just placed,
 * so clicking corners quickly does not end the wall early.
 */
function onDoubleClick(): void {
    if (tool.value === 'wall' && !lastClickAddedCorner) {
        finishWall();
    }
}

watch(tool, (next, previous) => {
    if (previous === 'wall' && next !== 'wall') {
        finishWall();
    }

    draftRoom.value = null;
});

const draftWallPoints = computed(() => {
    const points = draftWall.value;

    if (!points) {
        return null;
    }

    return pointer.value ? [...points, pointer.value.x, pointer.value.y] : points;
});

const draftRoomRect = computed(() => {
    const room = draftRoom.value;

    return room
        ? { x: Math.min(room.x0, room.x1), y: Math.min(room.y0, room.y1), width: Math.abs(room.x1 - room.x0), height: Math.abs(room.y1 - room.y0) }
        : null;
});

// ---- Keyboard ----------------------------------------------------------

function isTyping(target: EventTarget | null): boolean {
    return target instanceof HTMLElement && (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable);
}

const toolShortcuts: Record<string, typeof tool.value> = {
    v: 'select',
    w: 'wall',
    r: 'room',
    t: 'label',
    d: 'desk',
    m: 'meeting_room',
};

useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    if (isTyping(event.target)) {
        // Escape leaves a panel field so the next shortcut reaches the canvas.
        if (event.key === 'Escape') {
            (event.target as HTMLElement).blur();
        }
        return;
    }

    const key = event.key.toLowerCase();
    const modifier = event.ctrlKey || event.metaKey;

    if (modifier && key === 'z') {
        event.preventDefault();
        if (event.shiftKey) {
            store.redo();
        } else {
            store.undo();
        }
        return;
    }

    if (modifier && key === 'y') {
        event.preventDefault();
        store.redo();
        return;
    }

    if (modifier && key === 'd') {
        event.preventDefault();
        store.duplicateSelected();
        return;
    }

    if (modifier) {
        return;
    }

    if (key === 'escape') {
        if (draftWall.value) {
            finishWall();
        } else if (tool.value !== 'select') {
            tool.value = 'select';
        } else {
            selectedKey.value = null;
        }
        return;
    }

    if (key === 'enter' && draftWall.value) {
        finishWall();
        return;
    }

    if (key === 'delete' || key === 'backspace') {
        event.preventDefault();
        store.removeSelected();
        return;
    }

    const nudges: Record<string, [number, number]> = {
        arrowleft: [-1, 0],
        arrowright: [1, 0],
        arrowup: [0, -1],
        arrowdown: [0, 1],
    };

    if (key in nudges && selectedKey.value !== null) {
        event.preventDefault();
        const step = event.shiftKey ? GRID_SIZE * 5 : GRID_SIZE;
        const [dx, dy] = nudges[key];
        store.nudgeSelected(dx * step, dy * step);
        return;
    }

    if (key === '0') {
        fit();
        return;
    }

    if (key in toolShortcuts) {
        tool.value = toolShortcuts[key];
    }
});

// ---- Shapes ------------------------------------------------------------

const rooms = computed(() => elements.value.filter((element): element is RoomElement => element.type === 'room'));
const walls = computed(() => elements.value.filter((element): element is WallElement => element.type === 'wall'));
const labels = computed(() => elements.value.filter((element): element is LabelElement => element.type === 'label'));
const selectedWall = computed(() => walls.value.find((wall) => wall.id === selectedKey.value) ?? null);

function select(key: string): void {
    if (isSelectTool.value) {
        selectedKey.value = key;
    }
}

function deskStyle(desk: EditorDesk): { fill: string; stroke: string; text: string } {
    const base = !desk.is_bookable ? palette.disabled : desk.type === 'desk' ? palette.desk : palette.meeting;

    if (props.errorKeys.has(desk.key)) {
        return { ...base, stroke: palette.error };
    }

    // Amber means "this one": the same colour a booker sees for their pick.
    return selectedKey.value === desk.key ? palette.selected : base;
}

function selectionGlow(key: string): Record<string, number | string> {
    return selectedKey.value === key ? { shadowColor: palette.selection, shadowBlur: 18, shadowOpacity: 0.55 } : { shadowOpacity: 0 };
}

/** Size readout shown above the selected desk or room, like a dimension line on a plan. */
const dimension = computed(() => {
    const desk = store.selectedDesk;
    const element = store.selectedElement;
    const target = desk ?? (element?.type === 'room' ? element : null);

    if (!target || !isSelectTool.value) {
        return null;
    }

    // Drawn below the item (and below a desk's chair) so it never sits under the rotate handle.
    const below = target.height + (desk?.type === 'desk' ? 14 : 2);

    return { x: target.x, y: target.y, width: target.width, below, rotation: target.rotation, text: `${Math.round(target.width)} × ${Math.round(target.height)}` };
});

// Snap while dragging so what you see is where it lands.
function onShapeDragMove(event: KonvaEventObject<DragEvent>): void {
    const node = event.target;
    node.position({ x: store.snap(node.x()), y: store.snap(node.y()) });
}

function onDeskDragEnd(desk: EditorDesk, event: KonvaEventObject<DragEvent>): void {
    const node = event.target;
    const position = { x: store.snap(node.x()), y: store.snap(node.y()) };
    node.position(position);
    store.updateDesk(desk.key, position);
}

function onElementDragEnd(id: string, event: KonvaEventObject<DragEvent>): void {
    const node = event.target;
    const position = { x: store.snap(node.x()), y: store.snap(node.y()) };
    node.position(position);
    store.updateElement(id, position);
}

function onWallDragEnd(wall: WallElement, event: KonvaEventObject<DragEvent>): void {
    const node = event.target;
    const dx = store.snap(node.x());
    const dy = store.snap(node.y());
    node.position({ x: 0, y: 0 });
    store.updateElement(wall.id, { points: wall.points.map((value, index) => value + (index % 2 === 0 ? dx : dy)) });
}

function onVertexDragMove(wall: WallElement, index: number, event: KonvaEventObject<DragEvent>): void {
    const node = event.target;
    const position = { x: store.snap(node.x()), y: store.snap(node.y()) };
    node.position(position);
    const points = [...wall.points];
    points[index * 2] = position.x;
    points[index * 2 + 1] = position.y;
    store.updateElement(wall.id, { points });
}

function wallVertices(wall: WallElement): Point[] {
    const vertices: Point[] = [];

    for (let index = 0; index < wall.points.length; index += 2) {
        vertices.push({ x: wall.points[index], y: wall.points[index + 1] });
    }

    return vertices;
}

// ---- Transformer (resize and rotate) -----------------------------------

const transformerConfig = computed(() => ({
    rotationSnaps: [0, 45, 90, 135, 180, 225, 270, 315],
    rotationSnapTolerance: 8,
    rotateAnchorOffset: 24,
    keepRatio: false,
    ignoreStroke: true,
    borderStroke: palette.selection,
    borderStrokeWidth: 1.5,
    anchorStroke: palette.selection,
    anchorFill: palette.handleFill,
    anchorSize: 9,
    anchorCornerRadius: 2,
    padding: 4,
    enabledAnchors: store.selectedElement?.type === 'label' ? [] : ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'middle-left', 'middle-right', 'top-center', 'bottom-center'],
    boundBoxFunc: (oldBox: { width: number; height: number }, newBox: { width: number; height: number }) =>
        Math.abs(newBox.width) < GRID_SIZE || Math.abs(newBox.height) < GRID_SIZE ? oldBox : newBox,
}));

function attachTransformer(): void {
    const transformer = transformerRef.value?.getNode();
    const stage = stageRef.value?.getNode();

    if (!transformer || !stage) {
        return;
    }

    const key = selectedKey.value;
    const isTransformable = key !== null && isSelectTool.value && selectedWall.value === null;
    const node = isTransformable ? stage.findOne(`#${CSS.escape(key)}`) : undefined;

    transformer.nodes(node ? [node] : []);
    transformer.getLayer()?.batchDraw();
}

watch([selectedKey, tool, () => desks.value.length, () => elements.value.length], attachTransformer, { flush: 'post' });

function onTransformEnd(event: KonvaEventObject<Event>): void {
    const node = event.target;
    const scaleX = node.scaleX();
    const scaleY = node.scaleY();
    node.scale({ x: 1, y: 1 });

    const key = node.id();
    const position = { x: store.snap(node.x()), y: store.snap(node.y()) };
    const rotation = Math.round(node.rotation());
    node.position(position);

    const desk = desks.value.find((candidate) => candidate.key === key);

    if (desk) {
        store.updateDesk(key, {
            ...position,
            rotation,
            width: Math.max(GRID_SIZE, Math.round(desk.width * scaleX)),
            height: Math.max(GRID_SIZE, Math.round(desk.height * scaleY)),
        });
        return;
    }

    const element = elements.value.find((candidate) => candidate.id === key);

    if (element?.type === 'room') {
        store.updateElement(key, {
            ...position,
            rotation,
            width: Math.max(GRID_SIZE, Math.round(element.width * scaleX)),
            height: Math.max(GRID_SIZE, Math.round(element.height * scaleY)),
        });
    } else if (element?.type === 'label') {
        store.updateElement(key, { ...position, rotation });
    }
}

// ---- Hints -------------------------------------------------------------

const hint = computed(() => {
    switch (tool.value) {
        case 'wall':
            return draftWall.value ? 'Click to add corners · Double-click or Enter to finish · Shift for straight lines' : 'Click to start a wall';
        case 'room':
            return 'Drag to draw a room';
        case 'label':
            return 'Click to place a label';
        case 'desk':
            return 'Click to place desks · Esc when done';
        case 'meeting_room':
            return 'Click to place meeting rooms · Esc when done';
        default:
            return null;
    }
});

const controlButton = 'flex size-8 items-center justify-center rounded text-[#E9F1FB]/70 transition hover:bg-white/10 hover:text-white';
</script>

<template>
    <div ref="container" class="relative h-full w-full overflow-hidden bg-bp-navy" :style="backdropStyle" :class="isSelectTool ? 'cursor-default' : 'cursor-crosshair'">
        <v-stage
            ref="stageRef"
            :config="stageConfig"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @dblclick="onDoubleClick"
            @dbltap="onDoubleClick"
            @wheel="onWheel"
            @dragmove="onStageDragMove"
            @dragend="onStageDragEnd"
        >
            <v-layer>
                <v-rect
                    :config="{
                        name: 'floor-background',
                        width,
                        height,
                        fill: palette.floor,
                        stroke: palette.floorBorder,
                        strokeWidth: 1 / view.scale,
                        cornerRadius: 6,
                    }"
                />
            </v-layer>

            <v-layer>
                <v-group
                    v-for="room in rooms"
                    :key="room.id"
                    :config="{ id: room.id, x: room.x, y: room.y, rotation: room.rotation, draggable: isSelectTool }"
                    @pointerdown="select(room.id)"
                    @dragstart="store.checkpoint()"
                    @dragmove="onShapeDragMove"
                    @dragend="onElementDragEnd(room.id, $event)"
                    @transformstart="store.checkpoint()"
                    @transformend="onTransformEnd"
                >
                    <v-rect
                        :config="{
                            width: room.width,
                            height: room.height,
                            fill: palette.roomFill,
                            stroke: selectedKey === room.id ? palette.selection : palette.roomStroke,
                            strokeWidth: 1.5,
                            dash: [6, 4],
                            cornerRadius: 4,
                        }"
                    />
                    <v-text
                        :config="{
                            x: 10,
                            y: 10,
                            text: room.label.toUpperCase(),
                            fontSize: 10,
                            fontFamily: MONO,
                            fontStyle: '500',
                            letterSpacing: 1,
                            fill: palette.roomText,
                            listening: false,
                        }"
                    />
                </v-group>

                <v-line
                    v-for="wall in walls"
                    :key="wall.id"
                    :config="{
                        id: wall.id,
                        points: wall.points,
                        stroke: selectedKey === wall.id ? palette.selection : palette.wall,
                        strokeWidth: wall.thickness,
                        lineCap: 'round',
                        lineJoin: 'round',
                        hitStrokeWidth: Math.max(wall.thickness, 14),
                        draggable: isSelectTool,
                    }"
                    @pointerdown="select(wall.id)"
                    @dragstart="store.checkpoint()"
                    @dragend="onWallDragEnd(wall, $event)"
                />

                <v-text
                    v-for="label in labels"
                    :key="label.id"
                    :config="{
                        id: label.id,
                        x: label.x,
                        y: label.y,
                        rotation: label.rotation,
                        text: label.text,
                        fontSize: label.font_size,
                        fontFamily: FONT,
                        fontStyle: '500',
                        fill: selectedKey === label.id ? palette.selection : palette.labelText,
                        draggable: isSelectTool,
                    }"
                    @pointerdown="select(label.id)"
                    @dragstart="store.checkpoint()"
                    @dragmove="onShapeDragMove"
                    @dragend="onElementDragEnd(label.id, $event)"
                    @transformstart="store.checkpoint()"
                    @transformend="onTransformEnd"
                />

                <v-group
                    v-for="desk in desks"
                    :key="desk.key"
                    :config="{ id: desk.key, x: desk.x, y: desk.y, rotation: desk.rotation, draggable: isSelectTool }"
                    @pointerdown="select(desk.key)"
                    @dragstart="store.checkpoint()"
                    @dragmove="onShapeDragMove"
                    @dragend="onDeskDragEnd(desk, $event)"
                    @transformstart="store.checkpoint()"
                    @transformend="onTransformEnd"
                >
                    <!-- Chair, so a desk reads as a desk at a glance. -->
                    <v-rect
                        v-if="desk.type === 'desk'"
                        :config="{
                            x: desk.width * 0.3,
                            y: desk.height + 3,
                            width: desk.width * 0.4,
                            height: 9,
                            cornerRadius: 4,
                            fill: palette.chair,
                            listening: false,
                        }"
                    />
                    <v-rect
                        :config="{
                            width: desk.width,
                            height: desk.height,
                            fill: deskStyle(desk).fill,
                            stroke: deskStyle(desk).stroke,
                            strokeWidth: selectedKey === desk.key || errorKeys.has(desk.key) ? 2 : 1.5,
                            dash: desk.assigned_user_id !== null ? [5, 3] : undefined,
                            cornerRadius: desk.type === 'desk' ? 4 : 6,
                            ...selectionGlow(desk.key),
                        }"
                    />
                    <v-text
                        :config="{
                            width: desk.width,
                            height: desk.height,
                            text: desk.label,
                            align: 'center',
                            verticalAlign: 'middle',
                            fontSize: desk.type === 'desk' ? 11 : 13,
                            fontFamily: FONT,
                            fontStyle: '600',
                            fill: deskStyle(desk).text,
                            padding: 4,
                            ellipsis: true,
                            wrap: 'none',
                            listening: false,
                        }"
                    />
                    <v-group v-if="desk.assigned_user_id !== null" :config="{ x: desk.width - 2, y: 2, listening: false }">
                        <v-circle :config="{ radius: 9, fill: palette.badge, stroke: '#0F2747', strokeWidth: 2 }" />
                        <v-text
                            :config="{
                                x: -9,
                                y: -9,
                                width: 18,
                                height: 18,
                                text: initialsById.get(desk.assigned_user_id) ?? '•',
                                align: 'center',
                                verticalAlign: 'middle',
                                fontSize: 7.5,
                                fontFamily: FONT,
                                fontStyle: '700',
                                fill: palette.badgeText,
                            }"
                        />
                    </v-group>
                </v-group>

                <template v-if="selectedWall && isSelectTool">
                    <v-circle
                        v-for="(vertex, index) in wallVertices(selectedWall)"
                        :key="`${selectedWall.id}-${index}`"
                        :config="{
                            x: vertex.x,
                            y: vertex.y,
                            radius: 6 / view.scale,
                            fill: palette.handleFill,
                            stroke: palette.selection,
                            strokeWidth: 2 / view.scale,
                            draggable: true,
                        }"
                        @dragstart="store.checkpoint()"
                        @dragmove="onVertexDragMove(selectedWall, index, $event)"
                    />
                </template>

                <v-line
                    v-if="draftWallPoints"
                    :config="{ points: draftWallPoints, stroke: palette.draft, strokeWidth: 8, lineCap: 'round', lineJoin: 'round', opacity: 0.55, listening: false }"
                />
                <v-rect
                    v-if="draftRoomRect"
                    :config="{ ...draftRoomRect, fill: palette.roomFill, stroke: palette.draft, strokeWidth: 1.5, dash: [6, 4], cornerRadius: 4, listening: false }"
                />

                <!-- Dimension readout above the selection, sized to stay legible at any zoom. -->
                <v-group v-if="dimension" :config="{ x: dimension.x, y: dimension.y, rotation: dimension.rotation, listening: false }">
                    <v-line
                        :config="{
                            points: [0, dimension.below + 10 / view.scale, dimension.width, dimension.below + 10 / view.scale],
                            stroke: palette.dimension,
                            strokeWidth: 1 / view.scale,
                        }"
                    />
                    <v-line
                        :config="{
                            points: [0, dimension.below + 6 / view.scale, 0, dimension.below + 14 / view.scale],
                            stroke: palette.dimension,
                            strokeWidth: 1 / view.scale,
                        }"
                    />
                    <v-line
                        :config="{
                            points: [dimension.width, dimension.below + 6 / view.scale, dimension.width, dimension.below + 14 / view.scale],
                            stroke: palette.dimension,
                            strokeWidth: 1 / view.scale,
                        }"
                    />
                    <v-text
                        :config="{
                            x: 0,
                            y: dimension.below + 16 / view.scale,
                            width: dimension.width,
                            align: 'center',
                            text: dimension.text,
                            fontSize: 10 / view.scale,
                            fontFamily: MONO,
                            fill: palette.dimension,
                        }"
                    />
                </v-group>

                <v-transformer ref="transformerRef" :config="transformerConfig" />
            </v-layer>
        </v-stage>

        <!-- Zoom controls -->
        <div class="absolute bottom-4 left-4 flex items-center gap-0.5 rounded-md border border-white/10 bg-bp-ink/90 p-1 shadow-lg backdrop-blur">
            <button type="button" :class="controlButton" title="Zoom out" @click="zoomBy(1 / 1.2)"><Minus class="size-4" /></button>
            <button type="button" class="h-8 min-w-14 rounded px-1 font-mono text-xs tabular-nums text-bp-cyan hover:bg-white/10" title="Fit to screen (0)" @click="fit()">
                {{ Math.round(view.scale * 100) }}%
            </button>
            <button type="button" :class="controlButton" title="Zoom in" @click="zoomBy(1.2)"><Plus class="size-4" /></button>
            <div class="mx-0.5 h-5 w-px bg-white/10" />
            <button type="button" :class="controlButton" title="Fit to screen (0)" @click="fit()"><Maximize class="size-4" /></button>
        </div>

        <!-- Pointer position, like the coordinate readout on a drawing -->
        <div v-if="pointer" class="pointer-events-none absolute bottom-6 right-4 font-mono text-[11px] tabular-nums text-[#E9F1FB]/50">
            X {{ pointer.x }} · Y {{ pointer.y }}
        </div>

        <!-- Contextual hint for drawing tools -->
        <Transition
            enter-active-class="transition duration-150"
            enter-from-class="translate-y-1 opacity-0"
            leave-active-class="transition duration-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="hint"
                class="pointer-events-none absolute bottom-4 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md border border-bp-amber/40 bg-bp-ink/90 px-3 py-1.5 text-xs font-medium text-bp-amber shadow-lg backdrop-blur"
            >
                {{ hint }}
            </div>
        </Transition>
    </div>
</template>
