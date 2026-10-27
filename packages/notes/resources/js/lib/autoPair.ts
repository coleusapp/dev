import type { Selection, TextEdit } from '@/lib/markdownFormat';

/**
 * Characters typed in pairs. Brackets, quotes and backticks are paired
 * even with nothing selected; Markdown emphasis markers only wrap a
 * selection (pairing a lone "*" would get in the way of bullets and
 * multiplication).
 */
export const PAIRS: Record<string, string> = { '(': ')', '[': ']', '{': '}', '"': '"', '`': '`' };
export const WRAP_ONLY: Record<string, string> = { '*': '*', _: '_', '~': '~', '=': '=' };

const CLOSERS = new Set(Object.values(PAIRS));

/**
 * Whether an opener typed before `next` should get its closer: only at the
 * end of a word, before whitespace, punctuation that ends things, or end of text.
 */
export const canPairBefore = (next: string): boolean => next === '' || /[\s)\]}.,;:!?]/.test(next);

/**
 * Quotes don't pair after a letter or digit (apostrophes, inch marks).
 */
export const canPairAfter = (typed: string, previous: string): boolean => typed !== '"' || !/[\p{L}\p{N}]/u.test(previous);

/**
 * What typing `typed` should do at the selection in plain text, or null to
 * let it through: wrap the selection, insert a pair, or step over an
 * already-present closing character.
 */
export const autoPair = ({ text, start, end }: Selection, typed: string): TextEdit | null => {
    const closer = PAIRS[typed] ?? WRAP_ONLY[typed];

    if (start !== end && closer !== undefined) {
        const selected = text.slice(start, end);

        return { from: start, to: end, insert: typed + selected + closer, selectionStart: start + 1, selectionEnd: end + 1 };
    }

    if (start !== end) {
        return null;
    }

    const next = text.charAt(start);

    if (CLOSERS.has(typed) && next === typed) {
        return { from: start, to: start, insert: '', selectionStart: start + 1, selectionEnd: start + 1 };
    }

    if (PAIRS[typed] !== undefined && canPairBefore(next) && canPairAfter(typed, text.charAt(start - 1))) {
        return { from: start, to: start, insert: typed + PAIRS[typed], selectionStart: start + 1, selectionEnd: start + 1 };
    }

    return null;
};

/**
 * Backspace between an empty pair ("(|)", "[[|]]" after typing "[[")
 * removes both characters.
 */
export const deletePair = ({ text, start, end }: Selection): TextEdit | null => {
    if (start !== end || start === 0) {
        return null;
    }

    const previous = text.charAt(start - 1);

    if (PAIRS[previous] === undefined || text.charAt(start) !== PAIRS[previous]) {
        return null;
    }

    return { from: start - 1, to: start + 1, insert: '', selectionStart: start - 1, selectionEnd: start - 1 };
};
