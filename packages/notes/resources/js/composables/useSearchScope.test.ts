import type { NoteFilters } from '@/components/notes/note';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

const visits: { url: string; query: Record<string, string> }[] = [];

vi.mock('@inertiajs/vue3', () => ({
    router: {
        get: (url: string, query: Record<string, string>) => {
            visits.push({ url, query });
            filters.value = { ...blank, ...query, folder: query.folder ? Number(query.folder) : null } as NoteFilters;
        },
    },
}));

const blank: NoteFilters = { tag: null, q: null, view: 'notes', folder: null, smart: null, prop: null, value: null };
const filters = ref<NoteFilters>({ ...blank });

const setup = async () => {
    const { useSearchScope } = await import('@/composables/useSearchScope');

    return useSearchScope({ filters: () => filters.value, listTitle: () => (filters.value.folder ? 'Trips' : 'Notes'), url: () => '/notes' });
};

beforeEach(() => {
    visits.length = 0;
    filters.value = { ...blank };
});

describe('useSearchScope', () => {
    it('offers no choice without a search or in an unnarrowed list', async () => {
        const scope = await setup();

        filters.value = { ...blank, folder: 3 };
        expect(scope.label.value).toBeNull();

        filters.value = { ...blank, q: 'kiwi' };
        expect(scope.label.value).toBeNull();
    });

    it('widens a narrowed search to all notes and switches back', async () => {
        const scope = await setup();
        filters.value = { ...blank, folder: 3, q: 'kiwi' };

        expect(scope.label.value).toBe('Trips');
        expect(scope.narrowed.value).toBe(true);

        scope.searchEverywhere();
        await nextTick();
        expect(visits.at(-1)?.query).toEqual({ q: 'kiwi' });
        expect(scope.narrowed.value).toBe(false);
        expect(scope.label.value).toBe('Trips');

        scope.searchInScope();
        await nextTick();
        expect(visits.at(-1)?.query).toEqual({ folder: '3', q: 'kiwi' });
        expect(scope.narrowed.value).toBe(true);
    });

    it('forgets where it came from when the search is cleared', async () => {
        const scope = await setup();
        filters.value = { ...blank, folder: 3, q: 'kiwi' };
        scope.searchEverywhere();
        await nextTick();

        filters.value = { ...blank };
        await nextTick();
        filters.value = { ...blank, q: 'kiwi' };
        await nextTick();

        expect(scope.label.value).toBeNull();
    });

    it('does nothing when the choice already applies', async () => {
        const scope = await setup();
        filters.value = { ...blank, folder: 3, q: 'kiwi' };

        scope.searchInScope();
        expect(visits).toEqual([]);
    });
});
