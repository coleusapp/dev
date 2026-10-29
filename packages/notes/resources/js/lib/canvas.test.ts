import {
    anchor,
    arrowHead,
    bounds,
    colorCss,
    drawOrder,
    edgeGeometry,
    fitView,
    insideGroup,
    linkHost,
    newId,
    placeNode,
    removeNodes,
    sideFacing,
    snap,
    toWorld,
    zoomAt,
    type CanvasData,
    type CanvasNode,
} from '@/lib/canvas';
import { describe, expect, it } from 'vitest';

const text = (id: string, x: number, y: number, width = 100, height = 50): CanvasNode => ({ id, type: 'text', x, y, width, height, text: id });

describe('viewport', () => {
    it('maps screen to world and zooms around the pointer', () => {
        const viewport = { x: 100, y: 50, zoom: 2 };

        expect(toWorld(viewport, { x: 300, y: 250 })).toEqual({ x: 100, y: 100 });

        const zoomed = zoomAt(viewport, { x: 300, y: 250 }, 0.5);
        expect(zoomed.zoom).toBe(1);
        expect(toWorld(zoomed, { x: 300, y: 250 })).toEqual({ x: 100, y: 100 });
        expect(zoomAt(viewport, { x: 0, y: 0 }, 100).zoom).toBe(4);
        expect(zoomAt(viewport, { x: 0, y: 0 }, 0.0001).zoom).toBe(0.1);
    });

    it('fits every card without zooming past 100%', () => {
        expect(fitView([], 800, 600)).toEqual({ x: 400, y: 300, zoom: 1 });
        expect(fitView([text('a', 0, 0, 100, 100)], 800, 600)).toEqual({ x: 350, y: 250, zoom: 1 });

        const wide = fitView([text('a', -1000, 0, 2000, 100)], 1096, 600);
        expect(wide.zoom).toBe(0.5);
        expect(wide.x).toBe(548);
        expect(bounds([text('a', 0, 0), text('b', 200, -20, 10, 10)])).toEqual({ x: 0, y: -20, width: 210, height: 70 });
    });
});

describe('edges', () => {
    const left = { x: 0, y: 0, width: 100, height: 50 };
    const right = { x: 300, y: 0, width: 100, height: 50 };

    it('anchors on sides and picks facing sides', () => {
        expect(anchor(left, 'right')).toEqual({ x: 100, y: 25 });
        expect(anchor(left, 'top')).toEqual({ x: 50, y: 0 });
        expect(sideFacing(left, { x: 50, y: 300 })).toBe('bottom');
        expect(sideFacing(left, { x: -300, y: 30 })).toBe('left');
    });

    it('curves between facing sides unless sides are given', () => {
        const auto = edgeGeometry(left, right);
        expect(auto.startSide).toBe('right');
        expect(auto.endSide).toBe('left');
        expect(auto.path).toBe('M 100 25 C 166.67 25, 233.33 25, 300 25');
        expect(auto.middle).toEqual({ x: 200, y: 25 });

        expect(edgeGeometry(left, right, 'bottom', 'bottom').path.startsWith('M 50 50 C 50 ')).toBe(true);
        expect(arrowHead({ x: 300, y: 25 }, 'left')).toBe('M 300 25 L 290 20 L 290 30 Z');
    });
});

describe('cards', () => {
    it('finds cards inside a group and draws groups underneath', () => {
        const group: CanvasNode = { id: 'g', type: 'group', x: 0, y: 0, width: 500, height: 500 };
        const small: CanvasNode = { id: 'h', type: 'group', x: 10, y: 10, width: 100, height: 100 };
        const nodes = [text('in', 20, 20), text('out', 450, 480), small, group];

        expect(insideGroup(group, nodes)).toEqual(['in', 'h']);
        expect(drawOrder(nodes).map((node) => node.id)).toEqual(['g', 'h', 'in', 'out']);
    });

    it('removes cards with their connections', () => {
        const data: CanvasData = {
            nodes: [text('a', 0, 0), text('b', 0, 0), text('c', 0, 0)],
            edges: [
                { id: 'e1', fromNode: 'a', toNode: 'b' },
                { id: 'e2', fromNode: 'b', toNode: 'c' },
                { id: 'e3', fromNode: 'c', toNode: 'a' },
            ],
        };

        expect(removeNodes(data, new Set(['b']))).toEqual({ nodes: [data.nodes[0], data.nodes[2]], edges: [data.edges[2]] });
    });

    it('places new cards on the grid without stacking them', () => {
        expect(placeNode([], { x: 104, y: 52 }, 200, 100)).toEqual({ x: 0, y: 0, width: 200, height: 100 });
        expect(placeNode([text('a', 0, 0)], { x: 100, y: 50 }, 200, 100)).toEqual({ x: 30, y: 30, width: 200, height: 100 });
        expect(snap(14)).toBe(10);
        expect(snap(-16)).toBe(-20);
    });

    it('has colours, ids and link hosts', () => {
        expect(colorCss('1')).toBe('var(--color-red-500)');
        expect(colorCss('#00ff00')).toBe('#00ff00');
        expect(colorCss('nope')).toBeNull();
        expect(colorCss(undefined)).toBeNull();
        expect(newId()).toMatch(/^[0-9a-f]{16}$/);
        expect(linkHost('https://www.example.com/a')).toBe('example.com');
        expect(linkHost('not a url')).toBe('not a url');
    });
});
