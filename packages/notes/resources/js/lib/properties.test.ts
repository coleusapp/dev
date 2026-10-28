import { isValidPropertyName, parseProperties, propertyType, setProperties } from '@/lib/properties';
import { describe, expect, it } from 'vitest';

describe('properties', () => {
    it('reads the same subset as the server', () => {
        const markdown = "---\ntitle: Trip plan\nrating: 4\nprice: 12.5\ndone: false\nempty:\ndate: 2026-10-28\nquoted: \"a: b # c\"\nsingle: 'it''s'\ntags: [travel, \"2026, q4\"]\naliases:\n  - Lisbon\n  - LIS\n---\n# Body";

        expect(parseProperties(markdown)).toEqual({
            title: 'Trip plan',
            rating: 4,
            price: 12.5,
            done: false,
            empty: null,
            date: '2026-10-28',
            quoted: 'a: b # c',
            single: "it's",
            tags: ['travel', '2026, q4'],
            aliases: ['Lisbon', 'LIS'],
        });
    });

    it('returns {} without front matter and null for unreadable YAML', () => {
        expect(parseProperties('# Note')).toEqual({});
        expect(parseProperties('---\nmap:\n  nested: 1\n---\nx')).toBeNull();
    });

    it('infers the field type from the value', () => {
        expect([['a'], true, 3, '2026-10-28', '2026-10-28T09:30', 'text', null].map(propertyType)).toEqual([
            'list',
            'checkbox',
            'number',
            'date',
            'datetime',
            'text',
            'text',
        ]);
    });

    it('writes properties back, quoting what YAML would misread, and reads them again', () => {
        const properties = { status: 'draft', count: 3, done: true, when: '2026-10-28', tags: ['a', 'b c'], note: 'a: b', code: '007', empty: null, yes: 'true' };
        const markdown = setProperties('# Body\n\ntext', properties);

        expect(markdown).toBe(
            '---\nstatus: draft\ncount: 3\ndone: true\nwhen: 2026-10-28\ntags:\n  - a\n  - b c\nnote: "a: b"\ncode: "007"\nempty:\nyes: "true"\n---\n# Body\n\ntext',
        );
        expect(parseProperties(markdown)).toEqual(properties);
    });

    it('replaces existing front matter and removes it when no properties are left', () => {
        expect(setProperties('---\nold: 1\n---\nbody', { new: 'x' })).toBe('---\nnew: x\n---\nbody');
        expect(setProperties('---\nold: 1\n---\nbody', {})).toBe('body');
    });

    it('accepts property names the parser can read back', () => {
        expect(['status', 'due date', 'v1.2', '_x'].every(isValidPropertyName)).toBe(true);
        expect(['', ' lead', 'a:b', '-x'].some(isValidPropertyName)).toBe(false);
    });
});
