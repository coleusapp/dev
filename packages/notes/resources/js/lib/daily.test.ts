import { dailyBody, dailyTemplate } from '@/lib/daily';
import { describe, expect, it } from 'vitest';

const day = new Date(2026, 9, 28, 9, 30);

describe('dailyBody', () => {
    it('is just the title without a template', () => {
        expect(dailyBody(day, 'YYYY-MM-DD', null)).toBe('# 2026-10-28\n');
        expect(dailyBody(day, 'dddd, MMMM D, YYYY', null, 'en-US')).toBe('# Wednesday, October 28, 2026\n');
    });

    it('fills the template, adding the title below its front matter when it has none', () => {
        expect(dailyBody(day, 'YYYY-MM-DD', '---\ntype: daily\n---\n## Plan for {{date:dddd}}\n- [ ] ', 'en-US')).toBe(
            '---\ntype: daily\n---\n# 2026-10-28\n\n## Plan for Wednesday\n- [ ] ',
        );
    });

    it('keeps a template that already titles the note', () => {
        expect(dailyBody(day, 'YYYY-MM-DD', '# {{title}}\n\nMood:')).toBe('# 2026-10-28\n\nMood:');
    });
});

describe('dailyTemplate', () => {
    it('finds the template titled Daily note', () => {
        expect(
            dailyTemplate([
                { title: 'Meeting', body: 'm' },
                { title: ' daily NOTE ', body: 'd' },
            ]),
        ).toBe('d');
        expect(dailyTemplate([])).toBeNull();
    });
});
