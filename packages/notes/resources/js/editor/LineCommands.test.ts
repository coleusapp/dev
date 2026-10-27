// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { duplicateBlockTransaction, moveBlockTransaction, toggleCommentTransaction } from '@/editor/LineCommands';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { Editor } from '@tiptap/vue-3';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

let editor: Editor;

beforeEach(() => {
    editor = new Editor({ extensions: editorExtensions() });
});

afterEach(() => editor.destroy());

const cursorIn = (text: string, offset = 1): void => {
    editor.state.doc.descendants((node, position) => {
        if (node.isText && node.text?.includes(text)) {
            editor.commands.setTextSelection(position + node.text.indexOf(text) + offset);
        }
    });
};

describe('line commands in the visual editor', () => {
    it('moves a paragraph up and down', () => {
        loadMarkdown(editor, 'a\n\nb\n\nc');
        cursorIn('b');

        editor.view.dispatch(moveBlockTransaction(editor.state, 'up')!);
        expect(editorMarkdown(editor, '')).toBe('b\n\na\n\nc');

        editor.view.dispatch(moveBlockTransaction(editor.state, 'down')!);
        editor.view.dispatch(moveBlockTransaction(editor.state, 'down')!);
        expect(editorMarkdown(editor, '')).toBe('a\n\nc\n\nb');
    });

    it('moves a checklist item within its list, with sub-items', () => {
        loadMarkdown(editor, '- [ ] a\n  - [ ] a1\n- [ ] b');
        cursorIn('b');

        editor.view.dispatch(moveBlockTransaction(editor.state, 'up')!);

        expect(editorMarkdown(editor, '')).toBe('- [ ] b\n- [ ] a\n  - [ ] a1');
    });

    it('stops at the edges', () => {
        loadMarkdown(editor, 'a\n\nb');
        cursorIn('a');

        expect(moveBlockTransaction(editor.state, 'up')).toBeNull();
    });

    it('duplicates the current line', () => {
        loadMarkdown(editor, '- milk\n- eggs');
        cursorIn('milk');

        editor.view.dispatch(duplicateBlockTransaction(editor.state)!);

        expect(editorMarkdown(editor, '')).toBe('- milk\n- milk\n- eggs');
    });

    it('comments and uncomments the current paragraph', () => {
        loadMarkdown(editor, 'secret plan');
        cursorIn('plan');

        editor.view.dispatch(toggleCommentTransaction(editor.state));
        expect(editorMarkdown(editor, '')).toBe('%%secret plan%%');

        editor.view.dispatch(toggleCommentTransaction(editor.state));
        expect(editorMarkdown(editor, '')).toBe('secret plan');
    });
});
