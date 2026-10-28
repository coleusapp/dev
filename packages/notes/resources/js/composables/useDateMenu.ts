import { useMenuKeys } from '@/composables/useMenuKeys';
import { dateSuggestions, type DateSuggestion } from '@/lib/dates';
import { computed, ref } from 'vue';

export type OpenDateMenu = { source: boolean; query: string; range: { from: number; to: number }; position: { left: number; top: number } };

/**
 * Date suggestions after "@" (B5.4): choosing writes "@YYYY-MM-DD ", which
 * shows as a date chip.
 */
export const useDateMenu = (options: { today?: () => Date; insert: (open: OpenDateMenu, text: string) => void }) => {
    const open = ref<OpenDateMenu | null>(null);

    const choices = computed<DateSuggestion[]>(() => (open.value ? dateSuggestions(open.value.query, options.today?.() ?? new Date()) : []));

    const show = (state: OpenDateMenu | null): void => {
        open.value = state;
    };

    const choose = (choice: DateSuggestion | undefined): void => {
        const current = open.value;

        if (!current || !choice) {
            return;
        }

        open.value = null;
        options.insert(current, `@${choice.iso} `);
    };

    const { selected, handleKey } = useMenuKeys(choices, { isOpen: () => open.value !== null, choose, close: () => (open.value = null) });

    return { open, selected, choices, show, choose, handleKey };
};
