import { fillTemplate, formatDate } from '@/lib/templates';
import { describe, expect, it } from 'vitest';

// Wednesday 28 October 2026, 2:05:09 PM
const now = new Date(2026, 9, 28, 14, 5, 9);

describe('formatDate', () => {
    it('formats with Moment-style tokens', () => {
        expect(formatDate(now, 'YYYY-MM-DD HH:mm:ss', 'en-US')).toBe('2026-10-28 14:05:09');
        expect(formatDate(now, 'dddd, MMMM D YY', 'en-US')).toBe('Wednesday, October 28 26');
        expect(formatDate(now, 'ddd MMM h:mm A', 'en-US')).toBe('Wed Oct 2:05 PM');
        expect(formatDate(now, '[Week of] M/D', 'en-US')).toBe('Week of 10/28');
    });
});

describe('fillTemplate', () => {
    it('fills title, date and time, with optional formats', () => {
        const template = '# {{title}}\nCreated {{date}} at {{time}}\n{{ date:dddd }} {{time:h a}} {{unknown}}';

        expect(fillTemplate(template, { title: 'Standup', now, locale: 'en-US' })).toBe(
            '# Standup\nCreated 2026-10-28 at 14:05\nWednesday 2 pm {{unknown}}',
        );
    });
});
