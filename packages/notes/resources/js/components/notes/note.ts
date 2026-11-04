/**
 * Shapes of what the Notes pages receive from the server (see the
 * NoteResource / NoteDetailResource PHP classes).
 */
export type NoteData = {
    id: number;
    title: string;
    snippet: string;
    pinned: boolean;
    archived: boolean;
    bookmarked: boolean;
    trashed: boolean;
    created_at: string;
    updated_at: string;
    updated_at_for_humans: string;
};

export type NoteDetailData = NoteData & {
    body: string;
};

export type Resource<T> = { data: T };

export type NoteResource = Resource<NoteDetailData>;
export type NoteCollection = { data: NoteData[] };

export type SortField = 'modified' | 'created';

export type ListSort = {
    sort: SortField;
    direction: 'asc' | 'desc';
};

export type NoteView = 'notes' | 'bookmarks' | 'recent' | 'archive' | 'trash';

export type NoteFilters = {
    q: string | null;
    view: NoteView;
};

/**
 * The query string that carries the list filters on links and redirects.
 */
export const filterQuery = (filters: Partial<NoteFilters>): Record<string, string> =>
    Object.fromEntries(
        Object.entries(filters)
            .filter(([, value]) => typeof value === 'string' && value !== '')
            .filter(([key, value]) => !(key === 'view' && value === 'notes'))
            .map(([key, value]) => [key, String(value)]),
    );

/**
 * Newest list order first, the way the server sorts (pinned notes on top).
 */
export const compareNotes =
    ({ sort, direction }: ListSort) =>
    (a: NoteData, b: NoteData): number => {
        if (a.pinned !== b.pinned) {
            return a.pinned ? -1 : 1;
        }

        const sign = direction === 'asc' ? 1 : -1;
        const field = sort === 'created' ? 'created_at' : 'updated_at';

        return sign * a[field].localeCompare(b[field]) || sign * (a.id - b.id);
    };

/**
 * The signed-in user's Notes settings (NotePreferences::DEFINITIONS).
 */
export type NotePreferences = {
    'editor.autosave_delay': number;
    'editor.readable_width': boolean;
    'editor.text_direction': 'auto' | 'ltr' | 'rtl';
    'list.sort': SortField;
    'list.sort_direction': 'asc' | 'desc';
    'list.relative_dates': boolean;
    'list.show_snippets': boolean;
};
