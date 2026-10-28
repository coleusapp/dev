import { useTagMenu, type OpenTagMenu } from '@/composables/useTagMenu';
import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

const setup = () => {
    const inserted: string[] = [];
    const menu = useTagMenu({ tags: () => ['inbox', 'work', 'work/meetings', 'home'], insert: (_open, text) => inserted.push(text) });
    const at = (query: string): OpenTagMenu => ({ source: true, query, range: { from: 0, to: query.length + 1 }, position: { left: 0, top: 0 } });

    return { menu, inserted, at };
};

describe('useTagMenu', () => {
    it('ranks tags by the typed text, full paths included', () => {
        const { menu, at } = setup();

        menu.show(at('meet'));
        expect(menu.choices.value.map((choice) => choice.tag)).toEqual(['work/meetings']);

        menu.show(at(''));
        expect(menu.choices.value).toHaveLength(4);
    });

    it('does not offer back a tag typed out in full, any case', () => {
        const { menu, at } = setup();

        menu.show(at('Work'));
        expect(menu.choices.value.map((choice) => choice.tag)).toEqual(['work/meetings']);
    });

    it('writes the chosen tag with a trailing space', async () => {
        const { menu, inserted, at } = setup();

        menu.show(at('w'));
        await nextTick();
        menu.handleKey('ArrowDown');
        menu.handleKey('Tab');

        expect(inserted).toEqual(['#work/meetings ']);
        expect(menu.open.value).toBeNull();
    });

    it('lets Enter through when nothing matches', () => {
        const { menu, inserted, at } = setup();

        menu.show(at('zzz'));

        expect(menu.handleKey('Enter')).toBe(false);
        expect(inserted).toEqual([]);
    });
});
