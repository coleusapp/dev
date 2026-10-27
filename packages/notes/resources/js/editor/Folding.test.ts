// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { foldKey, toggleFoldMeta } from '@/editor/Folding';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { Editor } from '@tiptap/vue-3';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

let editor: Editor;

beforeEach(() => {
    editor = new Editor({ element: document.createElement('div'), extensions: editorExtensions() });
});

afterEach(() => editor.destroy());

const toggle = (position: number): void => {
    editor.view.dispatch(editor.state.tr.setMeta(foldKey, toggleFoldMeta(position)));
};

const hiddenTexts = (): string[] => Array.from(editor.view.dom.querySelectorAll('.folded-hidden')).map((element) => element.textContent ?? '');

describe('folding in the visual editor', () => {
    it('adds a toggle to headings and to list items with sub-items only', () => {
        loadMarkdown(editor, '# A\n\ntext\n\n- item\n  - sub\n- plain');

        expect(editor.view.dom.querySelectorAll('h1 .fold-toggle')).toHaveLength(1);
        expect(editor.view.dom.querySelectorAll('li .fold-toggle')).toHaveLength(1);
    });

    it('hides a section up to the next heading of the same or higher level', () => {
        loadMarkdown(editor, '# A\n\none\n\n## A1\n\ntwo\n\n# B\n\nthree');

        toggle(0);

        expect(hiddenTexts()).toEqual(['one', 'A1', 'two']);
        expect(editorMarkdown(editor, '')).toBe('# A\n\none\n\n## A1\n\ntwo\n\n# B\n\nthree');

        toggle(0);
        expect(hiddenTexts()).toEqual([]);
    });

    it('keeps a fold on its heading while text above changes', () => {
        loadMarkdown(editor, 'intro\n\n# A\n\nhidden');
        let headingPosition = 0;
        editor.state.doc.forEach((node, offset) => {
            if (node.type.name === 'heading') {
                headingPosition = offset;
            }
        });
        toggle(headingPosition);

        editor.view.dispatch(editor.state.tr.insertText('more ', 1));

        expect(hiddenTexts()).toEqual(['hidden']);
    });

    it('folds a list item, hiding only its sub-items', () => {
        loadMarkdown(editor, '- trip\n  - pack\n- other');
        let itemPosition = -1;
        editor.state.doc.descendants((node, position) => {
            if (itemPosition < 0 && node.type.name === 'listItem') {
                itemPosition = position;
            }
        });

        toggle(itemPosition);

        expect(editor.view.dom.querySelector('li.is-folded')?.textContent).toContain('trip');
        expect(editorMarkdown(editor, '')).toBe('- trip\n  - pack\n- other');
    });
});
