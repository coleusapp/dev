import { DATE_CHIP, dateQuery, dateSuggestions, isoDate, isRealDate } from '@/lib/dates';
import { describe, expect, it } from 'vitest';

// Wednesday 28 October 2026
const today = new Date(2026, 9, 28);

describe('dateSuggestions', () => {
    it('offers relative days and the coming weekdays', () => {
        const all = dateSuggestions('', today);

        expect(all.slice(0, 4)).toEqual([
            { label: 'Today', iso: '2026-10-28' },
            { label: 'Tomorrow', iso: '2026-10-29' },
            { label: 'Yesterday', iso: '2026-10-27' },
            { label: 'Next week', iso: '2026-11-04' },
        ]);
        expect(all.find((each) => each.label === 'Wednesday')?.iso).toBe('2026-11-04');
        expect(all.find((each) => each.label === 'Friday')?.iso).toBe('2026-10-30');
        expect(all.slice(4).map((each) => each.label)[0]).toBe('Thursday');
    });

    it('filters by the start of any word', () => {
        expect(dateSuggestions('to', today).map((each) => each.label)).toEqual(['Today', 'Tomorrow']);
        expect(dateSuggestions('week', today).map((each) => each.label)).toEqual(['Next week']);
        expect(dateSuggestions('xyz', today)).toEqual([]);
    });
});

describe('dateQuery', () => {
    it('follows "@" at a word start, letters only', () => {
        expect(dateQuery('call @to')).toEqual({ trigger: '@', query: 'to', before: 3, after: 0 });
        expect(dateQuery('@next we')).toMatchObject({ query: 'next we' });
        expect(dateQuery('sam@ex')).toBeNull();
        expect(dateQuery('@2026')).toBeNull();
    });
});

describe('dates', () => {
    it('formats, validates and finds date chips', () => {
        expect(isoDate(new Date(2026, 0, 5))).toBe('2026-01-05');
        expect(isRealDate('2026-02-30')).toBe(false);
        expect([...'on @2026-10-28 not sam@2026-10-28 or @2026-10-281'.matchAll(DATE_CHIP)].map((match) => match[1])).toEqual(['2026-10-28']);
    });
});
