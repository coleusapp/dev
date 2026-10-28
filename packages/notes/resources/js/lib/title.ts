import { joinFrontMatter, splitFrontMatter } from '@/editor/frontMatter';

const FENCE = /^\s*(```|~~~)/;
const BLOCK_PREFIX = /^(\s{0,3}#{1,6}\s+|\s*>+\s?|\s*(?:[-*+]|\d+[.)])\s+(?:\[[ xX]\]\s+)?)/;

/**
 * Renames a note by rewriting the line its title comes from: the first
 * line with text (blank lines and code fences skipped, as the server's
 * NoteText::title does). The line keeps its heading, list or quote marker,
 * so "# Old" becomes "# New". A note without such a line gets the title
 * as a new first line. Front matter is left alone.
 */
export const renameFirstLine = (markdown: string, title: string): string => {
    const name = title.replace(/\s+/g, ' ').trim();
    const { frontMatter, body } = splitFrontMatter(markdown);
    const lines = body.split('\n');
    const index = lines.findIndex((line) => line.trim() !== '' && !FENCE.test(line));

    if (index === -1) {
        return joinFrontMatter(frontMatter, body.trim() === '' ? name : `${name}\n\n${body}`);
    }

    const prefix = BLOCK_PREFIX.exec(lines[index])?.[0] ?? '';
    lines[index] = prefix + name;

    return joinFrontMatter(frontMatter, lines.join('\n'));
};
