import type { HighlightPart } from '@/components/notes/note';
import { useMenuKeys } from '@/composables/useMenuKeys';
import { fuzzyRank, highlightPositions } from '@/lib/fuzzy';
import { computed, ref } from 'vue';

/**
 * The tag menu while a #tag is being typed, in the visual editor or source.
 */
export type OpenTagMenu = { source: boolean; query: string; range: { from: number; to: number }; position: { left: number; top: number } };

export type TagChoice = { tag: string; parts: HighlightPart[] };

const MAX_CHOICES = 8;

/**
 * Tag autocomplete (O2.5): the user's tags (full paths, so "#work/meetings"
 * comes up for "meet") ranked fuzzily as a #tag is typed. A tag typed out
 * in full isn't offered back. Choosing writes "#tag " over what was typed.
 */
export const useTagMenu = (options: { tags: () => string[]; insert: (open: OpenTagMenu, text: string) => void }) => {
    const open = ref<OpenTagMenu | null>(null);

    const choices = computed<TagChoice[]>(() => {
        if (!open.value) {
            return [];
        }

        const query = open.value.query.toLowerCase();

        return fuzzyRank(
            options.tags().filter((tag) => tag !== query),
            query,
            (tag) => [tag],
        )
            .slice(0, MAX_CHOICES)
            .map(({ item, match }) => ({ tag: item, parts: highlightPositions(item, match.positions) }));
    });

    const show = (state: OpenTagMenu | null): void => {
        open.value = state;
    };

    const choose = (choice: TagChoice | undefined): void => {
        const current = open.value;

        if (!current || !choice) {
            return;
        }

        open.value = null;
        options.insert(current, `#${choice.tag} `);
    };

    const { selected, handleKey } = useMenuKeys(choices, { isOpen: () => open.value !== null, choose, close: () => (open.value = null) });

    return { open, selected, choices, show, choose, handleKey };
};
