export type FindOptions = {
    caseSensitive: boolean;
    regex: boolean;
};

export type FindMatch = { start: number; end: number };

export const MAX_MATCHES = 1000;

/**
 * The search as a global RegExp, or an error message for an invalid
 * regular expression. Plain searches are escaped.
 */
export const findPattern = (query: string, { caseSensitive, regex }: FindOptions): RegExp | string => {
    const source = regex ? query : query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    try {
        return new RegExp(source, `g${caseSensitive ? '' : 'i'}u`);
    } catch (error) {
        return error instanceof Error ? error.message : 'Invalid regular expression';
    }
};

/**
 * Non-empty matches of `pattern` in `text` (capped at MAX_MATCHES).
 */
export const findMatches = (text: string, pattern: RegExp): FindMatch[] => {
    const matches: FindMatch[] = [];

    for (const match of text.matchAll(pattern)) {
        if (match[0] !== '' && match.index !== undefined) {
            matches.push({ start: match.index, end: match.index + match[0].length });
        }

        if (matches.length >= MAX_MATCHES) {
            break;
        }
    }

    return matches;
};

/**
 * What one match becomes: the replacement text, with $1, $& … expanded
 * when searching with a regular expression.
 */
export const replacementFor = (matched: string, pattern: RegExp, replacement: string, regex: boolean): string => {
    if (!regex) {
        return replacement;
    }

    return matched.replace(new RegExp(pattern.source, pattern.flags.replace('g', '')), replacement);
};

/**
 * Replaces every match in `text`.
 */
export const replaceAll = (text: string, pattern: RegExp, replacement: string, regex: boolean): string => {
    let result = '';
    let last = 0;

    for (const { start, end } of findMatches(text, pattern)) {
        result += text.slice(last, start) + replacementFor(text.slice(start, end), pattern, replacement, regex);
        last = end;
    }

    return result + text.slice(last);
};
