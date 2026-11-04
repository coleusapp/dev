// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
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
        ['heading-1', '# word'],
        ['heading-2', '## word'],
        ['heading-3', '### word'],
        ['bullet-list', '- word'],
        ['ordered-list', '1. word'],
        ['task-list', '- [ ] word'],
        ['blockquote', '> word'],
        ['code-block', '```\nword\n```'],
    ])('%s turns the line into Markdown block and back', (id, markdown) => {
        loadMarkdown(editor, 'word');
        editor.commands.setTextSelection(2);

        action(id).run(editor);
        expect(editorMarkdown(editor)).toBe(markdown);
        expect(action(id).isActive(editor)).toBe(true);

        action(id).run(editor);
        expect(editorMarkdown(editor)).toBe('word');
        expect(action(id).isActive(editor)).toBe(false);
    });

    it.each([
        ['bold', '**word**'],
        ['italic', '*word*'],
        ['strike', '~~word~~'],
        ['code', '`word`'],
    ])('%s marks the selection', (id, markdown) => {
        loadMarkdown(editor, 'word');
        editor.commands.setTextSelection({ from: 1, to: 5 });

        action(id).run(editor);

        expect(editorMarkdown(editor)).toBe(markdown);
        expect(action(id).isActive(editor)).toBe(true);
    });
});
