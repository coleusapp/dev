import { noteHeadings } from '@/components/notes/note';
import { noteOutline } from '@/lib/outline';
import { describe, expect, it } from 'vitest';

describe('noteOutline', () => {
    it('lists headings with level, text and line offset', () => {
        const markdown = '# Trip\n\nIntro\n## **Day** one ##\n### `Food`\n';

        expect(noteOutline(markdown)).toEqual([
            { level: 1, text: 'Trip', offset: 0, occurrence: 0 },
            { level: 2, text: 'Day one', offset: 14, occurrence: 0 },
            { level: 3, text: 'Food', offset: 32, occurrence: 0 },
        ]);
        expect(markdown.slice(14, 20)).toBe('## **D');
    });

    it('skips front matter and fenced code, counting offsets from the top', () => {
        const markdown = '---\n# not a heading\ntags: [a]\n---\n# Real\n```\n# code\n```\n~~~~\n```\n# still code\n~~~~\n## After';
        const outline = noteOutline(markdown);

        expect(outline.map(({ text }) => text)).toEqual(['Real', 'After']);
        expect(markdown.slice(outline[0].offset).startsWith('# Real')).toBe(true);
        expect(markdown.slice(outline[1].offset)).toBe('## After');
    });

    it('numbers repeated heading text', () => {
        expect(noteOutline('## Notes\n## Other\n## Notes').map(({ occurrence }) => occurrence)).toEqual([0, 0, 1]);
    });

    it('ignores empty headings and "#tags"', () => {
        expect(noteOutline('# \n#tag\n#### **\n# Kept')).toEqual([{ level: 1, text: 'Kept', offset: 16, occurrence: 0 }]);
    });
});

describe('noteHeadings', () => {
    it('is the outline as plain text', () => {
        expect(noteHeadings('---\n# comment\n---\n# One\n## Two')).toEqual(['One', 'Two']);
    });
});
