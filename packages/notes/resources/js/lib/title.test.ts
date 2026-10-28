import { renameFirstLine, titleOf } from '@/lib/title';
import { describe, expect, it } from 'vitest';

describe('renameFirstLine', () => {
    it.each([
        ['heading', '# Old\nbody', 'New', '# New\nbody'],
        ['plain line', 'Old\nbody', 'New', 'New\nbody'],
        ['leading blank lines', '\n\n## Old\nbody', 'New', '\n\n## New\nbody'],
        ['checklist line', '- [ ] Old', 'New', '- [ ] New'],
        ['skips a code fence', '```\nOld\n```', 'New', '```\nNew\n```'],
        ['formatting in the old title is replaced', '# **Old** [link](x)', 'New', '# New'],
        ['spaces collapsed', '# Old', '  New   title ', '# New title'],
        ['keeps front matter', '---\na: 1\n---\n# Old', 'New', '---\na: 1\n---\n# New'],
    ])('renames a %s', (_, markdown, title, expected) => {
        expect(renameFirstLine(markdown, title)).toBe(expected);
    });

    it('adds a title line to an empty or blank note', () => {
        expect(renameFirstLine('', 'New')).toBe('New');
        expect(renameFirstLine('\n\n', 'New')).toBe('New');
    });
});

describe('titleOf', () => {
    it('reads the first line with text, without Markdown', () => {
        expect(titleOf('---\ntags: [a]\n---\n\n# **Bank** details\nbody')).toBe('Bank details');
        expect(titleOf('```\n\n- [ ] [[Call|call]] `mom` [site](https://x.test)')).toBe('call mom site');
        expect(titleOf('   \n')).toBe('');
        expect(titleOf('x'.repeat(300))).toHaveLength(255);
    });
});
