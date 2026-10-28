import type { TypeaheadMatch } from '@/editor/Typeahead';

/**
 * A #tag being typed at the caret, where a tag can start (Hashtags'
 * rules, plus not right after "[" or ">" so "[[#Heading" and ">>#Heading" stay links), or null.
 * "#" alone opens the menu; a space after it (a Markdown heading) closes it.
 */
export const tagQuery = (textBefore: string): TypeaheadMatch<'#'> | null => {
    const match = /(?<![\p{L}\p{N}_&/#:.\-[>])#((?:[\p{L}\p{N}_-]+\/?)*)$/u.exec(textBefore);

    return match ? { trigger: '#', query: match[1], before: match[1].length + 1, after: 0 } : null;
};
