// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { Editor } from '@tiptap/vue-3';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

let editor: Editor;

beforeEach(() => {
    editor = new Editor({ element: document.createElement('div'), extensions: editorExtensions() });
});

afterEach(() => editor.destroy());

/**
 * Types like a user: each character goes through the editor's text-input
 * handlers (auto-pairing, input rules) before falling back to plain insertion.
 */
const type = (text: string): void => {
    for (const character of text) {
        const { from, to } = editor.state.selection;
        const handled = editor.view.someProp('handleTextInput', (handler) => handler(editor.view, from, to, character, () => editor.state.tr));

        if (!handled) {
            editor.view.dispatch(editor.state.tr.insertText(character, from, to));
        }
    }
};

const backspace = (): void => {
    const event = new KeyboardEvent('keydown', { key: 'Backspace' });
    const handled = editor.view.someProp('handleKeyDown', (handler) => handler(editor.view, event));

    if (!handled) {
        const { from } = editor.state.selection;
        editor.view.dispatch(editor.state.tr.delete(from - 1, from));
    }
};

describe('typing in the visual editor', () => {
    it('pairs brackets and steps over the closer', () => {
        loadMarkdown(editor, '');
        editor.commands.setTextSelection(1);

        type('see (here) ok');

        expect(editorMarkdown(editor, '')).toBe('see (here) ok');
    });

    it('turns a typed [[Title]] into a wiki link instead of escaped brackets', () => {
        loadMarkdown(editor, '');
        editor.commands.setTextSelection(1);

        type('go to [[Trip]]');

        expect(editorMarkdown(editor, '')).toBe('go to [[Trip]]');
        expect(editor.getJSON().content?.[0]?.content?.some((node) => node.type === 'wikiLink')).toBe(true);
    });

    it('removes an empty pair with one backspace', () => {
        loadMarkdown(editor, '');
        editor.commands.setTextSelection(1);

        type('a (');
        backspace();

        expect(editorMarkdown(editor, '')).toBe('a');
    });

    it('wraps a selection in brackets', () => {
        loadMarkdown(editor, 'word');
        editor.commands.setTextSelection({ from: 1, to: 5 });

        type('(');

        expect(editorMarkdown(editor, '')).toBe('(word)');
    });
});
