// @vitest-environment jsdom
import { prepareFirstLine } from '@/editor/commands';
import { editorExtensions } from '@/editor/extensions';
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

        prepareFirstLine(editor);

        expect(updates).toBe(0);
        expect(editor.isActive('heading', { level: 1 })).toBe(true);
        editor.commands.insertContent('Trip');
        expect(editor.getHTML()).toBe('<h1>Trip</h1><p></p>');
    });

    it('turns an empty first paragraph into the title line', () => {
        editor.commands.setContent('<p></p><p>later</p>');

        prepareFirstLine(editor);
        editor.commands.insertContent('Plan');

        expect(editor.getHTML()).toBe('<h1>Plan</h1><p>later</p>');
    });

    it('leaves notes that already have a first line alone', () => {
        editor.commands.setContent('<p>Existing</p>');

        prepareFirstLine(editor);

        expect(editor.getHTML()).toBe('<p>Existing</p>');
    });
});

describe('checklists', () => {
    it('ticks an item in place', () => {
        editor.commands.setContent(
            '<ul data-type="taskList"><li data-type="taskItem" data-checked="false"><p>milk</p></li><li data-type="taskItem" data-checked="false"><p>eggs</p></li></ul>',
        );

        editor.state.doc.descendants((node, position) => {
            if (node.type.name === 'taskItem' && node.textContent === 'milk') {
                editor.view.dispatch(editor.state.tr.setNodeAttribute(position, 'checked', true));
            }
        });

        const checked: boolean[] = [];
        editor.state.doc.descendants((node) => {
            if (node.type.name === 'taskItem') {
                checked.push(node.attrs.checked);
            }
        });

        expect(checked).toEqual([true, false]);
    });
});
