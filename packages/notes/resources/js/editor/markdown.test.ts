// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { Editor } from '@tiptap/vue-3';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

let editor: Editor;

beforeEach(() => {
    editor = new Editor({ extensions: editorExtensions() });
});

afterEach(() => editor.destroy());

const roundTrip = (markdown: string): string => {
    loadMarkdown(editor, markdown);

    return editorMarkdown(editor);
};

describe('Markdown in and out of the editor', () => {
    it.each([
        ['heading and paragraph', '# Title\n\nSome **bold**, *italic*, ~~strike~~ and `code`.'],
        ['checklist with nesting', '- [ ] todo\n- [x] done\n  - [ ] nested'],
        ['bullets and numbers', '- a\n- b\n\n1. one\n2. two'],
        ['quote and code', '> quote\n\n```php\necho 1;\n```'],
        ['link', '[link](https://x.test)'],
    ])('keeps %s', (_, markdown) => {
        expect(roundTrip(markdown)).toBe(markdown);
    });

    it('drops trailing blank lines', () => {
        expect(roundTrip('# A\n\n\n')).toBe('# A');
    });

    it('loads without firing an update', () => {
        let updates = 0;
        editor.on('update', () => updates++);

        loadMarkdown(editor, '# A');

        expect(updates).toBe(0);
    });
});
