// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { SlashMenu, type SlashState } from '@/editor/SlashMenu';
import { Editor } from '@tiptap/vue-3';
import { afterEach, describe, expect, it } from 'vitest';

let editor: Editor;
let state: SlashState | null = null;

const create = (onKey: (key: string) => boolean = () => false): void => {
    editor = new Editor({
        element: document.createElement('div'),
        extensions: [...editorExtensions(), SlashMenu.configure({ onState: (next) => (state = next), onKey })],
    });
};

afterEach(() => {
    editor.destroy();
    state = null;
});

const typeText = (text: string): void => {
    editor.view.dispatch(editor.state.tr.insertText(text));
};

describe('slash menu trigger', () => {
    it('opens on "/" at the start of a line and follows the query', () => {
        create();
        loadMarkdown(editor, '');
        editor.commands.setTextSelection(1);

        typeText('/');
        expect(state?.query).toBe('');

        typeText('che');
        expect(state).toMatchObject({ query: 'che', range: { from: 1, to: 5 } });
    });

    it('does not open inside words or code', () => {
        create();
        loadMarkdown(editor, 'path');
        editor.commands.setTextSelection(5);
        typeText('/to');
        expect(state).toBeNull();

        loadMarkdown(editor, '```\ncode\n```');
        editor.commands.setTextSelection(2);
        typeText('/x');
        expect(state).toBeNull();
    });

    it('closes on Escape and stays closed for that "/"', () => {
        create(() => true);
        loadMarkdown(editor, '');
        editor.commands.setTextSelection(1);
        typeText('/he');

        editor.view.someProp('handleKeyDown', (handler) => handler(editor.view, new KeyboardEvent('keydown', { key: 'Escape' })));
        expect(state).toBeNull();

        typeText('a');
        expect(state).toBeNull();
    });

    it('lets the menu take arrow keys and Enter while open', () => {
        const keys: string[] = [];
        create((key) => {
            keys.push(key);
            return true;
        });
        loadMarkdown(editor, '');
        editor.commands.setTextSelection(1);
        typeText('/');

        editor.view.someProp('handleKeyDown', (handler) => handler(editor.view, new KeyboardEvent('keydown', { key: 'ArrowDown' })));
        editor.view.someProp('handleKeyDown', (handler) => handler(editor.view, new KeyboardEvent('keydown', { key: 'Enter' })));

        expect(keys).toEqual(['ArrowDown', 'Enter']);
    });

    it('keeps the text when the "/query" is replaced by a command', () => {
        create();
        loadMarkdown(editor, '');
        editor.commands.setTextSelection(1);
        typeText('/check');

        editor.chain().deleteRange(state!.range).toggleTaskList().insertContent('milk').run();

        expect(editorMarkdown(editor, '')).toBe('- [ ] milk');
    });
});
