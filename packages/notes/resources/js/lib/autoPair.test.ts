import { autoPair, deletePair } from '@/lib/autoPair';
import { describe, expect, it } from 'vitest';

const type = (text: string, start: number, end: number, typed: string): string | null => {
    const edit = autoPair({ text, start, end }, typed);

    if (!edit) {
        return null;
    }

    const result = text.slice(0, edit.from) + edit.insert + text.slice(edit.to);

    return `${result.slice(0, edit.selectionStart)}|${result.slice(edit.selectionStart)}`;
};

describe('autoPair', () => {
    it.each([
        ['bracket', 'a ', 2, '[', 'a [|]'],
        ['paren before space', 'a  b', 2, '(', 'a (|) b'],
        ['brace at end', '', 0, '{', '{|}'],
        ['backtick', 'run ', 4, '`', 'run `|`'],
        ['quote after space', 'say ', 4, '"', 'say "|"'],
        ['wiki link from inside a pair', '[]', 1, '[', '[[|]]'],
    ])('pairs a %s', (_, text, cursor, typed, expected) => {
        expect(type(text, cursor, cursor, typed)).toBe(expected);
    });

    it.each([
        ['before a word', 'word', 0, '('],
        ['quote after a letter', 'it', 2, '"'],
        ['lone asterisk', '', 0, '*'],
        ['letters', 'a', 1, 'b'],
    ])('does not pair %s', (_, text, cursor, typed) => {
        expect(type(text, cursor, cursor, typed)).toBeNull();
    });

    it('steps over a closing character that is already there', () => {
        expect(type('(x)', 2, 2, ')')).toBe('(x)|');
        expect(type('[[a]]', 3, 3, ']')).toBe('[[a]|]');
    });

    it.each([
        ['*', '*|word*'],
        ['_', '_|word_'],
        ['`', '`|word`'],
        ['[', '[|word]'],
        ['=', '=|word='],
    ])('wraps a selection with %s, keeping it selected', (typed, expected) => {
        expect(type('word', 0, 4, typed)).toBe(expected);
    });
});

describe('deletePair', () => {
    it('removes an empty pair', () => {
        const edit = deletePair({ text: 'a ()', start: 3, end: 3 });

        expect(edit).toEqual({ from: 2, to: 4, insert: '', selectionStart: 2, selectionEnd: 2 });
    });

    it('leaves other backspaces alone', () => {
        expect(deletePair({ text: '(x)', start: 2, end: 2 })).toBeNull();
        expect(deletePair({ text: '()', start: 0, end: 0 })).toBeNull();
        expect(deletePair({ text: '()', start: 1, end: 2 })).toBeNull();
    });
});
