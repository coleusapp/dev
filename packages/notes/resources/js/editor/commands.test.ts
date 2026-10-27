// @vitest-environment jsdom
import { applyParagraphStyle, currentParagraphStyle, prepareFirstLine } from '@/editor/commands';
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

describe('toolbar commands', () => {
    it('toggles numbered lists and quotes', () => {
        loadMarkdown(editor, 'one');
        editor.commands.setTextSelection(2);
        editor.chain().toggleOrderedList().run();
        expect(editorMarkdown(editor, '')).toBe('1. one');

        editor.chain().toggleOrderedList().toggleBlockquote().run();
        expect(editorMarkdown(editor, '')).toBe('> one');
    });
});

describe('prepareFirstLine', () => {
    it('starts an empty note with a title line without saving anything', () => {
        let updates = 0;
        editor.on('update', () => updates++);
        loadMarkdown(editor, '');

        prepareFirstLine(editor, '', 'title');

        expect(updates).toBe(0);
        expect(currentParagraphStyle(editor)).toBe('title');
        editor.commands.insertContent('Trip');
        expect(editorMarkdown(editor, '')).toBe('# Trip');
    });

    it('adds the styled line above the tag of a note started from a tag', () => {
        loadMarkdown(editor, '\n\n#work');

        prepareFirstLine(editor, '\n\n#work', 'heading');
        editor.commands.insertContent('Plan');

        expect(editorMarkdown(editor, '')).toBe('## Plan\n\n#work');
    });

    it('leaves notes that already have a first line alone', () => {
        loadMarkdown(editor, 'Existing');

        prepareFirstLine(editor, 'Existing', 'title');

        expect(editorMarkdown(editor, '')).toBe('Existing');
    });

    it('treats a leftover empty heading as faithful', () => {
        expect(loadMarkdown(editor, '#').faithful).toBe(true);
    });
});

describe('checklists in the visual editor', () => {
    it('ticks and unticks an item in place and writes it back as Markdown', () => {
        loadMarkdown(editor, '- [ ] milk\n- [ ] eggs');

        let itemPosition = -1;
        editor.state.doc.descendants((node, position) => {
            if (itemPosition < 0 && node.type.name === 'taskItem' && node.textContent === 'eggs') {
                itemPosition = position;
            }
        });

        editor.commands.command(({ tr }) => {
            tr.setNodeAttribute(itemPosition, 'checked', true);
            return true;
        });
        expect(editorMarkdown(editor, '')).toBe('- [ ] milk\n- [x] eggs');

        editor.commands.command(({ tr }) => {
            tr.setNodeAttribute(itemPosition, 'checked', false);
            return true;
        });
        expect(editorMarkdown(editor, '')).toBe('- [ ] milk\n- [ ] eggs');
    });

    it('renders done items with data-checked for the strike-through style', () => {
        loadMarkdown(editor, '- [x] done');

        expect(editor.view.dom.querySelector('li[data-checked="true"]')?.textContent).toContain('done');
    });
});

describe('checklist button', () => {
    /**
     * Selects from the start of the first text to the end of the last, like a user dragging over the lines.
     */
    const selectAll = (): void => {
        const textPositions: number[] = [];
        editor.state.doc.descendants((node, position) => {
            if (node.isText) {
                textPositions.push(position, position + node.nodeSize);
            }
        });
        editor.commands.setTextSelection({ from: Math.min(...textPositions), to: Math.max(...textPositions) });
    };

    it('turns every selected line into a checklist item and back', () => {
        loadMarkdown(editor, 'milk\n\neggs\n\nbread');
        selectAll();

        editor.chain().toggleTaskList().run();
        expect(editorMarkdown(editor, '')).toBe('- [ ] milk\n- [ ] eggs\n- [ ] bread');

        selectAll();
        editor.chain().toggleTaskList().run();
        expect(editorMarkdown(editor, '')).toBe('milk\n\neggs\n\nbread');
    });

    it('converts a bulleted list into a checklist', () => {
        loadMarkdown(editor, '- milk\n- eggs');
        selectAll();

        editor.chain().toggleTaskList().run();

        expect(editorMarkdown(editor, '')).toBe('- [ ] milk\n- [ ] eggs');
    });
});

describe('sub-tasks in the visual editor', () => {
    it('Tab nests a checklist item and Shift+Tab lifts it back', () => {
        loadMarkdown(editor, '- [ ] trip\n- [ ] pack');
        let packPosition = -1;
        editor.state.doc.descendants((node, position) => {
            if (node.isText && node.text === 'pack') {
                packPosition = position;
            }
        });
        editor.commands.setTextSelection(packPosition + 1);

        editor.commands.sinkListItem('taskItem');
        expect(editorMarkdown(editor, '')).toBe('- [ ] trip\n  - [ ] pack');

        editor.commands.liftListItem('taskItem');
        expect(editorMarkdown(editor, '')).toBe('- [ ] trip\n- [ ] pack');
    });
});

describe('smart lists in the visual editor', () => {
    /**
     * Puts the cursor at the end of the last text (the editor keeps an empty
     * paragraph after a list, which isn't where the user types).
     */
    const cursorAtEnd = (): void => {
        let end = 1;
        editor.state.doc.descendants((node, position) => {
            if (node.isTextblock && node.content.size > 0) {
                end = position + 1 + node.content.size;
            }
        });
        editor.commands.setTextSelection(end);
    };

    const typeAtEnd = (text: string): void => {
        editor.commands.insertContent(text);
    };

    // Enter is bound to splitListItem by Tiptap's list keymaps; jsdom can't
    // dispatch real key events through them, so the bound commands are called.
    it('Enter continues a checklist with an unticked item', () => {
        loadMarkdown(editor, '- [x] done');
        cursorAtEnd();

        editor.commands.splitListItem('taskItem');
        typeAtEnd('next');

        expect(editorMarkdown(editor, '')).toBe('- [x] done\n- [ ] next');
    });

    it('Enter continues numbered lists and ends a list on an empty item', () => {
        loadMarkdown(editor, '1. one');
        cursorAtEnd();

        editor.commands.splitListItem('listItem');
        typeAtEnd('two');
        editor.commands.splitListItem('listItem');
        editor.commands.liftListItem('listItem');
        typeAtEnd('after');

        expect(editorMarkdown(editor, '')).toBe('1. one\n2. two\n\nafter');
    });
});

describe('tables in the visual editor', () => {
    it('inserts a table and adds a row and a column', () => {
        loadMarkdown(editor, '');
        editor.commands.setTextSelection(1);

        editor.chain().insertTable({ rows: 2, cols: 2, withHeaderRow: true }).run();
        editor.chain().addColumnAfter().addRowAfter().run();

        const markdown = editorMarkdown(editor, '').trim();
        expect(markdown.split('\n')).toHaveLength(4);
        expect(markdown.split('\n')[0].split('|').length - 2).toBe(3);
    });
});
