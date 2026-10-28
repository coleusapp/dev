import { fuzzyMatch, fuzzyRank, highlightPositions } from '@/lib/fuzzy';
import { describe, expect, it } from 'vitest';

describe('fuzzyMatch', () => {
    it('matches characters in order, ignoring case', () => {
        expect(fuzzyMatch('tl', 'Trip to Lisbon')?.positions).toEqual([0, 8]);
        expect(fuzzyMatch('LIS', 'Trip to Lisbon')?.positions).toEqual([8, 9, 10]);
        expect(fuzzyMatch('xyz', 'Trip to Lisbon')).toBeNull();
        expect(fuzzyMatch('lt', 'Trip to Lisbon')).toBeNull();
    });

    it('matches everything for an empty query', () => {
        expect(fuzzyMatch('  ', 'Anything')).toEqual({ score: 0, positions: [] });
    });

    it('prefers exact, then prefix, then word-start, then mid-word matches', () => {
        const score = (text: string): number => fuzzyMatch('plan', text)?.score ?? -1;

        expect(score('Plan')).toBeGreaterThan(score('Plans'));
        expect(score('Plans')).toBeGreaterThan(score('Weekly plan'));
        expect(score('Weekly plan')).toBeGreaterThan(score('Airplane'));
        expect(score('Airplane')).toBeGreaterThan(score('P l a n'));
    });

    it('ignores spaces in the query when matching scattered characters', () => {
        expect(fuzzyMatch('trip lis', 'Trip to Lisbon')).not.toBeNull();
    });
});

describe('fuzzyRank', () => {
    const notes = [
        { title: 'Groceries', aliases: [] },
        { title: 'Trip to Lisbon', aliases: ['Portugal'] },
        { title: 'Lisbon hotels', aliases: [] },
    ];

    it('ranks by the best name and drops non-matches', () => {
        const ranked = fuzzyRank(notes, 'lisbon', (note) => [note.title, ...note.aliases]);

        expect(ranked.map(({ item }) => item.title)).toEqual(['Lisbon hotels', 'Trip to Lisbon']);
    });

    it('reports an alias when it matches better than the title', () => {
        const [first] = fuzzyRank(notes, 'portugal', (note) => [note.title, ...note.aliases]);

        expect(first.item.title).toBe('Trip to Lisbon');
        expect(first.name).toBe('Portugal');
    });

    it('keeps the given order for an empty query', () => {
        expect(fuzzyRank(notes, '', (note) => [note.title]).map(({ item }) => item.title)).toEqual(['Groceries', 'Trip to Lisbon', 'Lisbon hotels']);
    });
});

describe('highlightPositions', () => {
    it('groups matched and unmatched runs', () => {
        expect(highlightPositions('Trip', [0, 1])).toEqual([
            { text: 'Tr', match: true },
            { text: 'ip', match: false },
        ]);
    });
});
