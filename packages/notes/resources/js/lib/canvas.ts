/**
 * Canvas (O10.1): JSON Canvas types as the server keeps them
 * (Canvas\JsonCanvas) and the board's geometry. World coordinates are the
 * saved x/y; the screen shows them through a Viewport (pan and zoom).
 */

export type CanvasSide = 'top' | 'right' | 'bottom' | 'left';
export type CanvasEnd = 'none' | 'arrow';

type NodeBase = { id: string; x: number; y: number; width: number; height: number; color?: string };

export type TextNode = NodeBase & { type: 'text'; text: string };
export type FileNode = NodeBase & { type: 'file'; file: string; note?: number; attachment?: string; subpath?: string };
export type LinkNode = NodeBase & { type: 'link'; url: string };
export type GroupNode = NodeBase & { type: 'group'; label?: string; background?: string; backgroundStyle?: 'cover' | 'ratio' | 'repeat' };
export type CanvasNode = TextNode | FileNode | LinkNode | GroupNode;

export type CanvasEdge = {
    id: string;
    fromNode: string;
    fromSide?: CanvasSide;
    fromEnd?: CanvasEnd;
    toNode: string;
    toSide?: CanvasSide;
    toEnd?: CanvasEnd;
    color?: string;
    label?: string;
};

export type CanvasData = { nodes: CanvasNode[]; edges: CanvasEdge[] };

export type CanvasCards = {
    notes: Record<number, { title: string; snippet: string; locked: boolean }>;
    attachments: Record<string, { name: string; kind: string; url: string }>;
};

export type Point = { x: number; y: number };
export type Rect = { x: number; y: number; width: number; height: number };

/**
 * screen = world × zoom + offset.
 */
export type Viewport = { x: number; y: number; zoom: number };

export const MIN_ZOOM = 0.1;
export const MAX_ZOOM = 4;
export const GRID = 10;
export const MIN_SIZE = 20;

/**
 * JSON Canvas's preset colours, "1"–"6".
 */
export const PRESET_COLORS: Record<string, { label: string; css: string }> = {
    '1': { label: 'Red', css: 'var(--color-red-500)' },
    '2': { label: 'Orange', css: 'var(--color-orange-500)' },
    '3': { label: 'Yellow', css: 'var(--color-yellow-500)' },
    '4': { label: 'Green', css: 'var(--color-green-500)' },
    '5': { label: 'Cyan', css: 'var(--color-cyan-500)' },
    '6': { label: 'Purple', css: 'var(--color-purple-500)' },
};

export const colorCss = (color: string | undefined): string | null =>
    color ? (PRESET_COLORS[color]?.css ?? (/^#[0-9a-f]{6}$/i.test(color) ? color : null)) : null;

export const clampZoom = (zoom: number): number => Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, zoom));

export const toWorld = (viewport: Viewport, screen: Point): Point => ({
    x: (screen.x - viewport.x) / viewport.zoom,
    y: (screen.y - viewport.y) / viewport.zoom,
});

/**
 * Zooms by a factor keeping the world point under `screen` where it is.
 */
export const zoomAt = (viewport: Viewport, screen: Point, factor: number): Viewport => {
    const zoom = clampZoom(viewport.zoom * factor);
    const world = toWorld(viewport, screen);

    return { x: screen.x - world.x * zoom, y: screen.y - world.y * zoom, zoom };
};

export const bounds = (rects: Rect[]): Rect | null => {
    if (!rects.length) {
        return null;
    }

    const left = Math.min(...rects.map((rect) => rect.x));
    const top = Math.min(...rects.map((rect) => rect.y));
    const right = Math.max(...rects.map((rect) => rect.x + rect.width));
    const bottom = Math.max(...rects.map((rect) => rect.y + rect.height));

    return { x: left, y: top, width: right - left, height: bottom - top };
};

/**
 * Shows every card in a screen of the given size, never zooming past 100%.
 */
export const fitView = (rects: Rect[], width: number, height: number, padding = 48): Viewport => {
    const box = bounds(rects);

    if (!box) {
        return { x: width / 2, y: height / 2, zoom: 1 };
    }

    const zoom = clampZoom(Math.min(1, (width - padding * 2) / Math.max(box.width, 1), (height - padding * 2) / Math.max(box.height, 1)));

    return { x: (width - box.width * zoom) / 2 - box.x * zoom, y: (height - box.height * zoom) / 2 - box.y * zoom, zoom };
};

export const snap = (value: number): number => Math.round(value / GRID) * GRID;

export const center = (rect: Rect): Point => ({ x: rect.x + rect.width / 2, y: rect.y + rect.height / 2 });

export const anchor = (rect: Rect, side: CanvasSide): Point => {
    switch (side) {
        case 'top':
            return { x: rect.x + rect.width / 2, y: rect.y };
        case 'bottom':
            return { x: rect.x + rect.width / 2, y: rect.y + rect.height };
        case 'left':
            return { x: rect.x, y: rect.y + rect.height / 2 };
        default:
            return { x: rect.x + rect.width, y: rect.y + rect.height / 2 };
    }
};

/**
 * The side of `rect` facing `point`, judged against the rect's shape.
 */
