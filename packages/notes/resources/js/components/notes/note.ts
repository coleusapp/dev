export type NoteData = {
    id: number;
    title: string;
    snippet: string;
    pinned: boolean;
    trashed: boolean;
    created_at: string;
    updated_at: string;
    updated_at_for_humans: string;
};

export type NoteDetailData = NoteData & {
    body: string | null;
};

export type Resource<T> = { data: T };

export type NoteResource = Resource<NoteDetailData>;
export type NoteCollection = { data: NoteData[] };

export type NoteView = 'notes' | 'trash';

export type NoteCounts = Record<'notes' | 'trash', number>;

export type NoteFilters = {
    q: string | null;
    view: NoteView;
};

export const filterQuery = (filters: Partial<NoteFilters>): Record<string, string> =>
    Object.fromEntries(
        Object.entries(filters)
            .filter(([, value]) => typeof value === 'string' && value !== '')
            .filter(([key, value]) => !(key === 'view' && value === 'notes'))
            .map(([key, value]) => [key, String(value)]),
    );
