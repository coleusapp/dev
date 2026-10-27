import { noteStats, readableText } from '@/lib/stats';
import { describe, expect, it } from 'vitest';

describe('noteStats', () => {
    it('counts what a reader sees, not the Markdown', () => {
        const stats = noteStats('---\ntitle: x\n---\n# Trip plan\n\n- [ ] pack **light** bags\n- see [[Lisbon notes]] and [site](https://x.test)');

        expect(stats.words).toBe(10);
        expect(readableText('==hot=={pink} %%secret%% text')).not.toContain('secret');
    });

    it('counts characters with and without spaces', () => {
        expect(noteStats('ab cd')).toMatchObject({ characters: 5, charactersWithoutSpaces: 4 });
    });

    it('estimates reading time at 225 words a minute, at least one minute', () => {
        expect(noteStats('word').readingMinutes).toBe(1);
        expect(noteStats(Array(900).fill('word').join(' ')).readingMinutes).toBe(4);
        expect(noteStats('').readingMinutes).toBe(0);
    });

    it('counts paragraphs and list items as separate blocks', () => {
        expect(noteStats('one\n\ntwo\n\n- a\n- b').paragraphs).toBe(4);
    });

    it('handles empty notes', () => {
        expect(noteStats('')).toEqual({ words: 0, characters: 0, charactersWithoutSpaces: 0, paragraphs: 0, readingMinutes: 0 });
    });
});
