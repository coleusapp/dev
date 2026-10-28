import { useDateMenu, type OpenDateMenu } from '@/composables/useDateMenu';
import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

const at = (query: string): OpenDateMenu => ({ source: true, query, range: { from: 0, to: query.length + 1 }, position: { left: 0, top: 0 } });

describe('useDateMenu', () => {
    it('suggests dates for the typed text and writes the chosen one', async () => {
        const inserted: string[] = [];
        const menu = useDateMenu({ today: () => new Date(2026, 9, 28), insert: (_open, text) => inserted.push(text) });

        menu.show(at('tom'));
        expect(menu.choices.value.map((choice) => choice.label)).toEqual(['Tomorrow']);

        await nextTick();
        menu.handleKey('Enter');
        expect(inserted).toEqual(['@2026-10-29 ']);
        expect(menu.open.value).toBeNull();
    });

    it('lets Enter through when nothing matches', () => {
        const menu = useDateMenu({ insert: () => undefined });

        menu.show(at('zzz'));

        expect(menu.handleKey('Enter')).toBe(false);
    });
});
