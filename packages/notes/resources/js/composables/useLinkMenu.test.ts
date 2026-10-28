// @vitest-environment jsdom
import { useLinkMenu, type OpenLinkMenu } from '@/composables/useLinkMenu';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

const notes = [
    { id: 1, title: 'Trip to Lisbon', aliases: ['Portugal'], archived: false },
    { id: 2, title: 'Groceries', aliases: [], archived: true },
    { id: 3, title: 'Current', aliases: [], archived: false },
];

const requests: string[] = [];

beforeEach(() => {
    vi.stubGlobal('route', (name: string, params?: { note: number }) => (params ? `/${name}/${params.note}` : `/${name}`));
    vi.stubGlobal(
        'fetch',
        vi.fn(async (url: string) => {
            requests.push(url);
            const data = url === '/notes.switcher' ? notes : url === '/notes.headings/1' ? ['Day one', 'Day two', 'Food'] : null;

            return { ok: data !== null, json: async () => ({ data }) };
        }),
    );
});

afterEach(() => {
    vi.unstubAllGlobals();
    requests.length = 0;
});

const flush = async (): Promise<void> => {
    for (let i = 0; i < 5; i++) {
        await nextTick();
        await Promise.resolve();
    }
};

const setup = () => {
    const inserted: [OpenLinkMenu, string][] = [];
    const menu = useLinkMenu({
        currentNoteId: () => 3,
        currentHeadings: () => ['Intro', 'Packing'],
        insert: (open, link) => inserted.push([open, link]),
    });
    const at = (query: string): OpenLinkMenu => ({ source: false, trigger: '[[', query, range: { from: 1, to: 3 }, position: { left: 0, top: 0 } });

    return { menu, inserted, at };
};

describe('useLinkMenu', () => {
    it('suggests other notes by title or alias, and offers a new note', async () => {
        const { menu, at } = setup();
        menu.show(at(''));
        await flush();

        expect(menu.choices.value.map((choice) => choice.link)).toEqual(['Trip to Lisbon', 'Groceries']);
        expect(menu.choices.value[1].hint).toBe('Archived');

        menu.show(at('portu'));
        await flush();
        expect(menu.choices.value.map(({ kind, link, hint }) => ({ kind, link, hint }))).toEqual([
            { kind: 'note', link: 'Portugal', hint: 'Trip to Lisbon' },
            { kind: 'new', link: 'portu', hint: 'New note' },
        ]);

        menu.show(at('groceries'));
        await flush();
        expect(menu.choices.value.map((choice) => choice.kind)).toEqual(['note']);
    });

    it('suggests headings of a named note after "#", and of this note for "#…"', async () => {
        const { menu, at } = setup();
        menu.show(at(''));
        await flush();

        menu.show(at('trip to lisbon#day'));
        await flush();
        expect(requests).toContain('/notes.headings/1');
        expect(menu.choices.value.map((choice) => choice.link)).toEqual(['Trip to Lisbon#Day one', 'Trip to Lisbon#Day two']);

        menu.show(at('#pack'));
        await flush();
        expect(menu.choices.value.map((choice) => choice.link)).toEqual(['#Packing']);
    });

    it('moves with the arrow keys and inserts the chosen link', async () => {
        const { menu, inserted, at } = setup();
        menu.show(at(''));
        await flush();

        expect(menu.handleKey('ArrowDown')).toBe(true);
        expect(menu.handleKey('Enter')).toBe(true);
        expect(inserted.map(([, link]) => link)).toEqual(['Groceries']);
        expect(menu.open.value).toBeNull();
        expect(menu.handleKey('Enter')).toBe(false);
    });

    it('closes on Escape without inserting', async () => {
        const { menu, inserted, at } = setup();
        menu.show(at('x'));
        await flush();

        expect(menu.handleKey('Escape')).toBe(true);
        expect(menu.open.value).toBeNull();
        expect(inserted).toEqual([]);
    });
});
