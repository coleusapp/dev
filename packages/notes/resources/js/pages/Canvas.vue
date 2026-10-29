<script setup lang="ts">
import CanvasCard from '@/components/notes/CanvasCard.vue';
import NotePicker from '@/components/notes/NotePicker.vue';
import SaveStatus from '@/components/notes/SaveStatus.vue';
import { useAttachmentUpload, type UploadedAttachment } from '@/composables/useAttachmentUpload';
import { useCanvasSave } from '@/composables/useCanvasSave';
import NotesLayout from '@/layouts/NotesLayout.vue';
import {
    anchor,
    arrowHead,
    center,
    colorCss,
    curve,
    drawOrder,
    edgeGeometry,
    fitView,
    GRID,
    insideGroup,
    MIN_SIZE,
    newId,
    placeNode,
    PRESET_COLORS,
    removeNodes,
    sideFacing,
    snap,
    toWorld,
    zoomAt,
    type CanvasCards,
    type CanvasData,
    type CanvasEnd,
    type CanvasNode,
    type CanvasSide,
    type Point,
    type Viewport,
} from '@/lib/canvas';
import { jsonHeaders } from '@/lib/http';
import { Head, router } from '@inertiajs/vue3';
import { useEventListener, useResizeObserver } from '@vueuse/core';
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps<{
    canvas: { id: number; name: string; data: CanvasData };
    cards: CanvasCards;
}>();

/**
 * A canvas (O10.1): cards on an endless board, joined by arrows.
 *
 * Drag the background (or scroll) to pan, pinch or Ctrl/⌘-scroll to zoom.
 * Double-click the background for a text card, a card to edit or open
 * it. Drag a card to move it (a group takes the cards inside it along),
 * its corner to resize it, a dot on its side to connect it to another
 * card. Delete removes what's selected. Changes save as you go.
 */
const data = ref<CanvasData>(structuredClone(props.canvas.data));
const cards = ref<CanvasCards>(structuredClone(props.cards));
const name = ref(props.canvas.name);
const board = ref<HTMLElement | null>(null);
const viewport = ref<Viewport>({ x: 0, y: 0, zoom: 1 });
const selected = ref(new Set<string>());
const selectedEdge = ref<string | null>(null);
const editingNode = ref<string | null>(null);
const editingEdge = ref<string | null>(null);
const pickingNote = ref(false);
const addingLink = ref(false);
const linkUrl = ref('');
const linkError = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const dropPoint = ref<Point | null>(null);

const { status, error, flush } = useCanvasSave(props.canvas.id, data, (fresh) => {
    cards.value = { notes: { ...cards.value.notes, ...fresh.notes }, attachments: { ...cards.value.attachments, ...fresh.attachments } };
});

const ordered = computed(() => drawOrder(data.value.nodes));
const byId = computed(() => new Map(data.value.nodes.map((node) => [node.id, node])));
const selectedNodes = computed(() => data.value.nodes.filter((node) => selected.value.has(node.id)));
const theEdge = computed(() => data.value.edges.find((edge) => edge.id === selectedEdge.value) ?? null);

const edges = computed(() =>
    data.value.edges.flatMap((edge) => {
        const from = byId.value.get(edge.fromNode);
        const to = byId.value.get(edge.toNode);

        if (!from || !to) {
            return [];
        }

        const geometry = edgeGeometry(from, to, edge.fromSide, edge.toSide);

        return [{ edge, geometry, color: colorCss(edge.color) ?? 'var(--ui-border-accented)' }];
    }),
);

const rect = (): DOMRect | null => board.value?.getBoundingClientRect() ?? null;
const screenPoint = (event: { clientX: number; clientY: number }): Point => {
    const box = rect();

    return { x: event.clientX - (box?.left ?? 0), y: event.clientY - (box?.top ?? 0) };
};
const worldPoint = (event: { clientX: number; clientY: number }): Point => toWorld(viewport.value, screenPoint(event));
const viewCenter = (): Point => {
    const box = rect();

    return toWorld(viewport.value, { x: (box?.width ?? 800) / 2, y: (box?.height ?? 600) / 2 });
};

