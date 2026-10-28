import { compareNotes, filterQuery } from '@/components/notes/note';
import { describe, expect, it } from 'vitest';

describe('filterQuery', () => {
    it('keeps set filters as strings, including the folder id', () => {
        expect(filterQuery({ tag: 'work', q: '', view: 'archive', folder: 7 })).toEqual({ tag: 'work', view: 'archive', folder: '7' });
    });

    it('drops the default view and empty values', () => {
        expect(filterQuery({ tag: null, q: null, view: 'notes', folder: null })).toEqual({});
    });
});

describe('compareNotes', () => {
    const note = (id: number, title: string, created: string, updated: string, pinned = false) =>
        ({ id, title, created_at: created, updated_at: updated, pinned }) as unknown as import('@/components/notes/note').NoteData;
    const a = note(1, 'apple', '2026-01-03', '2026-01-09');
    const b = note(2, 'Banana', '2026-01-01', '2026-01-08');
    const c = note(3, '', '2026-01-02', '2026-01-07');
    const p = note(4, 'zebra', '2026-01-01', '2026-01-01', true);
    const ids = (sort: 'modified' | 'created' | 'title', direction: 'asc' | 'desc') =>
        [c, b, a, p].sort(compareNotes({ sort, direction })).map((n) => n.id);

    it('mirrors the server order', () => {
        expect(ids('modified', 'desc')).toEqual([4, 1, 2, 3]);
        expect(ids('created', 'asc')).toEqual([4, 2, 3, 1]);
        expect(ids('title', 'asc')).toEqual([4, 1, 2, 3]);
        expect(ids('title', 'desc')).toEqual([4, 2, 1, 3]);
    });
});

describe('tag filters', () => {
    it('adds and removes tags in a combined filter', async () => {
        const { toggleTagFilter, describeTags, tagList } = await import('@/components/notes/note');

        expect(toggleTagFilter(null, 'work')).toBe('work');
        expect(toggleTagFilter('work', 'urgent')).toBe('work,urgent');
        expect(toggleTagFilter('work,urgent', 'work')).toBe('urgent');
        expect(toggleTagFilter('work', 'work')).toBeNull();
        expect(describeTags('work,urgent')).toBe('#work + #urgent');
        expect(tagList(null)).toEqual([]);
    });
});
