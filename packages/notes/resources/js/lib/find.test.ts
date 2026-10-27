import { findMatches, findPattern, replaceAll, replacementFor } from '@/lib/find';
import { describe, expect, it } from 'vitest';

const pattern = (query: string, caseSensitive = false, regex = false): RegExp => findPattern(query, { caseSensitive, regex }) as RegExp;

describe('find', () => {
    it('finds plain text case-insensitively by default, special characters literally', () => {
        expect(findMatches('Plan plan PLAN', pattern('plan'))).toHaveLength(3);
        expect(findMatches('Plan plan', pattern('plan', true))).toEqual([{ start: 5, end: 9 }]);
        expect(findMatches('a.b axb (c)', pattern('a.b'))).toEqual([{ start: 0, end: 3 }]);
        expect(findMatches('cost (c)', pattern('(c)'))).toEqual([{ start: 5, end: 8 }]);
    });

    it('supports regular expressions and reports invalid ones', () => {
        expect(findMatches('a1 b22 c', pattern('\\d+', false, true))).toEqual([
            { start: 1, end: 2 },
            { start: 4, end: 6 },
        ]);
        expect(typeof findPattern('(', { caseSensitive: false, regex: true })).toBe('string');
    });

    it('skips empty matches', () => {
        expect(findMatches('abc', pattern('x*', false, true))).toEqual([]);
    });

    it('replaces all, expanding groups only for regular expressions', () => {
        expect(replaceAll('a-b a-b', pattern('a-b'), 'X', false)).toBe('X X');
        expect(replaceAll('2026-01-05', pattern('(\\d+)-(\\d+)-(\\d+)', false, true), '$3/$2/$1', true)).toBe('05/01/2026');
        expect(replaceAll('cost $1', pattern('cost'), 'price $1', false)).toBe('price $1 $1');
        expect(replacementFor('ab', pattern('(a)(b)', false, true), '$2$1', true)).toBe('ba');
    });
});