const fit = (): void => {
    const box = rect();

    if (box) {
        viewport.value = fitView(data.value.nodes, box.width, box.height);
    }
};

onMounted(fit);

let sized = false;
useResizeObserver(board, () => {
    // The first real size (after layout) decides the starting view.
    if (!sized && (rect()?.width ?? 0) > 0) {
        sized = true;
        fit();
    }
});

const zoomBy = (factor: number): void => {
    const box = rect();
    viewport.value = zoomAt(viewport.value, { x: (box?.width ?? 0) / 2, y: (box?.height ?? 0) / 2 }, factor);
};

const onWheel = (event: WheelEvent): void => {
    event.preventDefault();

    if (event.ctrlKey || event.metaKey) {
        viewport.value = zoomAt(viewport.value, screenPoint(event), Math.exp(-event.deltaY * 0.01));
    } else {
        viewport.value = { ...viewport.value, x: viewport.value.x - event.deltaX, y: viewport.value.y - event.deltaY };
    }
};

type Drag =
    | { kind: 'pan'; start: Point; from: Viewport }
    | { kind: 'move'; start: Point; origins: Map<string, Point>; moved: boolean }
    | { kind: 'resize'; id: string; start: Point; origin: { width: number; height: number } }
    | { kind: 'connect'; from: string; side: CanvasSide; to: Point };

const drag = ref<Drag | null>(null);

const clearSelection = (): void => {
    selected.value = new Set();
    selectedEdge.value = null;
};

const onBoardDown = (event: PointerEvent): void => {
    if (event.button !== 0) {
        return;
    }

    board.value?.focus();
    editingNode.value = null;
    editingEdge.value = null;
    clearSelection();
    drag.value = { kind: 'pan', start: screenPoint(event), from: { ...viewport.value } };
};

const onNodeDown = (event: PointerEvent, node: CanvasNode): void => {
    if (event.button !== 0 || editingNode.value === node.id) {
        return;
    }

    board.value?.focus();
    selectedEdge.value = null;

    if (event.shiftKey) {
        const next = new Set(selected.value);
        if (next.has(node.id)) {
            next.delete(node.id);
        } else {
            next.add(node.id);
        }
        selected.value = next;
    } else if (!selected.value.has(node.id)) {
        selected.value = new Set([node.id]);
    }

    const moving = new Set(selected.value);

    for (const each of selectedNodes.value) {
        if (each.type === 'group') {
            insideGroup(each, data.value.nodes).forEach((id) => moving.add(id));
        }
    }

    drag.value = {
        kind: 'move',
        start: worldPoint(event),
        origins: new Map(data.value.nodes.filter((each) => moving.has(each.id)).map((each) => [each.id, { x: each.x, y: each.y }])),
        moved: false,
    };
};

const onResizeDown = (event: PointerEvent, node: CanvasNode): void => {
    drag.value = { kind: 'resize', id: node.id, start: worldPoint(event), origin: { width: node.width, height: node.height } };
};

const onConnectDown = (event: PointerEvent, node: CanvasNode, side: CanvasSide): void => {
    drag.value = { kind: 'connect', from: node.id, side, to: worldPoint(event) };
};

const updateNode = (id: string, change: Partial<CanvasNode>): void => {
    data.value.nodes = data.value.nodes.map((node) => (node.id === id ? ({ ...node, ...change } as CanvasNode) : node));
};

