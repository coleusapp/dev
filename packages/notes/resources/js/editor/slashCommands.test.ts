import { filterSlashCommands, slashQuery, todayIso } from '@/editor/slashCommands';
import { describe, expect, it } from 'vitest';

describe('slashQuery', () => {
    it.each([
        ['/', ''],
        ['some text /hea', 'hea'],
        ['/check', 'check'],
    ])('finds the query in %j', (text, query) => {
        expect(slashQuery(text)?.query).toBe(query);
    });

    it.each(['a/b', 'path/to', '/with space', 'text'])('ignores %j', (text) => {
        expect(slashQuery(text)).toBeNull();
    });

    it('reports the length to replace', () => {
        expect(slashQuery('x /tab')).toEqual({ query: 'tab', length: 4 });
    });
});

describe('filterSlashCommands', () => {
    it('lists everything for an empty query', () => {
        expect(filterSlashCommands('').length).toBeGreaterThan(10);
    });

    it('matches labels, words and keywords', () => {
        expect(filterSlashCommands('check')[0].id).toBe('checklist');
        expect(filterSlashCommands('h2')[0].id).toBe('heading');
        expect(filterSlashCommands('todo')[0].id).toBe('checklist');
        expect(filterSlashCommands('list').map((command) => command.id)).toEqual(['bullets', 'numbers', 'checklist']);
    });

    it('returns nothing for unknown words', () => {
        expect(filterSlashCommands('zzz')).toEqual([]);
    });
});

it('formats today as an ISO date', () => {
    expect(todayIso(new Date(2026, 0, 5))).toBe('2026-01-05');
});

describe('attachment commands', () => {
    it('offers files, a photo and a video from the camera', () => {
        expect(
            filterSlashCommands('camera')
                .map((command) => command.action)
                .filter((action) => action.type === 'attach'),
        ).toEqual([
            { type: 'attach', capture: 'photo' },
            { type: 'attach', capture: 'video' },
        ]);
        expect(filterSlashCommands('upload')[0].action).toEqual({ type: 'attach' });
    });
});

describe('recording commands', () => {
    it('offers recording the camera and the screen', () => {
        expect(filterSlashCommands('screen')[0].action).toEqual({ type: 'record', kind: 'screen' });
        expect(filterSlashCommands('webcam')[0].action).toEqual({ type: 'record', kind: 'camera' });
        expect(filterSlashCommands('voice')[0].action).toEqual({ type: 'record', kind: 'audio' });
    });
});
