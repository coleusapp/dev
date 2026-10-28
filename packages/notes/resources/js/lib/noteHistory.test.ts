import { currentEntry, emptyHistory, forgetNote, HISTORY_LIMIT, stepHistory, visitNote, type NoteHistory } from '@/lib/noteHistory';
import { describe, expect, it } from 'vitest';

const visited = (...ids: number[]): NoteHistory => ids.reduce(visitNote, emptyHistory());

describe('visitNote', () => {
    it('adds entries and points at the newest', () => {
        expect(visited(1, 2, 3)).toEqual({ entries: [1, 2, 3], index: 2 });
    });

    it('ignores reopening the open note', () => {
        expect(visited(1, 2, 2)).toEqual({ entries: [1, 2], index: 1 });
    });

    it('drops forward entries when opening a note after going back', () => {
        const back = stepHistory(visited(1, 2, 3), -1)!;

        expect(visitNote(back, 4)).toEqual({ entries: [1, 2, 4], index: 2 });
    });

    it('keeps the newest entries up to the limit', () => {
        const history = visited(...Array.from({ length: HISTORY_LIMIT + 5 }, (_, index) => index));

        expect(history.entries).toHaveLength(HISTORY_LIMIT);
        expect(history.entries[0]).toBe(5);
        expect(history.index).toBe(HISTORY_LIMIT - 1);
    });
});

describe('stepHistory', () => {
    it('moves back and forward within the entries', () => {
        const back = stepHistory(visited(1, 2, 3), -1)!;

        expect(currentEntry(back)).toBe(2);
        expect(currentEntry(stepHistory(back, 1)!)).toBe(3);
    });

    it('returns null at either end', () => {
        expect(stepHistory(visited(1), -1)).toBeNull();
        expect(stepHistory(visited(1, 2), 1)).toBeNull();
        expect(stepHistory(emptyHistory(), 1)).toBeNull();
        expect(currentEntry(emptyHistory())).toBeNull();
    });
});

describe('forgetNote', () => {
    it('removes the note, merging neighbours that become the same', () => {
        expect(forgetNote(visited(1, 2, 1, 3), 2)).toEqual({ entries: [1, 3], index: 1 });
    });

    it('keeps pointing at the same entry', () => {
        const history = { entries: [1, 2, 3, 4], index: 2 };

        expect(forgetNote(history, 1)).toEqual({ entries: [2, 3, 4], index: 1 });
        expect(forgetNote(history, 4)).toEqual({ entries: [1, 2, 3], index: 2 });
    });

    it('falls back to the entry before when the pointed one goes', () => {
        expect(forgetNote({ entries: [1, 2, 3], index: 2 }, 3)).toEqual({ entries: [1, 2], index: 1 });
        expect(forgetNote({ entries: [1, 2, 3], index: 0 }, 1)).toEqual({ entries: [2, 3], index: 0 });
        expect(forgetNote({ entries: [1], index: 0 }, 1)).toEqual({ entries: [], index: -1 });
    });
});
