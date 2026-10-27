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
    ])('keeps %s', (_, markdown) => {
        expect(normalizeMarkdown(roundTrip(markdown))).toBe(normalizeMarkdown(markdown));
    });

    it('puts front matter back byte for byte', () => {
        expect(roundTrip('---\ntitle: x\n---\n# Hi')).toBe('---\ntitle: x\n---\n# Hi');
    });

    it('does not escape wiki links or hashtags', () => {
        expect(roundTrip('[[A note]] #tag')).toBe('[[A note]] #tag');
    });

    it('drops trailing blank lines', () => {
        expect(roundTrip('- a\n- b')).toBe('- a\n- b');
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
