import type { HighlightPart } from '@/components/notes/note';
import { useMenuKeys } from '@/composables/useMenuKeys';
import { splitLinkQuery, type LinkTrigger } from '@/editor/linkQuery';
import { fuzzyRank, highlightPositions } from '@/lib/fuzzy';
import { jsonHeaders } from '@/lib/http';
import { computed, ref, watch } from 'vue';

export type SwitcherNote = { id: number; title: string; aliases: string[]; archived: boolean };

/**
 * The note picker while a link is being typed: where it is (visual editor
 * or source), what's typed, the range to replace and where to show it.
 */
export type OpenLinkMenu = {
    source: boolean;
    trigger: LinkTrigger;
    query: string;
    range: { from: number; to: number };
    position: { left: number; top: number };
};

/**
 * A row of the picker; `link` is what goes between the brackets.
 */
export type LinkChoice = { key: string; kind: 'note' | 'heading' | 'new'; link: string; parts: HighlightPart[]; hint: string | null };

const MAX_CHOICES = 8;

/**
 * Link autocomplete (O2.4) and the ">>" note picker (A6.1): note titles and
 * aliases ranked fuzzily as you type, then, after "#", the headings of the
 * chosen note ("#…" alone: this note's). The note list comes from the quick
 * switcher's endpoint and headings from notes.headings, each fetched once
 * per open and cached.
 */
export const useLinkMenu = (options: {
    currentNoteId: () => number;
    currentHeadings: () => string[];
    insert: (open: OpenLinkMenu, link: string) => void;
}) => {
    const open = ref<OpenLinkMenu | null>(null);
    const notes = ref<SwitcherNote[]>([]);
    const headings = ref(new Map<number, string[]>());

    const fetchJson = async <T>(url: string): Promise<T | null> => {
        const response = await fetch(url, { headers: jsonHeaders(), credentials: 'same-origin' });
        const payload = await response.json().catch(() => null);

        return response.ok ? (payload?.data ?? null) : null;
    };

    const loadNotes = async (): Promise<void> => {
        notes.value = (await fetchJson<SwitcherNote[]>(route('notes.switcher'))) ?? [];
    };

    const loadHeadings = async (id: number): Promise<void> => {
        if (headings.value.has(id)) {
            return;
        }

        headings.value = new Map(headings.value).set(id, []);
        const found = (await fetchJson<string[]>(route('notes.headings', { note: id }))) ?? [];
        headings.value = new Map(headings.value).set(id, found);
    };

    const names = (note: SwitcherNote): string[] => [note.title, ...note.aliases].filter((name) => name !== '');

    /**
     * The note a "Note#…" query names: an exact title or alias, any case.
     */
    const namedNote = (name: string): SwitcherNote | null => {
        const lower = name.toLowerCase();

        return notes.value.find((note) => names(note).some((each) => each.toLowerCase() === lower)) ?? null;
    };

    const choices = computed<LinkChoice[]>(() => {
        if (!open.value) {
            return [];
        }

        const { note, heading } = splitLinkQuery(open.value.query);

        if (heading !== null) {
            const target = note === '' ? null : namedNote(note);

            if (note !== '' && !target) {
                return [];
            }

            const list = target ? (headings.value.get(target.id) ?? []) : options.currentHeadings();
            const prefix = target ? (names(target).find((name) => name.toLowerCase() === note.toLowerCase()) ?? note) : '';

            return fuzzyRank(list, heading, (each) => [each])
                .slice(0, MAX_CHOICES)
                .map(({ item, match }) => ({
                    key: `heading:${item}`,
                    kind: 'heading',
                    link: `${prefix}#${item}`,
                    parts: highlightPositions(item, match.positions),
                    hint: prefix || null,
                }));
        }

        const candidates = notes.value.filter((each) => each.id !== options.currentNoteId() && names(each).length);
        const found: LinkChoice[] = fuzzyRank(candidates, note, names)
            .slice(0, MAX_CHOICES)
            .map(({ item, name, match }) => ({
                key: `note:${item.id}`,
                kind: 'note',
                link: name,
                parts: highlightPositions(name, match.positions),
                hint: name === item.title ? (item.archived ? 'Archived' : null) : item.title,
            }));
        const exists = note === '' || notes.value.some((each) => names(each).some((name) => name.toLowerCase() === note.toLowerCase()));

        return exists ? found : [...found, { key: 'new', kind: 'new', link: note, parts: [{ text: note, match: false }], hint: 'New note' }];
    });

    /**
     * Headings are fetched as soon as "Note#" names a note.
     */
    watch(
        () => open.value?.query,
        (query) => {
            const { note, heading } = splitLinkQuery(query ?? '');
            const target = heading !== null && note !== '' ? namedNote(note) : null;

            if (target) {
                void loadHeadings(target.id);
            }
        },
    );

    const show = (state: OpenLinkMenu | null): void => {
        if (state && !open.value) {
            headings.value = new Map();
            void loadNotes();
        }

        open.value = state;
    };

    const choose = (choice: LinkChoice | undefined): void => {
        const current = open.value;

        if (!current || !choice) {
            return;
        }

        open.value = null;
        options.insert(current, choice.link);
    };

    const { selected, handleKey } = useMenuKeys(choices, { isOpen: () => open.value !== null, choose, close: () => (open.value = null) });

    return { open, selected, choices, show, choose, handleKey };
};
