import type { Resource } from '@coleus/support/types/resource';

export type NoteData = {
    id: number;
    title: string;
    body: string;
    snippet: string;
    created_at: string;
    updated_at: string;
    updated_at_for_humans: string;
};

export type NoteResource = Resource<NoteData>;
export type NoteCollection = { data: NoteData[] };
