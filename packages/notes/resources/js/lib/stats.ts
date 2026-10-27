import { splitFrontMatter } from '@/editor/frontMatter';

export type NoteStats = {
    words: number;
    characters: number;
    charactersWithoutSpaces: number;
    paragraphs: number;
    readingMinutes: number;
};

export const WORDS_PER_MINUTE = 225;

/**
 * The text a reader sees: front matter, Markdown markers, link targets,
 * wiki-link brackets, highlight colours and comment marks removed.
 */
export const readableText = (markdown: string): string =>
    splitFrontMatter(markdown)
        .body.replace(/^```.*$|^~~~.*$/gm, '')
        .replace(/%%[\s\S]*?%%/g, '')
        .replace(/!?\[\[([^\]]+)\]\]/g, '$1')
        .replace(/!?\[([^\]]*)\]\([^)]*\)/g, '$1')
        .replace(/==\{(?:pink|purple|orange|mint|blue)\}/g, '')
        .replace(/^\s{0,3}#{1,6}\s+/gm, '')
        .replace(/^\s*>+\s?/gm, '')
        .replace(/^\s*(?:[-*+]|\d+[.)])\s+(?:\[[ xX]\]\s+)?/gm, '')
        .replace(/^\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)*\|?\s*$/gm, '')
        .replace(/(\*\*|__|~~|==|\+\+|[*_`|])/g, ' ')
        .replace(/\\(.)/g, '$1');

const segmenter = typeof Intl !== 'undefined' && 'Segmenter' in Intl ? new Intl.Segmenter(undefined, { granularity: 'word' }) : null;

const countWords = (text: string): number => {
    if (segmenter) {
        let count = 0;
        for (const segment of segmenter.segment(text)) {
            if (segment.isWordLike) {
                count++;
            }
        }
        return count;
    }

    return text.split(/\s+/).filter((word) => /[\p{L}\p{N}]/u.test(word)).length;
};

/**
 * Counts for the note info panel. Words use the browser's word
 * segmentation, so Chinese, Japanese and Thai are counted sensibly too.
 */
export const noteStats = (markdown: string): NoteStats => {
    const text = readableText(markdown);
    const collapsed = text
        .replace(/[ \t]+/g, ' ')
        .replace(/ ?\n ?/g, '\n')
        .trim();
    const words = countWords(text);

    return {
        words,
        characters: [...collapsed.replace(/\n+/g, ' ')].length,
        charactersWithoutSpaces: [...collapsed.replace(/\s+/g, '')].length,
        paragraphs: collapsed === '' ? 0 : collapsed.split(/\n{2,}|\n(?=\S)/).filter((block) => block.trim() !== '').length,
        readingMinutes: words === 0 ? 0 : Math.max(1, Math.round(words / WORDS_PER_MINUTE)),
    };
};
