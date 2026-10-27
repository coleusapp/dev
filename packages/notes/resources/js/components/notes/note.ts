import type { Resource } from '@coleus/support/types/resource';

export type HighlightPart = {
    text: string;
    match: boolean;
};

export type NoteData = {
    id: number;
    title: string;
    body: string;
    snippet: string;
    pinned: boolean;
    archived: boolean;
    folder_id: number | null;
    trashed: boolean;
    highlights: { title: HighlightPart[]; snippet: HighlightPart[] } | null;
    created_at: string;
    updated_at: string;
    updated_at_for_humans: string;
};

/**
 * The open note, which also carries its server-rendered preview.
 */
export type Backlink = {
    id: number;
    title: string;
    updated_at_for_humans: string;
};

export type NoteDetailData = NoteData & {
    html: string;
    tags: string[];
    backlinks: Backlink[];
};

export type NoteResource = Resource<NoteDetailData>;
export type NoteCollection = { data: NoteData[] };

/**
 * The server's list order: pinned notes first, then most recently updated.
 */
export const compareNotes = (a: NoteData, b: NoteData): number =>
    Number(b.pinned) - Number(a.pinned) || b.updated_at.localeCompare(a.updated_at) || b.id - a.id;

export type TagNode = {
    name: string;
    path: string;
    count: number;
    children: TagNode[];
};

export type NoteView = 'notes' | 'archive' | 'trash';

export type NoteFilters = {
    tag: string | null;
    q: string | null;
    view: NoteView;
    folder: number | null;
};

export type FolderNode = {
    id: number;
    name: string;
    count: number;
    children: FolderNode[];
};

/**
 * The query string that carries the list filters on links and redirects.
 */
export const filterQuery = (filters: Partial<NoteFilters>): Record<string, string> =>
    Object.fromEntries(
        Object.entries(filters)
            .filter(([key, value]) => (typeof value === 'string' && value !== '') || (key === 'folder' && typeof value === 'number'))
            .filter(([key, value]) => !(key === 'view' && value === 'notes'))
            .map(([key, value]) => [key, String(value)]),
    );

export type TableAction =
    | 'insert'
    | 'row-above'
    | 'row-below'
    | 'column-left'
    | 'column-right'
    | 'delete-row'
    | 'delete-column'
    | 'toggle-header'
    | 'delete-table'
    | 'format';

export type ChecklistAction = 'checked-to-bottom' | 'check-all' | 'uncheck-all' | 'delete-checked';

export type EditorMode = 'edit' | 'source' | 'split' | 'preview';

/**
 * The user's Notes preferences (see NotePreferences::DEFINITIONS on the server).
 */
export type NotePreferences = {
    'editor.default_mode': EditorMode;
    'editor.autosave_delay': number;
    'editor.readable_width': boolean;
    'editor.spellcheck': boolean;
    'editor.text_direction': 'auto' | 'ltr' | 'rtl';
    'editor.first_line_style': 'title' | 'heading' | 'subheading' | 'body';
    'checklists.checked_to_bottom': boolean;
    'list.relative_dates': boolean;
    'list.show_snippets': boolean;
};
