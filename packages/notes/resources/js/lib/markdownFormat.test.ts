import {
    applyParagraphStyle,
    continueList,
    formatMarkdown,
    indentListLines,
    toggleHighlight,
    type FormatCommand,
    type ParagraphStyle,
} from '@/lib/markdownFormat';
import { describe, expect, it } from 'vitest';

const apply = (command: FormatCommand, text: string, start: number, end: number): [string, string] => {
    const edit = formatMarkdown(command, { text, start, end });
    const result = text.slice(0, edit.from) + edit.insert + text.slice(edit.to);

    return [result, result.slice(edit.selectionStart, edit.selectionEnd)];
};

describe('formatMarkdown', () => {
    it.each<[FormatCommand, string, number, number, string, string]>([
        ['bold', 'hello world', 0, 5, '**hello** world', 'hello'],
        ['bold', '**hello** world', 2, 7, 'hello world', 'hello'],
        ['bold', '**hello** world', 0, 9, 'hello world', 'hello'],
        ['italic', 'ab', 1, 1, 'a__b', ''],
        ['underline', 'a b', 2, 3, 'a ++b++', 'b'],
        ['strike', 'a b', 0, 1, '~~a~~ b', 'a'],
        ['code', 'x a\nb y', 2, 5, 'x \n```\na\nb\n```\n y', 'a\nb'],
        ['code', 'run cmd', 4, 7, 'run `cmd`', 'cmd'],
        ['heading', 'Title\nbody', 2, 2, '## Title\nbody', ''],
        ['heading', '## Title\nbody', 4, 4, 'Title\nbody', ''],
        ['list', 'a\nb\nc', 0, 3, '- a\n- b\nc', '- a\n- b'],
        ['list', '- a\n- b', 0, 7, 'a\nb', 'a\nb'],
        ['checklist', '- a', 2, 2, '- [ ] a', ''],
        ['list', '- [ ] a', 6, 6, '- a', ''],
        ['numbered', 'a\nb\n\nc', 0, 6, '1. a\n2. b\n\n3. c', '1. a\n2. b\n\n3. c'],
        ['numbered', '1. a\n2. b', 0, 9, 'a\nb', 'a\nb'],
        ['numbered', '- a', 1, 1, '1. a', ''],
        ['quote', 'a\n- b', 0, 5, '> a\n> - b', '> a\n> - b'],
        ['quote', '> a\n> b', 0, 7, 'a\nb', 'a\nb'],
        ['link', 'see docs', 4, 8, 'see [docs](url)', 'url'],
        ['link', 'https://x.test', 0, 14, '[](https://x.test)', ''],
        ['link', '', 0, 0, '[link](url)', 'url'],
    ])('%s on %j (%i-%i)', (command, text, start, end, expected, selected) => {
        expect(apply(command, text, start, end)).toEqual([expected, selected]);
    });
});

describe('applyParagraphStyle', () => {
    const style = (paragraphStyle: ParagraphStyle, text: string, start: number, end: number): string => {
        const edit = applyParagraphStyle(paragraphStyle, { text, start, end });

        return text.slice(0, edit.from) + edit.insert + text.slice(edit.to);
    };

    it.each<[ParagraphStyle, string, string]>([
        ['title', 'Plan', '# Plan'],
        ['heading', '# Plan', '## Plan'],
        ['subheading', 'Plan', '### Plan'],
        ['body', '### Plan', 'Plan'],
        ['title', '- item', '# item'],
        ['body', '- [ ] task', 'task'],
    ])('%s turns %j into %j', (paragraphStyle, text, expected) => {
        expect(style(paragraphStyle, text, 1, 1)).toBe(expected);
    });

    it('styles every selected line and skips blank ones', () => {
        expect(style('heading', 'a\n\nb', 0, 4)).toBe('## a\n\n## b');
    });

    it('fences monostyled text', () => {
        expect(style('monostyled', 'code', 0, 4)).toBe('```\ncode\n```\n');
    });
});

