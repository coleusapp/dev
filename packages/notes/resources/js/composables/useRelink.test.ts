// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

const reloads: unknown[] = [];

vi.mock('@inertiajs/vue3', () => ({ router: { reload: (options: unknown) => reloads.push(options) } }));

const calls: { url: string; method: string; body?: string }[] = [];
let count = 2;

beforeEach(() => {
    vi.stubGlobal('route', (name: string, params: Record<string, unknown>) => `/${name}?${new URLSearchParams(params as Record<string, string>)}`);
    vi.stubGlobal(
        'fetch',
        vi.fn(async (url: string, init: { method: string; body?: string }) => {
            calls.push({ url, method: init.method, body: init.body });

            return { ok: true, json: async () => ({ data: { count } }) };
        }),
    );
});

afterEach(() => {
    vi.unstubAllGlobals();
    calls.length = 0;
    reloads.length = 0;
    count = 2;
});

const setup = async () => {
    const { useRelink } = await import('@/composables/useRelink');
    const title = ref('Old title');

    return { title, relink: useRelink({ noteId: () => 7, title: () => title.value }) };
};

describe('useRelink', () => {
    it('offers nothing while the title is unchanged', async () => {
        const { relink } = await setup();

        await relink.check();

        expect(relink.offer.value).toBeNull();
        expect(calls).toEqual([]);
    });

    it('offers to update links to the old title after a rename', async () => {
        const { title, relink } = await setup();
        title.value = 'New title';

        await relink.check();

        expect(calls[0]).toMatchObject({ method: 'GET', url: '/notes.relink.show?note=7&title=Old+title' });
        expect(relink.offer.value).toEqual({ from: 'Old title', count: 2 });
    });

    it('updates the links, then treats the new title as the baseline', async () => {
        const { title, relink } = await setup();
        title.value = 'New title';
        await relink.check();

        await relink.relink();

        expect(calls[1]).toMatchObject({ method: 'POST', body: JSON.stringify({ title: 'Old title' }) });
        expect(relink.offer.value).toBeNull();
        expect(reloads).toEqual([{ only: ['notes'] }]);

        await relink.check();
        expect(relink.offer.value).toBeNull();
        expect(calls).toHaveLength(2);
    });

    it('keeps the links when asked, and hides the offer when the title goes back', async () => {
        const { title, relink } = await setup();
        title.value = 'New title';
        await relink.check();

        title.value = 'Old title';
        await relink.check();
        expect(relink.offer.value).toBeNull();

        title.value = 'Newer';
        await relink.check();
        relink.keep();
        expect(relink.offer.value).toBeNull();

        title.value = 'Newest';
        await relink.check();
        expect(calls.at(-1)?.url).toContain('title=Newer');
    });

    it('offers nothing when no note links to the old title', async () => {
        count = 0;
        const { title, relink } = await setup();
        title.value = 'New title';

        await relink.check();

        expect(relink.offer.value).toBeNull();
    });
});
