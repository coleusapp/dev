import { noteOutline } from '@/lib/outline';
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
    /**
     * Search results only: lines that match, in context (up to 20), and how many match in all.
     */
    matches?: { lines: { line: number; parts: HighlightPart[] }[]; total: number } | null;
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

export type SortField = 'modified' | 'created' | 'title';

export type ListSort = {
    sort: SortField;
    direction: 'asc' | 'desc';
    scope: 'folder' | 'default';
};

/**
 * The server's list order (Enums\NoteSort): pinned notes first, then the
 * chosen field in the chosen direction (untitled notes last by title),
 * then id.
 */
export const compareNotes =
    ({ sort, direction }: Pick<ListSort, 'sort' | 'direction'>) =>
    (a: NoteData, b: NoteData): number => {
        const sign = direction === 'asc' ? 1 : -1;
        const pinned = Number(b.pinned) - Number(a.pinned);

        if (pinned !== 0) {
            return pinned;
        }

        if (sort === 'title') {
            const untitled = Number(a.title === '') - Number(b.title === '');
            const byTitle = a.title.toLowerCase().localeCompare(b.title.toLowerCase());

            return untitled || sign * byTitle || sign * (a.id - b.id);
        }

        const field = sort === 'created' ? 'created_at' : 'updated_at';

        return sign * a[field].localeCompare(b[field]) || sign * (a.id - b.id);
    };

export type TagNode = {
    name: string;
    path: string;
    count: number;
    children: TagNode[];
};

export type NoteView = 'notes' | 'archive' | 'trash' | 'recent';

export type NoteFilters = {
    tag: string | null;
    q: string | null;
    view: NoteView;
    folder: number | null;
    smart: number | null;
    prop: string | null;
    value: string | null;
};

/**
 * "status", or "status: draft" when filtering by a value too.
 */
export const describeProperty = (filters: Pick<NoteFilters, 'prop' | 'value'>): string =>
    filters.prop ? (filters.value ? `${filters.prop}: ${filters.value}` : filters.prop) : '';

export type SmartRules = {
    tags: string[];
    tags_match: 'all' | 'any';
    folder_id: number | null;
    edited_within_days: number | null;
    created_within_days: number | null;
    edited_from: string | null;
    edited_to: string | null;
    created_from: string | null;
    created_to: string | null;
    pinned: boolean;
    has_checklist: boolean;
    has_open_tasks: boolean;
};

export type SmartFolder = { id: number; name: string; rules: SmartRules; count: number };

/**
 * The tags in a "?tag=" filter ("work,urgent" means notes with both).
 */
export const tagList = (tag: string | null): string[] => (tag ? tag.split(',').filter((each) => each !== '') : []);

/**
 * The filter with `tag` added, or removed when it's already in it.
 */
export const toggleTagFilter = (current: string | null, tag: string): string | null => {
    const tags = tagList(current);
    const next = tags.includes(tag) ? tags.filter((each) => each !== tag) : [...tags, tag];

    return next.length ? next.join(',') : null;
};

/**
 * "#work + #urgent" for headings and messages.
 */
export const describeTags = (tag: string | null): string =>
    tagList(tag)
        .map((each) => `#${each}`)
        .join(' + ');

export type FolderNode = {
    id: number;
    name: string;
    count: number;
    children: FolderNode[];
};

/**
 * One dialog for folder work: create (inside `parent`), rename or move a
 * folder, or move a note into a folder.
 */
export type FolderDialog =
    | { type: 'create'; parent: FolderNode | null }
    | { type: 'rename'; folder: FolderNode; parentId: number | null }
    | { type: 'move'; folder: FolderNode; parentId: number | null }
    | { type: 'move-note'; noteId: number; folderId: number | null };

/**
 * Dragged note rows carry their id under this type.
 */
export const NOTE_DRAG_TYPE = 'application/x-coleus-note';

/**
 * The query string that carries the list filters on links and redirects.
 */
export const filterQuery = (filters: Partial<NoteFilters>): Record<string, string> =>
    Object.fromEntries(
        Object.entries(filters)
            .filter(
                ([key, value]) => (typeof value === 'string' && value !== '') || ((key === 'folder' || key === 'smart') && typeof value === 'number'),
            )
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
    'list.sort': SortField;
    'list.sort_direction': 'asc' | 'desc';
    'list.group_by_date': boolean;
    'list.relative_dates': boolean;
    'list.show_snippets': boolean;
    'search.sort': 'relevance' | 'list';
};

export type BookmarkItem = {
    id: number;
    type: 'note' | 'heading' | 'search';
    note_id: number | null;
    heading: string | null;
    filters: Partial<NoteFilters> | null;
    label: string | null;
    title: string;
    group: string | null;
};

export type BookmarkGroup = { group: string | null; bookmarks: BookmarkItem[] };

/**
 * Where a bookmark leads: the note, the note scrolled to a heading, or the
 * list with the saved filters.
 */
export const bookmarkHref = (bookmark: BookmarkItem): string => {
    if (bookmark.type === 'search') {
        return route('notes.index', filterQuery(bookmark.filters ?? {}));
    }

    return route('notes.show', { note: bookmark.note_id, ...(bookmark.heading ? { heading: bookmark.heading } : {}) });
};

/**
 * The headings in a note's Markdown (see lib/outline.ts), for bookmarking.
 */
export const noteHeadings = (markdown: string): string[] => noteOutline(markdown).map((heading) => heading.text);
