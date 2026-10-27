// @vitest-environment jsdom
import { checkedToBottomTransaction, deleteCheckedTransaction, setAllCheckedTransaction } from '@/editor/checklists';
import { editorExtensions } from '@/editor/extensions';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { Editor } from '@tiptap/vue-3';
import { afterEach, describe, expect, it } from 'vitest';

let editor: Editor;

afterEach(() => editor.destroy());

const tick = (text: string): void => {
    editor.state.doc.descendants((node, position) => {
        if (node.type.name === 'taskItem' && node.firstChild?.textContent === text) {
            editor.commands.command(({ tr }) => {
                tr.setNodeAttribute(position, 'checked', true);
                return true;
            });
        }
    });
};

describe('moving checked items to the bottom', () => {
    it('sorts on demand, keeping order within each group and sub-items with their parent', () => {
        editor = new Editor({ extensions: editorExtensions() });
        loadMarkdown(editor, '- [x] a\n  - [ ] a1\n- [ ] b\n- [x] c\n- [ ] d');

        const tr = checkedToBottomTransaction(editor.state);
        editor.view.dispatch(tr!);

        expect(editorMarkdown(editor, '')).toBe('- [ ] b\n- [ ] d\n- [x] a\n  - [ ] a1\n- [x] c');
    });

    it('sorts nested lists independently', () => {
        editor = new Editor({ extensions: editorExtensions() });
        loadMarkdown(editor, '- [ ] trip\n  - [x] tickets\n  - [ ] pack');

        editor.view.dispatch(checkedToBottomTransaction(editor.state)!);

        expect(editorMarkdown(editor, '')).toBe('- [ ] trip\n  - [ ] pack\n  - [x] tickets');
    });

    it('returns nothing when the lists are already sorted', () => {
        editor = new Editor({ extensions: editorExtensions() });
        loadMarkdown(editor, '- [ ] a\n- [x] b');

        expect(checkedToBottomTransaction(editor.state)).toBeNull();
    });

    it('moves an item down as soon as it is ticked when the setting is on', () => {
        editor = new Editor({ extensions: editorExtensions({ checkedToBottom: () => true }) });
        loadMarkdown(editor, '- [ ] milk\n- [ ] eggs\n- [ ] bread');

        tick('milk');

        expect(editorMarkdown(editor, '')).toBe('- [ ] eggs\n- [ ] bread\n- [x] milk');
    });

    it('leaves ticked items in place when the setting is off', () => {
        editor = new Editor({ extensions: editorExtensions() });
        loadMarkdown(editor, '- [ ] milk\n- [ ] eggs');

        tick('milk');

        expect(editorMarkdown(editor, '')).toBe('- [x] milk\n- [ ] eggs');
    });

    it('does not reorder when the note is just loaded', () => {
        editor = new Editor({ extensions: editorExtensions({ checkedToBottom: () => true }) });

        loadMarkdown(editor, '- [x] done\n- [ ] todo');

        expect(editorMarkdown(editor, '')).toBe('- [x] done\n- [ ] todo');
    });
});

describe('check all, uncheck all, delete checked', () => {
    const placeCursorIn = (text: string): void => {
        editor.state.doc.descendants((node, position) => {
            if (node.isText && node.text === text) {
                editor.commands.setTextSelection(position + 1);
            }
        });
    };

    it('checks and unchecks every item of the checklist at the cursor, sub-items included', () => {
        editor = new Editor({ extensions: editorExtensions() });
        loadMarkdown(editor, '- [ ] a\n  - [ ] a1\n- [x] b\n\ntext\n\n- [ ] other');
        placeCursorIn('a');

        editor.view.dispatch(setAllCheckedTransaction(editor.state, true)!);
        expect(editorMarkdown(editor, '')).toBe('- [x] a\n  - [x] a1\n- [x] b\n\ntext\n\n- [ ] other');

        editor.view.dispatch(setAllCheckedTransaction(editor.state, false)!);
        expect(editorMarkdown(editor, '')).toBe('- [ ] a\n  - [ ] a1\n- [ ] b\n\ntext\n\n- [ ] other');
    });

    it('applies to every checklist when the cursor is outside one', () => {
        editor = new Editor({ extensions: editorExtensions() });
        loadMarkdown(editor, '- [ ] a\n\ntext\n\n- [ ] b');
        placeCursorIn('text');

        editor.view.dispatch(setAllCheckedTransaction(editor.state, true)!);

        expect(editorMarkdown(editor, '')).toBe('- [x] a\n\ntext\n\n- [x] b');
    });

    it('deletes ticked items with their sub-items and removes emptied lists', () => {
        editor = new Editor({ extensions: editorExtensions() });
        loadMarkdown(editor, '- [x] a\n  - [ ] a1\n- [ ] b\n- [x] c\n\ntext\n\n- [x] gone');
        placeCursorIn('text');

        editor.view.dispatch(deleteCheckedTransaction(editor.state)!);

        expect(editorMarkdown(editor, '')).toBe('- [ ] b\n\ntext');
    });

    it('returns nothing when there is nothing to change', () => {
        editor = new Editor({ extensions: editorExtensions() });
        loadMarkdown(editor, '- [x] a');

        expect(setAllCheckedTransaction(editor.state, true)).toBeNull();
        loadMarkdown(editor, '- [ ] a');
        expect(deleteCheckedTransaction(editor.state)).toBeNull();
    });
});
