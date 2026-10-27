import { EMOJI_GROUPS, searchEmoji } from '@/lib/emoji';
import { describe, expect, it } from 'vitest';

describe('emoji', () => {
    it('has groups with unique emoji', () => {
        const all = EMOJI_GROUPS.flatMap((group) => group.emoji.map((emoji) => emoji.char));

        expect(EMOJI_GROUPS.length).toBeGreaterThan(5);
        expect(new Set(all).size).toBe(all.length);
    });

    it('searches by name words', () => {
        expect(searchEmoji('thumbs up').map((emoji) => emoji.char)).toEqual(['👍']);
        expect(searchEmoji('heart').length).toBeGreaterThan(2);
        expect(searchEmoji('zzzz')).toEqual([]);
    });
});
