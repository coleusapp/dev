import type { HighlightPart } from '@/components/notes/note';

export type FuzzyMatch = { score: number; positions: number[] };

const isWordStart = (text: string, index: number): boolean => index === 0 || /[\s\-_/.,:;([{"'“]/.test(text[index - 1]);

/**
 * Matches the query's characters in order anywhere in the text (so "tl"
 * finds "Trip to Lisbon"), ignoring case and spaces in the query. A
 * contiguous match beats a scattered one, and matches at the start or at
 * word starts beat ones mid-word. Null when the text doesn't match.
 */
export const fuzzyMatch = (query: string, text: string): FuzzyMatch | null => {
    const needle = query.toLowerCase().replace(/\s+/g, ' ').trim();
    const haystack = text.toLowerCase();

    if (needle === '') {
        return { score: 0, positions: [] };
    }

    const at = haystack.indexOf(needle);

    if (at !== -1) {
        const exact = needle.length === haystack.length ? 100 : 0;
        const boundary = isWordStart(haystack, at) ? 20 : 0;

        return { score: 200 + exact + boundary - at, positions: Array.from({ length: needle.length }, (_, offset) => at + offset) };
    }

    const characters = needle.replace(/ /g, '');
    const positions: number[] = [];
    let score = 0;
    let from = 0;

    for (const character of characters) {
        const index = haystack.indexOf(character, from);

        if (index === -1) {
            return null;
        }

        score += 1;
        score += positions.length && index === positions[positions.length - 1] + 1 ? 5 : 0;
        score += isWordStart(haystack, index) ? 8 : 0;
        score -= Math.min(index - from, 10) * 0.2;
        positions.push(index);
        from = index + 1;
    }

    return { score, positions };
};

/**
 * Splits text into matched and unmatched runs for HighlightedText.
 */
export const highlightPositions = (text: string, positions: number[]): HighlightPart[] => {
    const marked = new Set(positions);
    const parts: HighlightPart[] = [];

    for (let index = 0; index < text.length; index++) {
        const match = marked.has(index);
        const last = parts[parts.length - 1];

        if (last && last.match === match) {
            last.text += text[index];
        } else {
            parts.push({ text: text[index], match });
        }
    }

    return parts;
};

/**
 * Ranks items by their best-matching name (e.g. a note's title or one of its
 * aliases), dropping those that don't match. Ties keep the given order, so
 * a list sorted by recency stays that way among equal matches.
 */
export const fuzzyRank = <T>(items: T[], query: string, names: (item: T) => string[]): { item: T; name: string; match: FuzzyMatch }[] => {
    const ranked: { item: T; name: string; match: FuzzyMatch; order: number }[] = [];

    items.forEach((item, order) => {
        let best: { name: string; match: FuzzyMatch } | null = null;

        for (const name of names(item)) {
            const match = fuzzyMatch(query, name);

            if (match && (!best || match.score > best.match.score)) {
                best = { name, match };
            }
        }

        if (best) {
            ranked.push({ item, ...best, order });
        }
    });

    return ranked.sort((a, b) => b.match.score - a.match.score || a.order - b.order).map(({ item, name, match }) => ({ item, name, match }));
};
