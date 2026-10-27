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
    highlights: { title: HighlightPart[]; snippet: HighlightPart[] } | null;
    created_at: string;
    updated_at: string;
    updated_at_for_humans: string;
};

/**
 * The open note, which also carries its server-rendered preview.
 */
export type NoteDetailData = NoteData & {
    html: string;
    tags: string[];
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

export type NoteView = 'notes' | 'archive';

export type NoteFilters = {
    tag: string | null;
    q: string | null;
    view: NoteView;
};

/**
 * The query string that carries the list filters on links and redirects.
 */
export const filterQuery = (filters: Partial<NoteFilters>): Record<string, string> =>
    Object.fromEntries(
        Object.entries(filters).filter(
            (entry): entry is [string, string] => typeof entry[1] === 'string' && entry[1] !== '' && !(entry[0] === 'view' && entry[1] === 'notes'),
        ),
    );
