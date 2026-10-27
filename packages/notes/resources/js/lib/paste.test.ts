// @vitest-environment jsdom
import { htmlToMarkdown } from '@/editor/htmlToMarkdown';
import { pasteUrlOverSelection } from '@/lib/paste';
import { describe, expect, it } from 'vitest';

describe('pasteUrlOverSelection', () => {
    it('links the selected text', () => {
        expect(pasteUrlOverSelection({ text: 'see docs here', start: 4, end: 8 }, ' https://x.test/a ')).toEqual({
            from: 4,
            to: 8,
            insert: '[docs](https://x.test/a)',
            selectionStart: 28,
            selectionEnd: 28,
        });
    });

    it.each([
        ['nothing selected', { text: 'a', start: 1, end: 1 }, 'https://x.test'],
        ['not a URL', { text: 'docs', start: 0, end: 4 }, 'just text'],
        ['URL with spaces', { text: 'docs', start: 0, end: 4 }, 'https://x.test and more'],
        ['multi-line selection', { text: 'a\nb', start: 0, end: 3 }, 'https://x.test'],
    ])('pastes normally when %s', (_, selection, pasted) => {
        expect(pasteUrlOverSelection(selection, pasted)).toBeNull();
    });
});

describe('htmlToMarkdown', () => {
    it.each([
        ['formatting', '<p>Some <strong>bold</strong> and <em>italic</em> text</p>', 'Some **bold** and *italic* text'],
        ['headings and lists', '<h2>Plan</h2><ul><li>one</li><li>two</li></ul>', '## Plan\n\n- one\n- two'],
        ['links', '<p><a href="https://x.test">site</a></p>', '[site](https://x.test)'],
        ['tables', '<table><tr><th>a</th></tr><tr><td>1</td></tr></table>', '| a   |\n| --- |\n| 1   |'],
    ])('converts %s', (_, html, markdown) => {
        expect(htmlToMarkdown(html)).toBe(markdown);
    });

    it('drops scripts and styles', () => {
        expect(htmlToMarkdown('<p>ok</p><script>alert(1)</script><style>p{}</style>')).toBe('ok');
    });
});
