import { linkQuery, splitLinkQuery } from '@/editor/linkQuery';
import { describe, expect, it } from 'vitest';

describe('linkQuery', () => {
    it('follows "[[" anywhere, swallowing an auto-paired "]]"', () => {
        expect(linkQuery('see [[', '')).toEqual({ trigger: '[[', query: '', before: 2, after: 0 });
        expect(linkQuery('see [[Trip to', ']] later')).toEqual({ trigger: '[[', query: 'Trip to', before: 9, after: 2 });
        expect(linkQuery('word[[Trip#Day', ']]')).toMatchObject({ query: 'Trip#Day' });
    });

    it('stops at an alias, a closing bracket or a new line', () => {
        expect(linkQuery('[[Trip|the', ']]')).toBeNull();
        expect(linkQuery('[[Trip]] and', '')).toBeNull();
        expect(linkQuery('[[Trip\nmore', '')).toBeNull();
        expect(linkQuery(`[[${'x'.repeat(101)}`, '')).toBeNull();
    });

    it('follows ">>" at a line start or after a space, but not ">> "', () => {
        expect(linkQuery('>>', '')).toEqual({ trigger: '>>', query: '', before: 2, after: 0 });
        expect(linkQuery('call >>Trip to', '')).toEqual({ trigger: '>>', query: 'Trip to', before: 9, after: 0 });
        expect(linkQuery('>> quote', '')).toBeNull();
        expect(linkQuery('a>>b', '')).toBeNull();
        expect(linkQuery('>>>', '')).toBeNull();
    });
});

describe('splitLinkQuery', () => {
    it('splits a note part from a heading part', () => {
        expect(splitLinkQuery(' Trip ')).toEqual({ note: 'Trip', heading: null });
        expect(splitLinkQuery('Trip#Day one')).toEqual({ note: 'Trip', heading: 'Day one' });
        expect(splitLinkQuery('#')).toEqual({ note: '', heading: '' });
    });
});
