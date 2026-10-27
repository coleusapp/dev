import { formatTable, insertTable, moveToCell } from '@/lib/tables';
import { describe, expect, it } from 'vitest';

describe('insertTable', () => {
    it('inserts a table on its own lines with the first header selected', () => {
        const edit = insertTable({ text: 'intro', start: 5, end: 5 });
        const result = 'intro'.slice(0, edit.from) + edit.insert;

        expect(result).toBe('intro\n\n| Column 1 | Column 2 | Column 3 |\n| --- | --- | --- |\n|  |  |  |');
        expect(result.slice(edit.selectionStart, edit.selectionEnd)).toBe('Column 1');
    });
});

describe('formatTable', () => {
    it('lines up the pipes, keeping alignment colons', () => {
        const text = '| a | longer |\n|:-|-:|\n| looooong | b |';
        const edit = formatTable({ text, start: 2, end: 2 });

        expect(edit?.insert).toBe('| a        | longer |\n| :------- | -----: |\n| looooong | b      |');
    });

    it('fills missing cells and keeps text around the table', () => {
        const text = 'before\n| a | b |\n| --- | --- |\n| 1 |\nafter';
        const edit = formatTable({ text, start: 10, end: 10 })!;

        expect(text.slice(0, edit.from) + edit.insert + text.slice(edit.to)).toBe('before\n| a   | b   |\n| --- | --- |\n| 1   |     |\nafter');
    });

    it('ignores text that is not a table', () => {
        expect(formatTable({ text: '| just pipes |', start: 2, end: 2 })).toBeNull();
        expect(formatTable({ text: 'plain', start: 2, end: 2 })).toBeNull();
    });
});

describe('moveToCell', () => {
    const row = '| aa | bb | cc |';

    it('moves to the next and previous cell', () => {
        expect(moveToCell({ text: row, start: 3, end: 3 }, false)?.selectionStart).toBe(7);
        expect(moveToCell({ text: row, start: 8, end: 8 }, true)?.selectionStart).toBe(2);
    });

    it('stops at the last cell and outside tables', () => {
        expect(moveToCell({ text: row, start: 13, end: 13 }, false)).toBeNull();
        expect(moveToCell({ text: 'no table', start: 2, end: 2 }, false)).toBeNull();
    });
});
