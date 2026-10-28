// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { LinkMenu, type LinkMenuState } from '@/editor/LinkMenu';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { Editor } from '@tiptap/vue-3';
import { afterEach, describe, expect, it } from 'vitest';

let editor: Editor;
let state: LinkMenuState | null = null;

const create = (onKey: (key: string) => boolean = () => false): void => {
    editor = new Editor({
        element: document.createElement('div'),
        extensions: [...editorExtensions(), LinkMenu.configure({ onState: (next) => (state = next), onKey })],
    });
};

afterEach(() => {
    editor.destroy();
    state = null;
});

const typeText = (text: string): void => {
    editor.view.dispatch(editor.state.tr.insertText(text));
};

describe('link menu trigger', () => {
    it('opens on "[[" and follows the query, covering an auto-paired "]]"', () => {
        create();
        loadMarkdown(editor, 'see ');
        editor.commands.setTextSelection(5);

        typeText('[[');
        expect(state).toMatchObject({ trigger: '[[', query: '', range: { from: 5, to: 7 } });

        typeText('Tri');
        const cursor = editor.state.selection.from;
        editor.view.dispatch(editor.state.tr.insertText(']]', cursor));
        editor.commands.setTextSelection(cursor);
        expect(state).toMatchObject({ query: 'Tri', range: { from: 5, to: cursor + 2 } });
    });

    it('opens on ">>" after a space, not inside code', () => {
        create();
        loadMarkdown(editor, 'call');
        editor.commands.setTextSelection(5);
        typeText(' >>Lis');
        expect(state).toMatchObject({ trigger: '>>', query: 'Lis', range: { from: 6, to: 11 } });

        loadMarkdown(editor, '```\ncode\n```');
        editor.commands.setTextSelection(2);
        typeText('[[x');
        expect(state).toBeNull();
    });

    it('closes on Escape until the trigger is retyped, and routes keys while open', () => {
        const keys: string[] = [];
        create((key) => keys.push(key) > 0);
        loadMarkdown(editor, '');
        editor.commands.setTextSelection(1);
        typeText('[[Tr');

        const press = (key: string) =>
            editor.view.someProp('handleKeyDown', (handler) => handler(editor.view, new KeyboardEvent('keydown', { key })));

        expect(press('ArrowDown')).toBe(true);
        expect(keys).toEqual(['ArrowDown']);

        press('Escape');
        expect(state).toBeNull();
        typeText('i');
        expect(state).toBeNull();
    });

    it('turns the typed range into a wiki link chip', () => {
        create();
        loadMarkdown(editor, 'see ');
        editor.commands.setTextSelection(5);
        typeText('[[Tri');

        editor
            .chain()
            .deleteRange(state!.range)
            .insertContent({ type: 'wikiLink', attrs: { title: 'Trip#Day one' } })
            .run();

        expect(editorMarkdown(editor, '')).toBe('see [[Trip#Day one]]');
    });
});
