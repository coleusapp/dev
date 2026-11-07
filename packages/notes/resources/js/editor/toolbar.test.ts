// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { TOOLBAR_GROUPS } from '@/editor/toolbar';
import { Editor } from '@tiptap/vue-3';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

let editor: Editor;

beforeEach(() => {
    editor = new Editor({ extensions: editorExtensions() });
});

afterEach(() => editor.destroy());

const action = (id: string) => TOOLBAR_GROUPS.flat().find((each) => each.id === id)!;

describe('toolbar', () => {
    it.each([
        ['heading-1', 'heading'],
        ['heading-2', 'heading'],
        ['heading-3', 'heading'],
        ['heading-4', 'heading'],
        ['heading-5', 'heading'],
        ['heading-6', 'heading'],
        ['bullet-list', 'bulletList'],
        ['ordered-list', 'orderedList'],
        ['task-list', 'taskList'],
        ['blockquote', 'blockquote'],
        ['code-block', 'codeBlock'],
    ])('%s turns the line into a %s and back', (id, nodeType) => {
        editor.commands.setContent('<p>word</p>');
        editor.commands.setTextSelection(2);

        action(id).run(editor);
        expect(editor.state.doc.firstChild?.type.name).toBe(nodeType);
        expect(editor.state.doc.firstChild?.textContent).toBe('word');
        expect(action(id).isActive(editor)).toBe(true);

        action(id).run(editor);
        expect(editor.state.doc.firstChild?.type.name).toBe('paragraph');
        expect(editor.state.doc.firstChild?.textContent).toBe('word');
        expect(action(id).isActive(editor)).toBe(false);
    });

    it.each([
        ['bold', '<p><strong>word</strong></p>'],
        ['italic', '<p><em>word</em></p>'],
        ['strike', '<p><s>word</s></p>'],
        ['code', '<p><code>word</code></p>'],
    ])('%s marks the selection', (id, html) => {
        editor.commands.setContent('<p>word</p>');
        editor.commands.setTextSelection({ from: 1, to: 5 });

        action(id).run(editor);

        expect(editor.getHTML()).toBe(html);
        expect(action(id).isActive(editor)).toBe(true);
    });
});
