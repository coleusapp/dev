// @vitest-environment jsdom
import { applyParagraphStyle, currentParagraphStyle } from '@/editor/commands';
import { editorExtensions } from '@/editor/extensions';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import type { ParagraphStyle } from '@/lib/markdownFormat';
import { Editor } from '@tiptap/vue-3';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

let editor: Editor;

beforeEach(() => {
    editor = new Editor({ extensions: editorExtensions() });
});

afterEach(() => editor.destroy());

describe('paragraph styles', () => {
    it.each<[ParagraphStyle, string]>([
        ['title', '# Plan'],
        ['heading', '## Plan'],
        ['subheading', '### Plan'],
        ['body', 'Plan'],
        ['monostyled', '```\nPlan\n```'],
    ])('applies %s', (style, markdown) => {
        loadMarkdown(editor, '## Plan');
        editor.commands.setTextSelection(3);

        applyParagraphStyle(editor, style);

        expect(editorMarkdown(editor, '')).toBe(markdown);
        expect(currentParagraphStyle(editor)).toBe(style);
    });

    it('reports no style inside a list', () => {
        loadMarkdown(editor, '- item');
        editor.commands.setTextSelection(3);

        expect(currentParagraphStyle(editor)).toBeNull();
    });
});
