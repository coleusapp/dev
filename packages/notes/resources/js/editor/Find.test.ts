// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { findState, replaceCurrent, replaceEvery, setFind, stepFind } from '@/editor/Find';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { findPattern } from '@/lib/find';
import { Editor } from '@tiptap/vue-3';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

let editor: Editor;

beforeEach(() => {
    editor = new Editor({ element: document.createElement('div'), extensions: editorExtensions() });
});

afterEach(() => editor.destroy());

const pattern = (query: string, regex = false): RegExp => findPattern(query, { caseSensitive: false, regex }) as RegExp;

describe('find in the visual editor', () => {
    it('finds matches across blocks, past wiki-link chips, and steps through them', () => {
        loadMarkdown(editor, '# Plan\n\nsee [[Ideas]] plan\n\n- plan b');

        setFind(editor, pattern('plan'));
        expect(findState(editor).matches).toHaveLength(3);
        expect(editor.state.doc.textBetween(editor.state.selection.from, editor.state.selection.to)).toBe('Plan');

        stepFind(editor, 1);
        expect(findState(editor).current).toBe(1);
        stepFind(editor, -1);
        stepFind(editor, -1);
        expect(findState(editor).current).toBe(2);
    });

    it('highlights matches with decorations', () => {
        loadMarkdown(editor, 'a plan and a plan');
        setFind(editor, pattern('plan'));

        expect(editor.view.dom.querySelectorAll('.find-match')).toHaveLength(2);
        expect(editor.view.dom.querySelectorAll('.find-current')).toHaveLength(1);
    });

    it('replaces the current match and then all, keeping formatting around', () => {
        loadMarkdown(editor, '**cat** and cat and cat');
        setFind(editor, pattern('cat'));

        replaceCurrent(editor, 'dog', false);
        expect(editorMarkdown(editor, '')).toBe('**dog** and cat and cat');

        expect(replaceEvery(editor, 'cow', false)).toBe(2);
        expect(editorMarkdown(editor, '')).toBe('**dog** and cow and cow');
    });

    it('expands regex groups when replacing', () => {
        loadMarkdown(editor, 'on 2026-01-05');
        setFind(editor, pattern('(\\d+)-(\\d+)-(\\d+)', true));

        replaceEvery(editor, '$3/$2/$1', true);

        expect(editorMarkdown(editor, '')).toBe('on 05/01/2026');
    });

    it('clears the search', () => {
        loadMarkdown(editor, 'plan');
        setFind(editor, pattern('plan'));
        setFind(editor, null);

        expect(findState(editor).matches).toEqual([]);
    });
});
