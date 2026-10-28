import { filterQuery, type NoteFilters } from '@/components/notes/note';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

/**
 * Search in this list or in all notes (A10.2, like Apple Notes' "this
 * folder / all notes"). A search typed in a narrowed list (folder, tag,
 * smart folder, property, archive…) stays there; "All notes" widens it and
 * remembers where it came from so it can switch back. That memory goes once
 * the search is cleared or another list is picked.
 */
export const useSearchScope = (options: { filters: () => NoteFilters; listTitle: () => string; url: () => string }) => {
    const narrowed = computed(() => {
        const filters = options.filters();

        return Boolean(filters.folder || filters.tag || filters.smart || filters.prop || filters.view !== 'notes');
    });

    const scope = ref<{ filters: Partial<NoteFilters>; label: string } | null>(null);

    watch([() => options.filters().q, narrowed], ([q, isNarrowed]) => {
        if (!q || isNarrowed) {
            scope.value = null;
        }
    });

    /**
     * The narrowed list's name while searching, or null when there's no choice to offer.
     */
    const label = computed(() => (options.filters().q ? (narrowed.value ? options.listTitle() : (scope.value?.label ?? null)) : null));

    const visit = (filters: Partial<NoteFilters>): void => {
        router.get(options.url(), filterQuery(filters), { preserveState: true, preserveScroll: true });
    };

    const searchEverywhere = (): void => {
        const filters = options.filters();

        if (!narrowed.value || !filters.q) {
            return;
        }

        scope.value = { filters: { ...filters, q: null }, label: options.listTitle() };
        visit({ q: filters.q });
    };

    const searchInScope = (): void => {
        if (scope.value && !narrowed.value) {
            visit({ ...scope.value.filters, q: options.filters().q });
        }
    };

    return { narrowed, label, searchEverywhere, searchInScope };
};