useEventListener(window, 'pointermove', (event: PointerEvent) => {
    const current = drag.value;

    if (!current) {
        return;
    }

    if (current.kind === 'pan') {
        const point = screenPoint(event);
        viewport.value = { ...current.from, x: current.from.x + point.x - current.start.x, y: current.from.y + point.y - current.start.y };
    } else if (current.kind === 'move') {
        const point = worldPoint(event);
        const dx = point.x - current.start.x;
        const dy = point.y - current.start.y;
        current.moved ||= Math.abs(dx) + Math.abs(dy) > 2 / viewport.value.zoom;

        if (current.moved) {
            data.value.nodes = data.value.nodes.map((node) => {
                const origin = current.origins.get(node.id);

                return origin ? { ...node, x: origin.x + dx, y: origin.y + dy } : node;
            });
        }
    } else if (current.kind === 'resize') {
        const point = worldPoint(event);
        updateNode(current.id, {
            width: Math.max(MIN_SIZE, current.origin.width + point.x - current.start.x),
            height: Math.max(MIN_SIZE, current.origin.height + point.y - current.start.y),
        });
    } else {
        drag.value = { ...current, to: worldPoint(event) };
    }
});

useEventListener(window, 'pointerup', (event: PointerEvent) => {
    const current = drag.value;
    drag.value = null;

    if (!current) {
        return;
    }

    if (current.kind === 'move' && current.moved) {
        data.value.nodes = data.value.nodes.map((node) => (current.origins.has(node.id) ? { ...node, x: snap(node.x), y: snap(node.y) } : node));
    } else if (current.kind === 'resize') {
        const node = byId.value.get(current.id);

        if (node) {
            updateNode(node.id, { width: Math.max(MIN_SIZE, snap(node.width)), height: Math.max(MIN_SIZE, snap(node.height)) });
        }
    } else if (current.kind === 'connect') {
        const target = (document.elementFromPoint(event.clientX, event.clientY) as HTMLElement | null)?.closest<HTMLElement>('[data-node-id]');
        const to = target ? byId.value.get(target.dataset.nodeId ?? '') : undefined;

        if (to && to.id !== current.from) {
            const edge = { id: newId(), fromNode: current.from, fromSide: current.side, toNode: to.id, toSide: sideFacing(to, current.to) };
            data.value.edges = [...data.value.edges, edge];
            selected.value = new Set();
            selectedEdge.value = edge.id;
        }
    }
});

const addNode = (node: Omit<CanvasNode, 'id' | 'x' | 'y'> & { width: number; height: number }, at: Point = viewCenter()): CanvasNode => {
    const place = placeNode(data.value.nodes, at, node.width, node.height);
    const created = { ...node, ...place, id: newId() } as CanvasNode;
    data.value.nodes = [...data.value.nodes, created];
    selected.value = new Set([created.id]);
    selectedEdge.value = null;

    return created;
};

const addText = (at?: Point): void => {
    const node = addNode({ type: 'text', text: '', width: 260, height: 120 }, at);
    editingNode.value = node.id;
};

const addGroup = (): void => {
    const covering = selectedNodes.value;
    const box = covering.length
        ? {
              x: Math.min(...covering.map((node) => node.x)) - GRID * 4,
              y: Math.min(...covering.map((node) => node.y)) - GRID * 4,
              right: Math.max(...covering.map((node) => node.x + node.width)) + GRID * 4,
              bottom: Math.max(...covering.map((node) => node.y + node.height)) + GRID * 4,
          }
        : null;

    if (box) {
        const group = {
            id: newId(),
            type: 'group' as const,
            label: 'Group',
            x: box.x,
            y: box.y,
            width: box.right - box.x,
            height: box.bottom - box.y,
        };
        data.value.nodes = [...data.value.nodes, group];
        selected.value = new Set([group.id]);
    } else {
        addNode({ type: 'group', label: 'Group', width: 600, height: 400 });
    }
};

const addNote = (id: number): void => {
    addNode({ type: 'file', file: '', note: id, width: 320, height: 220 });
};

