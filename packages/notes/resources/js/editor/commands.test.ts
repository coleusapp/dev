// @vitest-environment jsdom
import { prepareFirstLine } from '@/editor/commands';
import { editorExtensions } from '@/editor/extensions';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { Editor } from '@tiptap/vue-3';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

let editor: Editor;

beforeEach(() => {
    editor = new Editor({ extensions: editorExtensions() });
});

afterEach(() => editor.destroy());

describe('prepareFirstLine', () => {
    it('starts an empty note with a title line without saving anything', () => {
        let updates = 0;
        editor.on('update', () => updates++);
        loadMarkdown(editor, '');

        prepareFirstLine(editor, '');

        expect(updates).toBe(0);
        expect(editor.isActive('heading', { level: 1 })).toBe(true);
        editor.commands.insertContent('Trip');
        expect(editorMarkdown(editor)).toBe('# Trip');
    });

    it('adds the title line above text that starts with a blank line', () => {
        loadMarkdown(editor, '\n\nlater');

        prepareFirstLine(editor, '\n\nlater');
        editor.commands.insertContent('Plan');

        expect(editorMarkdown(editor)).toBe('# Plan\n\nlater');
    });

    it('leaves notes that already have a first line alone', () => {
        loadMarkdown(editor, 'Existing');

        prepareFirstLine(editor, 'Existing');

        expect(editorMarkdown(editor)).toBe('Existing');
    });
});

describe('checklists', () => {
    it('ticks an item in place and writes it back as Markdown', () => {
        loadMarkdown(editor, '- [ ] milk\n- [ ] eggs');

        editor.state.doc.descendants((node, position) => {
            if (node.type.name === 'taskItem' && node.textContent === 'milk') {
                editor.view.dispatch(editor.state.tr.setNodeAttribute(position, 'checked', true));
            }
        });

        expect(editorMarkdown(editor)).toBe('- [x] milk\n- [ ] eggs');
    });
});
