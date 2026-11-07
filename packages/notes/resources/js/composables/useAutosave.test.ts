// @vitest-environment jsdom
import type { NoteDetailData } from '@/components/notes/note';
import { useAutosave } from '@/composables/useAutosave';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createApp, defineComponent, ref, type Ref } from 'vue';

const requests: { url: string; body: unknown }[] = [];

vi.mock('@inertiajs/vue3', () => ({
    useHttp: (data: { body: string }) => {
        const http = {
            ...data,
            errors: {},
            put: async (url: string) => {
                requests.push({ url, body: { body: http.body } });

                return { data: { id: 1, body: http.body } };
            },
        };

        return http;
    },
}));

beforeEach(() => {
    vi.useFakeTimers();
    vi.stubGlobal('route', (name: string, params: { note: number }) => `/${name}/${params.note}`);
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
        const { body, autosave } = mount();

        body.value = 'changed';
        autosave.save();
        await vi.advanceTimersByTimeAsync(100);

        expect(requests).toEqual([{ url: '/notes.update/1', body: { body: 'changed' } }]);
    });
});
