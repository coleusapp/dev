// @vitest-environment jsdom
import { editorExtensions } from '@/editor/extensions';
import { joinFrontMatter, splitFrontMatter } from '@/editor/frontMatter';
import { editorMarkdown, loadMarkdown, normalizeMarkdown } from '@/editor/markdown';
import { Editor } from '@tiptap/vue-3';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

let editor: Editor;

beforeEach(() => {
    editor = new Editor({ extensions: editorExtensions() });
});

afterEach(() => editor.destroy());

const roundTrip = (markdown: string): string => {
    const { frontMatter } = loadMarkdown(editor, markdown);

    return editorMarkdown(editor, frontMatter);
};

describe('round trip', () => {
    it.each([
        ['heading and paragraph', '# Title\n\nSome **bold**, *italic*, ~~strike~~ and `code`.'],
        ['task list with nesting', '- [ ] todo\n- [x] done\n  - [ ] nested'],
        ['bullets and numbers', '- a\n- b\n\n1. one\n2. two'],
        ['wiki links', 'See [[Trip to Lisbon]] and [[Ideas]].'],
        ['wiki link spacing kept', 'See [[  Trip  ]] here'],
        ['wiki link alias', 'See [[Trip to Lisbon|the trip]] and [[Ideas | ideas ]]'],
        ['hashtags', '#inbox\n\nPlan #work/meetings today'],
        ['highlight', '==marked== text'],
        ['underline and strike', '++under++ and ~~gone~~'],
        ['coloured highlight', 'a ==hot=={pink} and ==calm=={mint} and ==plain== b'],
        ['table', '| a | b |\n| --- | --- |\n| 1 | 2 |'],
        ['quote and code', '> quote\n\n```php\necho 1;\n```'],
        ['link', '[link](https://x.test)'],
        ['bare url and email', 'Visit https://x.test or mail sam@x.test'],
        ['soft line break', 'line one\nline two'],
        ['front matter', '---\ntitle: x\ntags: [a, b]\n---\n\nbody'],
        ['table of contents placeholder', '# A\n\n[TOC]\n\n## B'],
        ['footnotes', 'Lisbon is hilly[^hills] and old[^1].\n\n[^hills]: Seven of them.\n\n[^1]: Very.'],
        ['callouts', '> [!note] Title\n> body text\n\n> [!warning]-\n> folded\n\n> [!tip]+ Open'],
        ['math', 'Area $\\pi r^2$ and $a_1 * b_2$ cost $5 and $10\n\n$$\n\\int_0^1 x\\,dx\n= \\frac12\n$$\n\n$$E=mc^2$$'],
        ['note embeds', '# A\n\n![[Packing]]\n\n![[Trip#Day one]]\n\nsee ![[X]] here'],
    ])('keeps %s', (_, markdown) => {
        expect(normalizeMarkdown(roundTrip(markdown))).toBe(normalizeMarkdown(markdown));
    });

    it('puts front matter back byte for byte', () => {
        expect(roundTrip('---\ntitle: x\n---\n# Hi')).toBe('---\ntitle: x\n---\n# Hi');
    });

    it('does not escape wiki links or hashtags', () => {
        expect(roundTrip('[[A note]] #tag')).toBe('[[A note]] #tag');
    });

    it('keeps TeX exactly as written', () => {
        const markdown = 'Area $\\pi r^2$ and $a_1 * b_2$\n\n$$\n\\int_0^1 x\\,dx\n$$';

        expect(roundTrip(markdown)).toBe(markdown);
        expect(editor.view.dom.querySelector('[data-tex]')?.textContent).toBe('\\pi r^2');
        expect(roundTrip('costs $5 and $10')).toBe('costs $5 and $10');
    });

    it('keeps footnote references and definitions unescaped', () => {
        expect(roundTrip('hilly[^hills].\n\n[^hills]: Seven.')).toBe('hilly[^hills].\n\n[^hills]: Seven.');
        expect(editor.view.dom.querySelector('[data-footnote]')?.textContent).toBe('hills');
        expect(editor.view.dom.querySelector('[data-footnote-definition]')?.textContent).toBe('Seven.');
    });

    it('keeps callout markers as written and shows them as chips', () => {
        expect(roundTrip('> [!Warning]- Careful\n> body')).toBe('> [!Warning]- Careful\n> body');
        expect(editor.view.dom.querySelector('[data-callout]')?.textContent).toBe('Warning ▸');
    });

    it('keeps [TOC] unescaped, but only on a line of its own', () => {
        expect(roundTrip('# A\n\n[TOC]\n\n## B')).toBe('# A\n\n[TOC]\n\n## B');
        expect(editor.getJSON().content?.[1]?.type).toBe('tableOfContents');

        loadMarkdown(editor, 'see [TOC] here');
        expect(editor.getJSON().content?.[0]?.type).toBe('paragraph');
    });

    it('drops trailing blank lines', () => {
        expect(roundTrip('- a\n- b')).toBe('- a\n- b');
    });

    it('shows a wiki link alias and links to the title', () => {
        loadMarkdown(editor, 'See [[Trip  to Lisbon|the trip]] and [[|nothing]]');
        const chip = editor.view.dom.querySelector<HTMLElement>('[data-wiki-link]');

        expect(chip?.textContent).toBe('the trip');
        expect(chip?.dataset.wikiLink).toBe('Trip to Lisbon');
        expect(editor.view.dom.querySelectorAll('[data-wiki-link]')).toHaveLength(1);
        expect(editorMarkdown(editor, '')).toBe('See [[Trip  to Lisbon|the trip]] and \\[\\[|nothing\\]\\]');
    });

    it('links to headings in other notes and in the same note', () => {
        loadMarkdown(editor, 'See [[Trip#Day  one]], [[#Packing]], [[Trip#Day one|day 1]] and [[#]]');
        const chips = Array.from(editor.view.dom.querySelectorAll<HTMLElement>('[data-wiki-raw]'));

        expect(chips.map((chip) => [chip.textContent, chip.dataset.wikiLink, chip.dataset.wikiHeading])).toEqual([
            ['Trip > Day one', 'Trip', 'Day one'],
            ['Packing', '', 'Packing'],
            ['day 1', 'Trip', 'Day one'],
        ]);
        expect(editorMarkdown(editor, '')).toContain('See [[Trip#Day  one]], [[#Packing]], [[Trip#Day one|day 1]] and');
    });

    it('does not treat [[…]] in code as a wiki link', () => {
        loadMarkdown(editor, '`[[code]]`');

        expect(editor.getJSON().content?.[0]?.content?.some((node) => node.type === 'wikiLink')).toBe(false);
    });
});

