// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const reloads: unknown[] = [];

vi.mock('@inertiajs/vue3', () => ({ router: { reload: (options: unknown) => reloads.push(options) } }));

const calls: { url: string; method: string }[] = [];
let ok = true;

beforeEach(() => {
    vi.stubGlobal('route', (name: string, params: Record<string, number>) => `/${name}/${Object.values(params).join('/')}`);
    vi.stubGlobal(
        'fetch',
        vi.fn(async (url: string, init: { method?: string }) => {
            calls.push({ url, method: init.method ?? 'GET' });

            return {
                ok,
                json: async () => ({
                    data: url.includes('index')
                        ? [
                              { id: 4, title: 'Plans', count: 2, context: [] },
                              { id: 5, title: 'Ideas', count: 1, context: [] },
                          ]
                        : { count: 2 },
                }),
            };
        }),
    );
});

afterEach(() => {
    vi.unstubAllGlobals();
    calls.length = 0;
    reloads.length = 0;
    ok = true;
});

const setup = async () => (await import('@/composables/useUnlinkedMentions')).useUnlinkedMentions(() => 9);

describe('useUnlinkedMentions', () => {
    it('loads the mentions of the note', async () => {
        const mentions = await setup();

        await mentions.load();

        expect(calls).toEqual([{ url: '/notes.mentions.index/9', method: 'GET' }]);
        expect(mentions.mentions.value?.map((mention) => mention.id)).toEqual([4, 5]);
        expect(mentions.failed.value).toBe(false);
    });

    it('links one note, drops it from the list and reloads the note', async () => {
        const mentions = await setup();
        await mentions.load();

        await mentions.linkAll(mentions.mentions.value![0]);

        expect(calls[1]).toEqual({ url: '/notes.mentions.store/9/4', method: 'POST' });
        expect(mentions.mentions.value?.map((mention) => mention.id)).toEqual([5]);
        expect(reloads).toEqual([{ only: ['note', 'notes'] }]);
        expect(mentions.linking.value).toBeNull();
    });

    it('reports a failed lookup and keeps the list on a failed link', async () => {
        const mentions = await setup();
        await mentions.load();
        ok = false;

        await mentions.linkAll(mentions.mentions.value![0]);
        expect(mentions.mentions.value).toHaveLength(2);
        expect(reloads).toEqual([]);

        await mentions.load();
        expect(mentions.failed.value).toBe(true);
        expect(mentions.mentions.value).toEqual([]);
    });

    it('forgets the list so it loads again', async () => {
        const mentions = await setup();
        await mentions.load();

        mentions.forget();

        expect(mentions.mentions.value).toBeNull();
    });
});
