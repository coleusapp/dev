// @vitest-environment jsdom
import type { NoteDetailData } from '@/components/notes/note';
import { useAutosave } from '@/composables/useAutosave';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createApp, defineComponent, ref, type Ref } from 'vue';

vi.mock('@inertiajs/vue3', () => ({ router: { on: () => () => undefined } }));

const requests: { url: string; body: unknown; keepalive?: boolean }[] = [];

beforeEach(() => {
    vi.useFakeTimers();
    vi.stubGlobal('route', (name: string, params: { note: number }) => `/${name}/${params.note}`);
    vi.stubGlobal(
        'fetch',
        vi.fn(async (url: string, init: { body: string; keepalive?: boolean }) => {
            requests.push({ url, body: JSON.parse(init.body), keepalive: init.keepalive });

            return { ok: true, json: async () => ({ data: { id: 1, body: '' } }) };
        }),
    );
});

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    requests.length = 0;
});

const mount = () => {
    const body = ref('start');
    let autosave!: ReturnType<typeof useAutosave>;
    const app = createApp(
        defineComponent({
            setup() {
                autosave = useAutosave({ id: 1, body: 'start' } as NoteDetailData, body as Ref<string>, () => undefined, 100);

                return () => null;
            },
        }),
    );
    app.mount(document.createElement('div'));

    return { body, autosave, app };
};

describe('useAutosave', () => {
    it('saves plain text to notes.update by default', async () => {
        const { body } = mount();

        body.value = 'changed';
        await vi.advanceTimersByTimeAsync(100);

        expect(requests).toEqual([{ url: '/notes.update/1', body: { body: 'changed' }, keepalive: undefined }]);
    });
});
