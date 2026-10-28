// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { loadMarkdown } from '@/editor/markdown';
import { TagMenu, type TagMenuState } from '@/editor/TagMenu';
import { tagQuery } from '@/editor/tagQuery';
import type { TypeaheadState } from '@/editor/Typeahead';
import { Editor } from '@tiptap/vue-3';
import { describe, expect, it } from 'vitest';

describe('tagQuery', () => {
    it('follows a #tag where a tag can start', () => {
        expect(tagQuery('#')).toEqual({ trigger: '#', query: '', before: 1, after: 0 });
        expect(tagQuery('plan #work/me')).toEqual({ trigger: '#', query: 'work/me', before: 8, after: 0 });
        expect(tagQuery('(#to-do_2')).toMatchObject({ query: 'to-do_2' });
    });

    it('ignores headings, mid-word "#", anchors and links', () => {
        expect(tagQuery('# ')).toBeNull();
        expect(tagQuery('a#b')).toBeNull();
        expect(tagQuery('page/#anchor')).toBeNull();
        expect(tagQuery('##')).toBeNull();
        expect(tagQuery('[[#Head')).toBeNull();
        expect(tagQuery('>>#Head')).toBeNull();
        expect(tagQuery('#work later')).toBeNull();
    });
});

describe('tag menu trigger', () => {
    it('reports the typed tag in the visual editor, not in code', () => {
        let state: TypeaheadState | null = null;
        const editor = new Editor({
            element: document.createElement('div'),
            extensions: [...editorExtensions(), TagMenu.configure({ onState: (next: TypeaheadState | null) => (state = next) })],
        });

        loadMarkdown(editor, 'plan ');
        editor.commands.setTextSelection(6);
        editor.view.dispatch(editor.state.tr.insertText('#wo'));
        expect(state as TagMenuState | null).toMatchObject({ trigger: '#', query: 'wo', range: { from: 6, to: 9 } });

        loadMarkdown(editor, 'see `a b` there');
        editor.commands.setTextSelection(7);
        editor.view.dispatch(editor.state.tr.insertText('#x'));
        expect(state).toBeNull();

        editor.destroy();
    });
});
