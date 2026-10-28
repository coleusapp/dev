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

/**
 * A note's title as the server derives it (NoteText::title): its first line
 * with text, without Markdown markers. Used where the server can't read the
 * text, i.e. a password-locked note sends its title along (O12.5).
 */
export const titleOf = (markdown: string): string => {
    const line = splitFrontMatter(markdown)
        .body.split('\n')
        .find((each) => each.trim() !== '' && !FENCE.test(each));

    if (line === undefined) {
        return '';
    }

    const plain = line
        .replace(BLOCK_PREFIX, '')
        .replace(/!?\[\[(?:[^\]|]*\|)?([^\]]+)\]\]/g, '$1')
        .replace(/!?\[([^\]]*)\]\([^)]*\)/g, '$1')
        .replace(/(\*\*|__|~~|==|\+\+)(.+?)\1/g, '$2')
        .replace(/(?<![\w*\\])([*_])(?!\s)(.+?)(?<![\s\\])\1(?![\w*])/g, '$2')
        .replace(/`([^`]*)`/g, '$1')
        .replace(/\s+/g, ' ')
        .trim();

    return plain.length > 252 ? `${plain.slice(0, 252)}...` : plain;
};
