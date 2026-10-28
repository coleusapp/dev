import type { NoteData } from '@/components/notes/note';
import { groupNotesByDate } from '@/lib/dateGroups';
import { describe, expect, it } from 'vitest';

const note = (id: number, updated: string, pinned = false): NoteData => ({ id, updated_at: updated, created_at: updated, pinned }) as unknown as NoteData;

describe('groupNotesByDate', () => {
    const now = new Date(2026, 9, 28, 15, 0);

    it('sorts notes into Apple-style sections', () => {
        const notes = [
            note(1, new Date(2026, 0, 1).toISOString(), true),
            note(2, new Date(2026, 9, 28, 9).toISOString()),
            note(3, new Date(2026, 9, 27, 23).toISOString()),
            note(4, new Date(2026, 9, 24).toISOString()),
            note(5, new Date(2026, 9, 10).toISOString()),
            note(6, new Date(2026, 6, 4).toISOString()),
            note(7, new Date(2025, 11, 31).toISOString()),
        ];

        const groups = groupNotesByDate(notes, 'updated_at', now);

        expect(groups.map((group) => group.notes.map((n) => n.id))).toEqual([[1], [2], [3], [4], [5], [6], [7]]);
        expect(groups.map((group) => group.label).slice(0, 5)).toEqual(['Pinned', 'Today', 'Yesterday', 'Previous 7 Days', 'Previous 30 Days']);
        expect(groups[6].label).toBe('2025');
    });

    it('keeps one section per label in the order the notes come', () => {
        const groups = groupNotesByDate([note(1, new Date(2026, 9, 28).toISOString()), note(2, new Date(2026, 9, 28, 1).toISOString())], 'updated_at', now);

        expect(groups).toHaveLength(1);
        expect(groups[0].notes.map((n) => n.id)).toEqual([1, 2]);
    });

    it('returns no sections for no notes', () => {
        expect(groupNotesByDate([], 'updated_at', now)).toEqual([]);
    });
});