const addLink = (): void => {
    const url = linkUrl.value.trim();

    if (!/^https?:\/\/\S+$/i.test(url)) {
        linkError.value = 'Use a web address starting with http:// or https://.';

        return;
    }

    addNode({ type: 'link', url, width: 360, height: 240 });
    addingLink.value = false;
};

const openLinkModal = (): void => {
    linkUrl.value = '';
    linkError.value = null;
    addingLink.value = true;
};

const {
    queue,
    upload,
    errors: uploadErrors,
    dismiss,
} = useAttachmentUpload((attachment: UploadedAttachment) => {
    cards.value = {
        ...cards.value,
        attachments: { ...cards.value.attachments, [attachment.uuid]: { name: attachment.name, kind: attachment.kind, url: attachment.url } },
    };
    const image = attachment.kind === 'image';
    addNode(
        { type: 'file', file: attachment.name, attachment: attachment.uuid, width: image ? 320 : 280, height: image ? 240 : 60 },
        dropPoint.value ?? undefined,
    );
});

const pickFiles = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    dropPoint.value = null;
    void upload(Array.from(input.files ?? []));
    input.value = '';
};

const onDrop = (event: DragEvent): void => {
    const files = Array.from(event.dataTransfer?.files ?? []);

    if (files.length) {
        dropPoint.value = worldPoint(event);
        void upload(files);
    }
};

const activate = async (node: CanvasNode): Promise<void> => {
    if (node.type === 'text') {
        editingNode.value = node.id;
    } else if (node.type === 'group') {
        editingNode.value = node.id;
        await nextTick();
        document.querySelector<HTMLInputElement>(`[data-group-label-input="${node.id}"]`)?.focus();
    } else if (node.type === 'file' && node.note) {
        router.visit(route('notes.show', { note: node.note, ...(node.subpath ? { heading: node.subpath.slice(1) } : {}) }));
    } else if (node.type === 'file' && node.attachment && cards.value.attachments[node.attachment]) {
        window.open(cards.value.attachments[node.attachment].url, '_blank', 'noopener');
    } else if (node.type === 'link') {
        window.open(node.url, '_blank', 'noopener,noreferrer');
    }
};

const onBoardDoubleClick = (event: MouseEvent): void => {
    addText(worldPoint(event));
};

const removeSelected = (): void => {
    if (selectedEdge.value) {
        data.value.edges = data.value.edges.filter((edge) => edge.id !== selectedEdge.value);
        selectedEdge.value = null;
    } else if (selected.value.size) {
        data.value = removeNodes(data.value, selected.value);
        selected.value = new Set();
    }
};

const setColor = (color: string | null): void => {
    const apply = <T extends { color?: string }>(item: T): T => {
        const { color: _old, ...rest } = item;
        void _old;

        return (color ? { ...rest, color } : rest) as T;
    };

    if (selectedEdge.value) {
        data.value.edges = data.value.edges.map((edge) => (edge.id === selectedEdge.value ? apply(edge) : edge));
    } else {
        data.value.nodes = data.value.nodes.map((node) => (selected.value.has(node.id) ? apply(node) : node));
    }
};

const setArrows = (fromEnd: CanvasEnd, toEnd: CanvasEnd): void => {
    data.value.edges = data.value.edges.map((edge) => (edge.id === selectedEdge.value ? { ...edge, fromEnd, toEnd } : edge));
};

const setEdgeLabel = (id: string, label: string): void => {
    data.value.edges = data.value.edges.map((edge) => {
        if (edge.id !== id) {
            return edge;
        }

        const { label: _old, ...rest } = edge;
        void _old;

        return label.trim() ? { ...rest, label: label.trim() } : rest;
    });
};

const startEdgeLabel = async (id: string): Promise<void> => {
    selectedEdge.value = id;
    selected.value = new Set();
    editingEdge.value = id;
    await nextTick();
    document.querySelector<HTMLInputElement>(`[data-edge-label-input="${id}"]`)?.focus();
};