describe('toggleHighlight', () => {
    const highlight = (text: string, start: number, end: number, color: string | null): string => {
        const edit = toggleHighlight({ text, start, end }, color);

        return text.slice(0, edit.from) + edit.insert + text.slice(edit.to);
    };

    it('highlights with and without a colour', () => {
        expect(highlight('a hot b', 2, 5, null)).toBe('a ==hot== b');
        expect(highlight('a hot b', 2, 5, 'pink')).toBe('a ==hot=={pink} b');
    });

    it('removes a highlight and its colour', () => {
        expect(highlight('a ==hot=={pink} b', 4, 7, null)).toBe('a hot b');
        expect(highlight('a ==hot== b', 4, 7, 'mint')).toBe('a hot b');
    });
});

describe('checklist on several lines', () => {
    const apply = (text: string, start: number, end: number): string => {
        const edit = formatMarkdown('checklist', { text, start, end });

        return text.slice(0, edit.from) + edit.insert + text.slice(edit.to);
    };

    it('converts each selected line, skipping blank ones, and toggles back', () => {
        const checklist = apply('milk\n\neggs\n- bread', 0, 17);

        expect(checklist).toBe('- [ ] milk\n\n- [ ] eggs\n- [ ] bread');
        expect(apply(checklist, 0, checklist.length)).toBe('milk\n\neggs\nbread');
    });
});

describe('indentListLines', () => {
    const indent = (text: string, start: number, end: number, outdent = false): string | null => {
        const edit = indentListLines({ text, start, end }, outdent);

        return edit ? text.slice(0, edit.from) + edit.insert + text.slice(edit.to) : null;
    };

    it('nests a checklist item under the one above', () => {
        expect(indent('- [ ] trip\n- [ ] pack', 15, 15)).toBe('- [ ] trip\n  - [ ] pack');
    });

    it('outdents back', () => {
        expect(indent('- [ ] trip\n  - [ ] pack', 17, 17, true)).toBe('- [ ] trip\n- [ ] pack');
    });

    it('indents every selected list line but not plain lines', () => {
        expect(indent('- a\ntext\n1. b', 0, 13)).toBe('  - a\ntext\n  1. b');
    });

    it('does nothing outside lists', () => {
        expect(indent('plain', 2, 2)).toBeNull();
    });
});

describe('continueList', () => {
    const enter = (text: string, cursor = text.length): string | null => {
        const edit = continueList({ text, start: cursor, end: cursor });

        return edit ? text.slice(0, edit.from) + edit.insert + text.slice(edit.to) + `|${edit.selectionStart}` : null;
    };

    it.each([
        ['bullet', '- milk', '- milk\n- |9'],
        ['star bullet', '* milk', '* milk\n* |9'],
        ['numbered', '1. one', '1. one\n2. |10'],
        ['numbered with paren', '9) nine', '9) nine\n10) |12'],
        ['checklist (new item unticked)', '- [x] done', '- [x] done\n- [ ] |17'],
        ['indented sub-item', '  - [ ] sub', '  - [ ] sub\n  - [ ] |20'],
        ['quote', '> said', '> said\n> |9'],
        ['quoted list', '> - item', '> - item\n> - |13'],
    ])('continues a %s', (_, text, expected) => {
        expect(enter(text)).toBe(expected);
    });

    it('splits an item at the cursor', () => {
        expect(enter('- milk eggs', 7)).toBe('- milk \n- eggs|10');
    });

    it('ends the list on an empty item, keeping quote nesting', () => {
        expect(enter('- a\n- ')).toBe('- a\n|4');
        expect(enter('- a\n- [ ] ')).toBe('- a\n|4');
        expect(enter('> - ')).toBe('> |2');
        expect(enter('> ')).toBe('|0');
    });

    it('leaves Enter alone elsewhere', () => {
        expect(enter('plain text')).toBeNull();
        expect(enter('- item', 1)).toBeNull();
        expect(continueList({ text: '- item', start: 2, end: 4 })).toBeNull();
    });
});
