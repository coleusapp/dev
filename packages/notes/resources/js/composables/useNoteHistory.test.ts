import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

type InvalidListener = (event: { detail: { response: { status: number } }; preventDefault: () => void }) => void;

const visits: string[] = [];
let invalidListeners: InvalidListener[] = [];
let respond: (url: string) => number = () => 200;

vi.mock('@inertiajs/vue3', () => ({
    router: {
        on: (name: string, listener: InvalidListener) => {
            invalidListeners.push(listener);

            return () => (invalidListeners = invalidListeners.filter((other) => other !== listener));
        },
        visit: (url: string, options: { onFinish?: () => void }) => {
            visits.push(url);
            const status = respond(url);

            if (status === 200) {
                openId.value = Number(url.split('/').pop());
            } else {
                [...invalidListeners].forEach((listener) => listener({ detail: { response: { status } }, preventDefault: () => {} }));
            }

            options.onFinish?.();
        },
    },
}));

const openId = ref<number | null>(null);

/**
 * The history lives at module level, so each test loads a fresh copy.
 */
const setup = async () => {
    vi.resetModules();
    const { useNoteHistory } = await import('@/composables/useNoteHistory');

    return useNoteHistory(
        () => openId.value,
        (id) => `/notes/${id}`,
    );
};

const open = async (id: number): Promise<void> => {
    openId.value = id;
    await nextTick();
};

beforeEach(() => {
    visits.length = 0;
    invalidListeners = [];
    respond = () => 200;
    openId.value = null;
});

describe('useNoteHistory', () => {
    it('goes back and forward through opened notes', async () => {
        const history = await setup();
        await open(1);
        await open(2);
        await open(3);

        expect(history.canGoBack.value).toBe(true);
        expect(history.canGoForward.value).toBe(false);

        history.back();
        await nextTick();
        expect(openId.value).toBe(2);

        history.back();
        await nextTick();
        expect(openId.value).toBe(1);
        expect(history.canGoBack.value).toBe(false);

        history.forward();
        await nextTick();
        expect(openId.value).toBe(2);
        expect(history.canGoForward.value).toBe(true);
        expect(visits).toEqual(['/notes/2', '/notes/1', '/notes/2']);
    });

    it('opening another note after going back drops the forward entries', async () => {
        const history = await setup();
        await open(1);
        await open(2);
        history.back();
        await nextTick();
        await open(5);

        expect(history.canGoForward.value).toBe(false);
        history.back();
        await nextTick();
        expect(openId.value).toBe(1);
    });

    it('skips notes that were deleted for good', async () => {
        vi.useFakeTimers();
        const history = await setup();
        await open(1);
        await open(2);
        await open(3);
        respond = (url) => (url === '/notes/2' ? 404 : 200);

        history.back();
        await vi.runAllTimersAsync();
        await nextTick();

        expect(visits).toEqual(['/notes/2', '/notes/1']);
        expect(openId.value).toBe(1);
        expect(history.canGoBack.value).toBe(false);
        vi.useRealTimers();
    });
});