const onKey = (event: KeyboardEvent): void => {
    if (editingNode.value || editingEdge.value || (event.target as HTMLElement).closest('input, textarea')) {
        return;
    }

    if (event.key === 'Delete' || event.key === 'Backspace') {
        event.preventDefault();
        removeSelected();
    } else if (event.key === 'Escape') {
        clearSelection();
    } else if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'a') {
        event.preventDefault();
        selected.value = new Set(data.value.nodes.map((node) => node.id));
    } else if (event.key === '+' || event.key === '=') {
        zoomBy(1.2);
    } else if (event.key === '-') {
        zoomBy(1 / 1.2);
    } else if (event.key === '0') {
        fit();
    }
};

const rename = async (): Promise<void> => {
    const next = name.value.trim();

    if (!next || next === props.canvas.name) {
        name.value = props.canvas.name;

        return;
    }

    await flush();
    const response = await fetch(route('notes.canvases.update', { canvas: props.canvas.id }), {
        method: 'PUT',
        headers: jsonHeaders(),
        credentials: 'same-origin',
        body: JSON.stringify({ name: next }),
    });

    if (!response.ok) {
        name.value = props.canvas.name;
    }
};

const connecting = computed(() => {
    const current = drag.value;

    if (current?.kind !== 'connect') {
        return null;
    }

    const from = byId.value.get(current.from);

    if (!from) {
        return null;
    }

    return curve(anchor(from, current.side), current.side, current.to, sideFacing({ ...current.to, width: 0, height: 0 }, center(from))).path;
});

const sides: CanvasSide[] = ['top', 'right', 'bottom', 'left'];

const handleStyle = (side: CanvasSide): Record<string, string> =>
    ({
        top: { left: '50%', top: '0' },
        right: { left: '100%', top: '50%' },
        bottom: { left: '50%', top: '100%' },
        left: { left: '0', top: '50%' },
    })[side];

const nodeStyle = (node: CanvasNode): Record<string, string> => {
    const color = colorCss(node.color);

    return {
        left: `${node.x}px`,
        top: `${node.y}px`,
        width: `${node.width}px`,
        height: `${node.height}px`,
        ...(color ? { '--card-color': color } : {}),
    };
};

const gridStyle = computed(() => {
    const size = GRID * 2 * viewport.value.zoom;

    return {
        backgroundSize: `${size}px ${size}px`,
        backgroundPosition: `${viewport.value.x}px ${viewport.value.y}px`,
    };
});

const colorItems = computed(() => [
    { label: 'No colour', value: null, css: null },
    ...Object.entries(PRESET_COLORS).map(([value, preset]) => ({ label: preset.label, value, css: preset.css })),
]);

const hasSelection = computed(() => selected.value.size > 0 || selectedEdge.value !== null);
</script>

