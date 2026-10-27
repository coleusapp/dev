import { duplicateLines, moveLines, toggleComment, type TextEdit } from '@/lib/markdownFormat';
import { describe, expect, it } from 'vitest';

const result = (text: string, edit: TextEdit | null): string | null =>
    edit ? `${(text.slice(0, edit.from) + edit.insert + text.slice(edit.to)).slice(0)}|${edit.selectionStart}-${edit.selectionEnd}` : null;

describe('moveLines', () => {
    it('moves the current line up and down, keeping the cursor on it', () => {
        expect(result('a\nb\nc', moveLines({ text: 'a\nb\nc', start: 3, end: 3 }, 'up'))).toBe('b\na\nc|1-1');
        expect(result('a\nb\nc', moveLines({ text: 'a\nb\nc', start: 3, end: 3 }, 'down'))).toBe('a\nc\nb|5-5');
    });

    it('moves several selected lines together', () => {
        expect(result('a\nb\nc\nd', moveLines({ text: 'a\nb\nc\nd', start: 2, end: 5 }, 'down'))).toBe('a\nd\nb\nc|4-7');
    });

    it('stops at the top and bottom', () => {
        expect(moveLines({ text: 'a\nb', start: 0, end: 0 }, 'up')).toBeNull();
        expect(moveLines({ text: 'a\nb', start: 2, end: 2 }, 'down')).toBeNull();
    });
});

describe('duplicateLines', () => {
    it('copies the current line below and moves the cursor onto the copy', () => {
        expect(result('a\nb', duplicateLines({ text: 'a\nb', start: 1, end: 1 }))).toBe('a\na\nb|3-3');
    });

    it('copies a multi-line selection as a block', () => {
        expect(result('a\nb\nc', duplicateLines({ text: 'a\nb\nc', start: 0, end: 3 }))).toBe('a\nb\na\nb\nc|4-7');
    });
});

describe('toggleComment', () => {
    it('comments the current line, or the selection', () => {
        expect(result('note\nnext', toggleComment({ text: 'note\nnext', start: 2, end: 2 }))).toBe('%%note%%\nnext|0-8');
        expect(result('a secret b', toggleComment({ text: 'a secret b', start: 2, end: 8 }))).toBe('a %%secret%% b|2-12');
    });

    it('uncomments a commented line or selection', () => {
        expect(result('%%note%%', toggleComment({ text: '%%note%%', start: 3, end: 3 }))).toBe('note|0-4');
        expect(result('a %%secret%% b', toggleComment({ text: 'a %%secret%% b', start: 4, end: 10 }))).toBe('a secret b|2-8');
    });
});