describe('faithfulness check', () => {
    it.each(['# Title\n\nBody', '* star\n* bullets', '1) paren', 'text [draft] and snake_case', 'Heading\n=======', '_italic_ and __bold__'])(
        'accepts style-only changes in %j',
        (markdown) => {
            expect(loadMarkdown(editor, markdown).faithful).toBe(true);
        },
    );

    it.each(['<details>raw html</details>', '\\#not-a-tag', 'text<br>more'])('flags lossy notes like %j', (markdown) => {
        expect(loadMarkdown(editor, markdown).faithful).toBe(false);
    });
});

describe('front matter', () => {
    it('splits and joins', () => {
        const markdown = '---\na: 1\n---\nbody';

        expect(splitFrontMatter(markdown)).toEqual({ frontMatter: '---\na: 1\n---\n', body: 'body' });
        expect(joinFrontMatter('---\na: 1\n---\n', 'body')).toBe(markdown);
    });

    it('ignores a horizontal rule that is not at the top', () => {
        expect(splitFrontMatter('text\n---\nmore').frontMatter).toBe('');
    });
});

describe('normalizeMarkdown', () => {
    it('keeps escaped hashes distinct', () => {
        expect(normalizeMarkdown('\\#x')).not.toBe(normalizeMarkdown('#x'));
    });
});

describe('text direction', () => {
    it('marks blocks dir="auto" without changing the Markdown', () => {
        const rtl = new Editor({ element: document.createElement('div'), extensions: editorExtensions(), textDirection: 'auto' });
        const { frontMatter } = loadMarkdown(rtl, '# سلام\n\n- [ ] خرید\n\nEnglish line');

        expect(rtl.view.dom.querySelector('h1')?.getAttribute('dir')).toBe('auto');
        expect(editorMarkdown(rtl, frontMatter)).toBe('# سلام\n\n- [ ] خرید\n\nEnglish line');
        rtl.destroy();
    });
});