export const sideFacing = (rect: Rect, point: Point): CanvasSide => {
    const middle = center(rect);
    const dx = (point.x - middle.x) / Math.max(rect.width, 1);
    const dy = (point.y - middle.y) / Math.max(rect.height, 1);

    if (Math.abs(dx) >= Math.abs(dy)) {
        return dx >= 0 ? 'right' : 'left';
    }

    return dy >= 0 ? 'bottom' : 'top';
};

const round = (value: number): number => Math.round(value * 100) / 100;

const NORMALS: Record<CanvasSide, Point> = { top: { x: 0, y: -1 }, right: { x: 1, y: 0 }, bottom: { x: 0, y: 1 }, left: { x: -1, y: 0 } };

export type EdgeGeometry = { path: string; start: Point; end: Point; middle: Point; endSide: CanvasSide; startSide: CanvasSide };

/**
 * A curve from one card to another, leaving and entering square to the
 * chosen sides (or the facing sides when the edge names none).
 */
export const edgeGeometry = (from: Rect, to: Rect, fromSide?: CanvasSide, toSide?: CanvasSide): EdgeGeometry => {
    const startSide = fromSide ?? sideFacing(from, center(to));
    const endSide = toSide ?? sideFacing(to, center(from));

    return curve(anchor(from, startSide), startSide, anchor(to, endSide), endSide);
};

/**
 * A curve to a loose point (a connection being dragged).
 */
export const curve = (start: Point, startSide: CanvasSide, end: Point, endSide: CanvasSide): EdgeGeometry => {
    const reach = Math.max(40, Math.hypot(end.x - start.x, end.y - start.y) / 3);
    const c1 = { x: start.x + NORMALS[startSide].x * reach, y: start.y + NORMALS[startSide].y * reach };
    const c2 = { x: end.x + NORMALS[endSide].x * reach, y: end.y + NORMALS[endSide].y * reach };
    const at = (t: number, a: number, b: number, c: number, d: number): number =>
        (1 - t) ** 3 * a + 3 * (1 - t) ** 2 * t * b + 3 * (1 - t) * t ** 2 * c + t ** 3 * d;

    return {
        path: `M ${round(start.x)} ${round(start.y)} C ${round(c1.x)} ${round(c1.y)}, ${round(c2.x)} ${round(c2.y)}, ${round(end.x)} ${round(end.y)}`,
        start,
        end,
        middle: { x: at(0.5, start.x, c1.x, c2.x, end.x), y: at(0.5, start.y, c1.y, c2.y, end.y) },
        startSide,
        endSide,
    };
};

/**
 * An arrowhead at `tip`, pointing into the card through `side`.
 */
export const arrowHead = (tip: Point, side: CanvasSide, size = 10): string => {
    const normal = NORMALS[side];
    const back = { x: tip.x + normal.x * size, y: tip.y + normal.y * size };
    const across = { x: -normal.y * (size / 2), y: normal.x * (size / 2) };

    return `M ${tip.x} ${tip.y} L ${back.x + across.x} ${back.y + across.y} L ${back.x - across.x} ${back.y - across.y} Z`;
};

export const contains = (outer: Rect, inner: Rect): boolean =>
    inner.x >= outer.x && inner.y >= outer.y && inner.x + inner.width <= outer.x + outer.width && inner.y + inner.height <= outer.y + outer.height;

/**
 * Cards that move with a group: those lying wholly inside it, like Obsidian.
 */
export const insideGroup = (group: CanvasNode, nodes: CanvasNode[]): string[] =>
    nodes.filter((node) => node.id !== group.id && contains(group, node)).map((node) => node.id);

/**
 * Groups underneath (biggest first, so a small group sits on a big one),
 * then everything else in saved order.
 */
export const drawOrder = (nodes: CanvasNode[]): CanvasNode[] => [
    ...nodes.filter((node) => node.type === 'group').sort((a, b) => b.width * b.height - a.width * a.height),
    ...nodes.filter((node) => node.type !== 'group'),
];

export const newId = (): string => {
    const bytes = new Uint8Array(8);
    crypto.getRandomValues(bytes);

    return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
};

/**
 * Removes cards and every connection touching them.
 */
export const removeNodes = (data: CanvasData, ids: Set<string>): CanvasData => ({
    nodes: data.nodes.filter((node) => !ids.has(node.id)),
    edges: data.edges.filter((edge) => !ids.has(edge.fromNode) && !ids.has(edge.toNode)),
});

/**
 * Where a new card goes: centred on `at`, nudged right until it no longer
 * sits exactly on another card.
 */
export const placeNode = (nodes: CanvasNode[], at: Point, width: number, height: number): Rect => {
    let rect = { x: snap(at.x - width / 2), y: snap(at.y - height / 2), width, height };

    while (nodes.some((node) => node.x === rect.x && node.y === rect.y)) {
        rect = { ...rect, x: rect.x + GRID * 3, y: rect.y + GRID * 3 };
    }

    return rect;
};

/**
 * What a link card shows before (or without) a preview.
 */
export const linkHost = (url: string): string => {
    try {
        return new URL(url).host.replace(/^www\./, '');
    } catch {
        return url;
    }
};