<template>
    <Head :title="name" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-canvas" :ui="{ body: 'p-0 sm:p-0' }">
                <template #header>
                    <UiDashboardNavbar>
                        <template #leading>
                            <UiButton
                                icon="i-lucide-chevron-left"
                                color="neutral"
                                variant="ghost"
                                aria-label="All canvases"
                                :to="route('notes.canvases.index')"
                            />
                        </template>
                        <template #title>
                            <input
                                v-model="name"
                                class="hover:bg-elevated focus:bg-elevated w-56 rounded px-1 font-semibold outline-none"
                                aria-label="Canvas name"
                                maxlength="100"
                                @change="rename"
                                @keydown.enter="($event.target as HTMLInputElement).blur()"
                            />
                        </template>
                        <template #right>
                            <SaveStatus :status="status" :error="error" @retry="flush" />
                            <UiTooltip text="Download as a .canvas file (opens in Obsidian)">
                                <UiButton
                                    icon="i-lucide-download"
                                    size="sm"
                                    color="neutral"
                                    variant="ghost"
                                    :href="route('notes.canvases.export', { canvas: canvas.id })"
                                    external
                                    download
                                    @click="flush"
                                    >Export</UiButton
                                >
                            </UiTooltip>
                        </template>
                    </UiDashboardNavbar>

                    <UiDashboardToolbar>
                        <div class="flex flex-wrap items-center gap-1">
                            <UiButton icon="i-lucide-type" size="sm" color="neutral" variant="ghost" @click="addText()">Text</UiButton>
                            <UiButton icon="i-lucide-file-text" size="sm" color="neutral" variant="ghost" @click="pickingNote = true">Note</UiButton>
                            <UiButton icon="i-lucide-image" size="sm" color="neutral" variant="ghost" @click="fileInput?.click()">File</UiButton>
                            <UiButton icon="i-lucide-link" size="sm" color="neutral" variant="ghost" @click="openLinkModal">Link</UiButton>
                            <UiButton icon="i-lucide-group" size="sm" color="neutral" variant="ghost" @click="addGroup">Group</UiButton>
                            <input ref="fileInput" type="file" multiple class="hidden" @change="pickFiles" />
                        </div>
                        <div v-if="hasSelection" class="ms-2 flex items-center gap-1" role="group" aria-label="Selection">
                            <button
                                v-for="item in colorItems"
                                :key="item.label"
                                type="button"
                                class="border-default size-5 rounded-full border"
                                :style="item.css ? { background: item.css } : {}"
                                :class="{ 'bg-default': !item.css }"
                                :aria-label="`Colour: ${item.label}`"
                                :title="item.label"
                                @click="setColor(item.value)"
                            />
                            <template v-if="theEdge">
                                <UiSelect
                                    :model-value="`${theEdge.fromEnd ?? 'none'}-${theEdge.toEnd ?? 'arrow'}`"
                                    :items="[
                                        { label: 'One way →', value: 'none-arrow' },
                                        { label: 'Both ways ↔', value: 'arrow-arrow' },
                                        { label: 'No arrows —', value: 'none-none' },
                                    ]"
                                    size="xs"
                                    class="w-32"
                                    aria-label="Arrows"
                                    @update:model-value="
                                        (value: string) => setArrows(value.split('-')[0] as CanvasEnd, value.split('-')[1] as CanvasEnd)
                                    "
                                />
                                <UiButton
                                    size="xs"
                                    color="neutral"
                                    variant="ghost"
                                    icon="i-lucide-text-cursor-input"
                                    @click="startEdgeLabel(theEdge.id)"
                                    >Label</UiButton
                                >
                            </template>
                            <UiButton
                                size="xs"
                                color="error"
                                variant="ghost"
                                icon="i-lucide-trash-2"
                                aria-label="Delete selection"
                                @click="removeSelected"
                            />
                        </div>
                        <div class="ms-auto flex items-center gap-1">
                            <UiButton
                                icon="i-lucide-minus"
                                size="xs"
                                color="neutral"
                                variant="ghost"
                                aria-label="Zoom out"
                                @click="zoomBy(1 / 1.2)"
                            />
                            <span class="text-dimmed w-12 text-center text-xs tabular-nums">{{ Math.round(viewport.zoom * 100) }}%</span>
                            <UiButton icon="i-lucide-plus" size="xs" color="neutral" variant="ghost" aria-label="Zoom in" @click="zoomBy(1.2)" />
                            <UiButton icon="i-lucide-scan" size="xs" color="neutral" variant="ghost" aria-label="Show everything" @click="fit" />
                        </div>
                    </UiDashboardToolbar>
                </template>

                <template #body>
                    <div
                        ref="board"
                        class="canvas-board bg-default relative h-full touch-none overflow-hidden outline-none select-none"
                        :style="gridStyle"
                        tabindex="0"
                        role="application"
                        aria-label="Canvas board"
                        aria-roledescription="canvas"
                        @pointerdown="onBoardDown"
                        @dblclick="onBoardDoubleClick"
                        @wheel="onWheel"
                        @keydown="onKey"
                        @dragover.prevent
                        @drop.prevent="onDrop"
                    >
                        <div
                            class="absolute top-0 left-0 origin-top-left"
                            :style="{ transform: `translate(${viewport.x}px, ${viewport.y}px) scale(${viewport.zoom})` }"
                        >
                            <svg class="pointer-events-none absolute top-0 left-0 overflow-visible" width="1" height="1" aria-hidden="true">
                                <g v-for="{ edge, geometry, color } in edges" :key="edge.id">
                                    <path
                                        :d="geometry.path"
                                        fill="none"
                                        stroke="transparent"
                                        stroke-width="14"
                                        class="pointer-events-auto cursor-pointer"
                                        @pointerdown.stop="((selectedEdge = edge.id), (selected = new Set()), board?.focus())"
                                        @dblclick.stop="startEdgeLabel(edge.id)"
                                    />
                                    <path
                                        :d="geometry.path"
                                        fill="none"
                                        :stroke="selectedEdge === edge.id ? 'var(--ui-primary)' : color"
                                        :stroke-width="selectedEdge === edge.id ? 3 : 2"
                                    />
                                    <path
                                        v-if="(edge.toEnd ?? 'arrow') === 'arrow'"
                                        :d="arrowHead(geometry.end, geometry.endSide)"
                                        :fill="selectedEdge === edge.id ? 'var(--ui-primary)' : color"
                                    />
                                    <path
                                        v-if="edge.fromEnd === 'arrow'"
                                        :d="arrowHead(geometry.start, geometry.startSide)"
                                        :fill="selectedEdge === edge.id ? 'var(--ui-primary)' : color"
                                    />
                                </g>
                                <path
                                    v-if="connecting"
                                    :d="connecting"
                                    fill="none"
                                    stroke="var(--ui-primary)"
                                    stroke-width="2"
                                    stroke-dasharray="6 4"
                                />
                            </svg>

                            <template v-for="{ edge, geometry } in edges" :key="`label-${edge.id}`">
                                <input
                                    v-if="editingEdge === edge.id"
                                    :data-edge-label-input="edge.id"
                                    :value="edge.label ?? ''"
                                    class="bg-default border-default absolute w-40 -translate-x-1/2 -translate-y-1/2 rounded border px-1 text-center text-xs"
                                    :style="{ left: `${geometry.middle.x}px`, top: `${geometry.middle.y}px` }"
                                    aria-label="Connection label"
                                    @pointerdown.stop
                                    @change="setEdgeLabel(edge.id, ($event.target as HTMLInputElement).value)"
                                    @blur="editingEdge = null"
                                    @keydown.enter="($event.target as HTMLInputElement).blur()"
                                    @keydown.esc="editingEdge = null"
                                />
                                <span
                                    v-else-if="edge.label"
                                    class="bg-default text-muted absolute -translate-x-1/2 -translate-y-1/2 rounded px-1 text-xs whitespace-nowrap"
                                    :style="{ left: `${geometry.middle.x}px`, top: `${geometry.middle.y}px` }"
                                    @pointerdown.stop="((selectedEdge = edge.id), (selected = new Set()))"
                                    @dblclick.stop="startEdgeLabel(edge.id)"
                                    >{{ edge.label }}</span
                                >
                            </template>

                            <div
                                v-for="node in ordered"
                                :key="node.id"
                                :data-node-id="node.id"
                                class="canvas-node absolute"
                                :class="node.type === 'group' ? 'canvas-group' : 'canvas-card'"
                                :style="nodeStyle(node)"
                                @pointerdown.stop="onNodeDown($event, node)"
                                @dblclick.stop="activate(node)"
                            >
                                <template v-if="node.type === 'group'">
                                    <input
                                        v-if="editingNode === node.id"
                                        :value="node.label ?? ''"
                                        class="bg-default border-default absolute -top-7 left-0 w-48 rounded border px-1 text-sm"
                                        :data-group-label-input="node.id"
                                        aria-label="Group name"
                                        @pointerdown.stop
                                        @change="updateNode(node.id, { label: ($event.target as HTMLInputElement).value.trim() || undefined })"
                                        @blur="editingNode = null"
                                        @keydown.enter="($event.target as HTMLInputElement).blur()"
                                    />
                                    <span v-else-if="node.label" class="text-muted absolute -top-6 left-0 truncate text-sm font-medium">{{
                                        node.label
                                    }}</span>
                                    <div class="canvas-frame h-full rounded-lg border-2" :class="{ 'ring-primary ring-2': selected.has(node.id) }" />
                                </template>
                                <div
                                    v-else
                                    class="canvas-frame bg-default h-full overflow-hidden rounded-md border shadow-sm"
                                    :class="{ 'ring-primary ring-2': selected.has(node.id) }"
                                >
                                    <CanvasCard
                                        :node="node"
                                        :cards="cards"
                                        :editing="editingNode === node.id"
                                        @text="(text) => updateNode(node.id, { text })"
                                        @done="editingNode = null"
                                    />
                                </div>

                                <template v-if="selected.size === 1 && selected.has(node.id) && editingNode !== node.id">
                                    <button
                                        v-for="side in sides"
                                        :key="side"
                                        type="button"
                                        class="bg-primary border-default absolute size-3 -translate-x-1/2 -translate-y-1/2 cursor-crosshair rounded-full border-2"
                                        :style="handleStyle(side)"
                                        :aria-label="`Connect from the ${side}`"
                                        @pointerdown.stop="onConnectDown($event, node, side)"
                                    />
                                    <button
                                        type="button"
                                        class="border-primary bg-default absolute -right-1.5 -bottom-1.5 size-3 cursor-nwse-resize rounded-sm border-2"
                                        aria-label="Resize"
                                        @pointerdown.stop="onResizeDown($event, node)"
                                    />
                                </template>
                            </div>
                        </div>

                        <div
                            v-if="!data.nodes.length"
                            class="text-dimmed pointer-events-none absolute inset-0 flex items-center justify-center text-sm"
                        >
                            Double-click anywhere to add a card, or use the buttons above.
                        </div>

                        <div
                            v-if="queue.length || uploadErrors.length"
                            class="bg-default border-default absolute right-3 bottom-3 rounded-md border p-2 text-xs shadow"
                        >
                            <p v-for="pending in queue" :key="pending.id">
                                Uploading {{ pending.name }} {{ pending.total ? Math.round((pending.loaded / pending.total) * 100) : 0 }}%
                            </p>
                            <p v-for="message in uploadErrors" :key="message" class="text-error">{{ message }}</p>
                            <UiButton v-if="uploadErrors.length" size="xs" color="neutral" variant="ghost" @click="dismiss">Dismiss</UiButton>
                        </div>
                    </div>
                </template>
            </UiDashboardPanel>
        </template>

        <NotePicker v-model:open="pickingNote" title="Add a note" description="It shows as a card you can open." @choose="addNote" />

        <UiModal v-model:open="addingLink" title="Add a link" description="A web page, shown with its preview.">
            <template #body>
                <form class="flex flex-col gap-3" @submit.prevent="addLink">
                    <UiFormField label="Address" :error="linkError ?? undefined">
                        <UiInput v-model="linkUrl" type="url" placeholder="https://" class="w-full" autofocus />
                    </UiFormField>
                    <div class="flex justify-end gap-2">
                        <UiButton color="neutral" variant="ghost" @click="addingLink = false">Cancel</UiButton>
                        <UiButton type="submit">Add</UiButton>
                    </div>
                </form>
            </template>
        </UiModal>
    </NotesLayout>
</template>
