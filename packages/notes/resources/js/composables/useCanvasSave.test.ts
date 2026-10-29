// @vitest-environment jsdom
import { useCanvasSave } from '@/composables/useCanvasSave';
import type { CanvasCards, CanvasData } from '@/lib/canvas';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createApp, defineComponent, nextTick, ref } from 'vue';

vi.mock('@inertiajs/vue3', () => ({ router: { on: () => () => undefined } }));

const requests: { url: string; body: { data: CanvasData }; keepalive?: boolean }[] = [];
let respond: () => { ok: boolean; status: number; json: () => Promise<unknown> };

beforeEach(() => {
    vi.useFakeTimers();
    vi.stubGlobal('route', (name: string, params: { canvas: number }) => `/${name}/${params.canvas}`);
    respond = () => ({
        ok: true,
        status: 200,
        json: async () => ({ data: { name: 'Board', cards: { notes: { 7: { title: 'Trip', snippet: '', locked: false } }, attachments: {} } } }),
    });
    vi.stubGlobal(
        'fetch',
        vi.fn(async (url: string, init: { body: string; keepalive?: boolean }) => {
            requests.push({ url, body: JSON.parse(init.body), keepalive: init.keepalive });

            return respond();
        }),
    );
});

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    requests.length = 0;
});

const mount = () => {
    const data = ref<CanvasData>({ nodes: [], edges: [] });
    const saved: CanvasCards[] = [];
    let save!: ReturnType<typeof useCanvasSave>;
    createApp(
        defineComponent({
            setup() {
                save = useCanvasSave(3, data, (cards) => saved.push(cards), 100);

                return () => null;
            },
        }),
    ).mount(document.createElement('div'));

    return { data, saved, save };
};

describe('useCanvasSave', () => {
    it('saves the board after a pause and hands back the cards', async () => {
        const { data, saved, save } = mount();

        data.value.nodes.push({ id: 'a', type: 'file', file: '', note: 7, x: 0, y: 0, width: 100, height: 100 });
        await vi.advanceTimersByTimeAsync(50);
        expect(save.status.value).toBe('unsaved');
        await vi.advanceTimersByTimeAsync(60);

        expect(requests).toHaveLength(1);
        expect(requests[0].url).toBe('/notes.canvases.update/3');
        expect(requests[0].body.data.nodes[0].id).toBe('a');
        expect(saved[0].notes[7].title).toBe('Trip');
        expect(save.status.value).toBe('saved');
    });

    it('sends edits made during a save right after it', async () => {
        const { data, save } = mount();

        data.value.nodes.push({ id: 'a', type: 'text', text: 'one', x: 0, y: 0, width: 100, height: 100 });
        await vi.advanceTimersByTimeAsync(100);
        data.value.nodes.push({ id: 'b', type: 'text', text: 'two', x: 0, y: 0, width: 100, height: 100 });
        await nextTick();
        await save.flush();

        expect(requests.map((request) => request.body.data.nodes.length)).toEqual([1, 2]);
        expect(save.status.value).toBe('saved');
    });

    it('shows the server error and keeps the edit unsaved', async () => {
        respond = () => ({ ok: false, status: 422, json: async () => ({ errors: { 'data.nodes': ['A canvas holds at most 2000 cards.'] } }) });
        const { data, save } = mount();

        data.value.edges.push({ id: 'e', fromNode: 'a', toNode: 'b' });
        await vi.advanceTimersByTimeAsync(100);

        expect(save.status.value).toBe('error');
        expect(save.error.value).toBe('A canvas holds at most 2000 cards.');
    });

    it('saves with keepalive when the tab goes', async () => {
        const { data } = mount();

        data.value.nodes.push({ id: 'a', type: 'text', text: 'x', x: 0, y: 0, width: 100, height: 100 });
        await vi.advanceTimersByTimeAsync(10);
        window.dispatchEvent(new Event('pagehide'));

        expect(requests[0].keepalive).toBe(true);
    });
});
