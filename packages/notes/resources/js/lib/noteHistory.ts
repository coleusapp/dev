/**
 * Back / forward between opened notes (O7.9), like a browser's history but
 * only for notes: changing filters, views or pages in between doesn't add
 * entries. `index` points at the open note.
 */
export type NoteHistory = { entries: number[]; index: number };

export const HISTORY_LIMIT = 100;

export const emptyHistory = (): NoteHistory => ({ entries: [], index: -1 });

/**
 * Opening a note (not by going back or forward) drops the forward entries,
 * as a browser does. Reopening the open note changes nothing.
 */
export const visitNote = (history: NoteHistory, id: number): NoteHistory => {
    if (history.entries[history.index] === id) {
        return history;
    }

    const entries = [...history.entries.slice(0, history.index + 1), id].slice(-HISTORY_LIMIT);

    return { entries, index: entries.length - 1 };
};

/**
 * The history after one step back (-1) or forward (1), or null at either end.
 */
export const stepHistory = (history: NoteHistory, step: 1 | -1): NoteHistory | null => {
    const index = history.index + step;

    return index >= 0 && index < history.entries.length ? { entries: history.entries, index } : null;
};

export const currentEntry = (history: NoteHistory): number | null => history.entries[history.index] ?? null;

/**
 * Drops notes that no longer exist. Neighbours that end up the same note
 * merge, and the pointer stays on the same entry or, if that one went, the
 * one before it.
 */
export const forgetNote = (history: NoteHistory, id: number): NoteHistory => {
    const entries: number[] = [];
    let index = -1;

    history.entries.forEach((entry, position) => {
        if (entry !== id && entries[entries.length - 1] !== entry) {
            entries.push(entry);
        }

        if (position === history.index) {
            index = entries.length - 1;
        }
    });

    return { entries, index: entries.length ? Math.max(index, 0) : -1 };
};
