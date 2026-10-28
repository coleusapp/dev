import type { NoteData } from '@/components/notes/note';

export type NoteGroup = { label: string; notes: NoteData[] };

const startOfDay = (date: Date): number => new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();

const DAY = 24 * 60 * 60 * 1000;

/**
 * Apple Notes' date sections: Pinned, Today, Yesterday, Previous 7 Days,
 * Previous 30 Days, then one section per month this year and per year
 * before that. Notes keep their order inside each section; sections
 * follow the order of the notes, so they work for newest- and
 * oldest-first lists alike.
 */
export const groupNotesByDate = (notes: NoteData[], field: 'updated_at' | 'created_at', now: Date = new Date()): NoteGroup[] => {
    const today = startOfDay(now);
    const monthFormat = new Intl.DateTimeFormat(undefined, { month: 'long' });

    const labelFor = (note: NoteData): string => {
        if (note.pinned) {
            return 'Pinned';
        }

        const date = new Date(note[field]);
        const days = Math.round((today - startOfDay(date)) / DAY);

        if (days <= 0) {
            return 'Today';
        }
        if (days === 1) {
            return 'Yesterday';
        }
        if (days < 7) {
            return 'Previous 7 Days';
        }
        if (days < 30) {
            return 'Previous 30 Days';
        }

        return date.getFullYear() === now.getFullYear() ? monthFormat.format(date) : String(date.getFullYear());
    };

    const groups: NoteGroup[] = [];

    for (const note of notes) {
        const label = labelFor(note);
        const group = groups.find((existing) => existing.label === label);

        if (group) {
            group.notes.push(note);
        } else {
            groups.push({ label, notes: [note] });
        }
    }

    return groups;
};
