/**
 * What starts a note picker while typing: "[[" (Obsidian) or ">>" at the
 * start of a line or after a space (Apple Notes).
 */
export type LinkTrigger = '[[' | '>>';

/**
 * A link being typed: the query after the trigger, how many characters
 * before the cursor belong to it (trigger included) and how many after it
 * (an auto-paired "]]") so choosing a note replaces them all.
 */
export type LinkQuery = { trigger: LinkTrigger; query: string; before: number; after: number };

export const LINK_QUERY_LENGTH = 100;

/**
 * The link being typed at the cursor, or null. "[[…" stops suggesting once
 * a "|" (alias) or "]" is typed; ">>" needs a non-space right after it, so
 * ">> " (a nested quote in Markdown) closes the picker.
 */
export const linkQuery = (textBefore: string, textAfter: string): LinkQuery | null => {
    const brackets = new RegExp(`\\[\\[([^[\\]\\n|]{0,${LINK_QUERY_LENGTH}})$`).exec(textBefore);

    if (brackets) {
        return { trigger: '[[', query: brackets[1], before: brackets[0].length, after: textAfter.startsWith(']]') ? 2 : 0 };
    }

    const arrows = new RegExp(`(?:^|\\s)>>((?:[^\\s>][^\\n]{0,${LINK_QUERY_LENGTH - 1}})?)$`).exec(textBefore);

    if (arrows) {
        return { trigger: '>>', query: arrows[1], before: arrows[1].length + 2, after: 0 };
    }

    return null;
};

/**
 * "Trip#Day" asks for headings of the note "Trip" matching "Day"; "#Day"
 * for headings of the current note. heading is null until a "#" is typed.
 */
export const splitLinkQuery = (query: string): { note: string; heading: string | null } => {
    const hash = query.indexOf('#');

    return hash === -1 ? { note: query.trim(), heading: null } : { note: query.slice(0, hash).trim(), heading: query.slice(hash + 1).trim() };
};
