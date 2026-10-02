import type { Desk, DeskType, Floor, FloorElement, LabelElement, RoomElement, WallElement } from '@/types/hotdesk';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export type FloorPlanTool = 'select' | 'wall' | 'room' | 'label' | 'desk' | 'meeting_room';

/** A desk while editing. New desks have no id until saved; `key` identifies both. */
export interface EditorDesk extends Omit<Desk, 'id'> {
    key: string;
    id: number | null;
}

export interface FloorPlanPayload {
    width: number;
    height: number;
    layout: { elements: FloorElement[] };
    desks: Omit<EditorDesk, 'key'>[];
}

interface Snapshot {
    width: number;
    height: number;
    elements: FloorElement[];
    desks: EditorDesk[];
}

export const GRID_SIZE = 10;
const HISTORY_LIMIT = 100;
const DEFAULT_WALL_THICKNESS = 8;
const DEFAULT_LABEL_SIZE = 16;
const DESK_SIZES: Record<DeskType, { width: number; height: number }> = {
    desk: { width: 60, height: 40 },
    meeting_room: { width: 160, height: 100 },
};

let keySequence = 0;

// Not crypto.randomUUID(): that needs a secure context, and local .test domains are plain http.
function newKey(prefix: string): string {
    keySequence += 1;

    return `${prefix}-${Date.now().toString(36)}-${keySequence.toString(36)}`;
}

function clone<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

export const useFloorPlanStore = defineStore('floorPlan', () => {
    const width = ref(1200);
    const height = ref(800);
    const elements = ref<FloorElement[]>([]);
    const desks = ref<EditorDesk[]>([]);
    const selectedKey = ref<string | null>(null);
    const tool = ref<FloorPlanTool>('select');
    const snapToGrid = ref(true);

    const past = ref<string[]>([]);
    const future = ref<string[]>([]);
    const savedSnapshot = ref('');

    function serialize(): string {
        const snapshot: Snapshot = { width: width.value, height: height.value, elements: elements.value, desks: desks.value };

        return JSON.stringify(snapshot);
    }

    function restore(raw: string): void {
        const snapshot = JSON.parse(raw) as Snapshot;
        width.value = snapshot.width;
        height.value = snapshot.height;
        elements.value = snapshot.elements;
        desks.value = snapshot.desks;

        if (selectedKey.value !== null && !exists(selectedKey.value)) {
            selectedKey.value = null;
        }
    }

    function exists(key: string): boolean {
        return desks.value.some((desk) => desk.key === key) || elements.value.some((element) => element.id === key);
    }

    const isDirty = computed(() => serialize() !== savedSnapshot.value);
    const canUndo = computed(() => past.value.length > 0);
    const canRedo = computed(() => future.value.length > 0);

    const selectedDesk = computed(() => desks.value.find((desk) => desk.key === selectedKey.value) ?? null);
    const selectedElement = computed(() => elements.value.find((element) => element.id === selectedKey.value) ?? null);

    /** Client-side checks run before save; the server repeats them. */
    const issues = computed(() => {
        const result: Record<string, string[]> = {};
        const seen = new Map<string, string>();

        for (const desk of desks.value) {
            const label = desk.label.trim().toLowerCase();
            const problems: string[] = [];

            if (label === '') {
                problems.push('Every desk needs a label.');
            } else if (seen.has(label)) {
                problems.push(`The label "${desk.label}" is already used on this floor.`);
                const firstKey = seen.get(label) as string;
                result[firstKey] = [...(result[firstKey] ?? []), `The label "${desk.label}" is used more than once.`];
            } else {
                seen.set(label, desk.key);
            }

            if (problems.length > 0) {
                result[desk.key] = [...(result[desk.key] ?? []), ...problems];
            }
        }

        return result;
    });

    function load(floor: Floor): void {
        width.value = floor.width;
        height.value = floor.height;
        elements.value = clone(floor.layout.elements ?? []);
        desks.value = clone(floor.desks ?? []).map((desk) => ({ ...desk, key: `desk-${desk.id}` }));
        selectedKey.value = null;
        tool.value = 'select';
        past.value = [];
        future.value = [];
        savedSnapshot.value = serialize();
    }

    /** Call before a change so it can be undone. */
    function checkpoint(): void {
        past.value.push(serialize());

        if (past.value.length > HISTORY_LIMIT) {
            past.value.shift();
        }

        future.value = [];
    }

    function undo(): void {
        const previous = past.value.pop();

        if (previous === undefined) {
            return;
        }

        future.value.push(serialize());
        restore(previous);
    }

    function redo(): void {
        const next = future.value.pop();

        if (next === undefined) {
            return;
        }

        past.value.push(serialize());
        restore(next);
    }

    function snap(value: number): number {
        return snapToGrid.value ? Math.round(value / GRID_SIZE) * GRID_SIZE : Math.round(value);
    }

    function nextDeskLabel(type: DeskType): string {
        const prefix = type === 'desk' ? 'D-' : 'Room ';
        const used = new Set(desks.value.map((desk) => desk.label.trim().toLowerCase()));
        let number = desks.value.filter((desk) => desk.type === type).length + 1;

        while (used.has(`${prefix}${number}`.toLowerCase())) {
            number += 1;
        }

        return `${prefix}${number}`;
    }

    function addDesk(type: DeskType, centerX: number, centerY: number): void {
        checkpoint();
        const size = DESK_SIZES[type];
        const desk: EditorDesk = {
            key: newKey('new-desk'),
            id: null,
            label: nextDeskLabel(type),
            type,
            x: snap(centerX - size.width / 2),
            y: snap(centerY - size.height / 2),
            width: size.width,
            height: size.height,
            rotation: 0,
            is_bookable: true,
            assigned_user_id: null,
            add_ons: [],
        };
        desks.value.push(desk);
        selectedKey.value = desk.key;
    }

    function addRoom(x: number, y: number, roomWidth: number, roomHeight: number): void {
        checkpoint();
        const room: RoomElement = { id: newKey('room'), type: 'room', x, y, width: roomWidth, height: roomHeight, rotation: 0, label: '' };
        elements.value.push(room);
        selectedKey.value = room.id;
    }

    function addLabel(x: number, y: number): void {
        checkpoint();
        const label: LabelElement = { id: newKey('label'), type: 'label', x, y, rotation: 0, text: 'Label', font_size: DEFAULT_LABEL_SIZE };
        elements.value.push(label);
        selectedKey.value = label.id;
    }

    function addWall(points: number[]): void {
        checkpoint();
        const wall: WallElement = { id: newKey('wall'), type: 'wall', points: [...points], thickness: DEFAULT_WALL_THICKNESS };
        elements.value.push(wall);
        selectedKey.value = wall.id;
    }

    function updateDesk(key: string, patch: Partial<Omit<EditorDesk, 'key' | 'id'>>): void {
        const desk = desks.value.find((candidate) => candidate.key === key);

        if (desk) {
            Object.assign(desk, patch);
        }
    }

    function updateElement(id: string, patch: Partial<WallElement> | Partial<RoomElement> | Partial<LabelElement>): void {
        const element = elements.value.find((candidate) => candidate.id === id);

        if (element) {
            Object.assign(element, patch);
        }
    }

    function removeSelected(): void {
        const key = selectedKey.value;

        if (key === null) {
            return;
        }

        checkpoint();
        desks.value = desks.value.filter((desk) => desk.key !== key);
        elements.value = elements.value.filter((element) => element.id !== key);
        selectedKey.value = null;
    }

    function duplicateSelected(): void {
        const offset = GRID_SIZE * 2;

        if (selectedDesk.value) {
            checkpoint();
            const source = selectedDesk.value;
            const copy: EditorDesk = {
                ...clone(source),
                key: newKey('new-desk'),
                id: null,
                label: nextDeskLabel(source.type),
                x: source.x + offset,
                y: source.y + offset,
            };
            desks.value.push(copy);
            selectedKey.value = copy.key;

            return;
        }

        if (selectedElement.value) {
            checkpoint();
            const copy = clone(selectedElement.value);
            copy.id = newKey(copy.type);

            if (copy.type === 'wall') {
                copy.points = copy.points.map((value) => value + offset);
            } else {
                copy.x += offset;
                copy.y += offset;
            }

            elements.value.push(copy);
            selectedKey.value = copy.id;
        }
    }

    function nudgeSelected(dx: number, dy: number): void {
        if (selectedDesk.value) {
            checkpoint();
            selectedDesk.value.x += dx;
            selectedDesk.value.y += dy;
        } else if (selectedElement.value) {
            checkpoint();
            const element = selectedElement.value;

            if (element.type === 'wall') {
                element.points = element.points.map((value, index) => value + (index % 2 === 0 ? dx : dy));
            } else {
                element.x += dx;
                element.y += dy;
            }
        }
    }

    function setCanvasSize(newWidth: number, newHeight: number): void {
        width.value = newWidth;
        height.value = newHeight;
    }

    function toPayload(): FloorPlanPayload {
        return {
            width: width.value,
            height: height.value,
            layout: { elements: clone(elements.value) },
            desks: clone(desks.value).map((desk) => {
                const { key: _key, ...rest } = desk;
                void _key;

                return rest;
            }),
        };
    }

    return {
        width,
        height,
        elements,
        desks,
        selectedKey,
        tool,
        snapToGrid,
        isDirty,
        canUndo,
        canRedo,
        selectedDesk,
        selectedElement,
        issues,
        load,
        checkpoint,
        undo,
        redo,
        snap,
        addDesk,
        addRoom,
        addLabel,
        addWall,
        updateDesk,
        updateElement,
        removeSelected,
        duplicateSelected,
        nudgeSelected,
        setCanvasSize,
        toPayload,
    };
});
