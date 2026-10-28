import { matchesShortcut, shortcutKeys, SHORTCUTS } from '@/lib/shortcuts';
import { describe, expect, it } from 'vitest';

const press = (key: string, code: string, modifiers: Partial<Record<'metaKey' | 'ctrlKey' | 'altKey' | 'shiftKey', boolean>> = {}) => ({
    key,
    code,
    metaKey: false,
    ctrlKey: false,
    altKey: false,
    shiftKey: false,
    ...modifiers,
});

describe('matchesShortcut', () => {
    it('accepts Cmd or Ctrl for "meta"', () => {
        expect(matchesShortcut(press('o', 'KeyO', { metaKey: true }), 'open-note')).toBe(true);
        expect(matchesShortcut(press('o', 'KeyO', { ctrlKey: true }), 'open-note')).toBe(true);
        expect(matchesShortcut(press('o', 'KeyO'), 'open-note')).toBe(false);
    });

    it('needs exactly the listed modifiers', () => {
        expect(matchesShortcut(press('O', 'KeyO', { metaKey: true, shiftKey: true }), 'open-note')).toBe(false);
        expect(matchesShortcut(press('n', 'KeyN', { metaKey: true, altKey: true }), 'new-note')).toBe(true);
        expect(matchesShortcut(press('N', 'KeyN', { metaKey: true, altKey: true, shiftKey: true }), 'new-note')).toBe(false);
        expect(matchesShortcut(press('N', 'KeyN', { metaKey: true, altKey: true, shiftKey: true }), 'new-folder')).toBe(true);
    });

    it('matches by physical key when Alt or Shift changes the character', () => {
        expect(matchesShortcut(press('¡', 'Digit1', { metaKey: true, altKey: true }), 'title')).toBe(true);
        expect(matchesShortcut(press('?', 'Slash', { metaKey: true, shiftKey: true }), 'shortcuts')).toBe(true);
        expect(matchesShortcut(press('ArrowDown', 'ArrowDown', { metaKey: true, altKey: true }), 'next-note')).toBe(true);
        expect(matchesShortcut(press('Dead', 'KeyN', { metaKey: true, altKey: true }), 'new-note')).toBe(true);
        expect(matchesShortcut(press('[', 'BracketLeft', { metaKey: true }), 'back')).toBe(true);
    });

    it('falls back to the character on other keyboard layouts', () => {
        expect(matchesShortcut(press('l', 'KeyP', { metaKey: true }), 'checklist')).toBe(true);
        expect(matchesShortcut(press('l', 'KeyP', { metaKey: true }), 'commands')).toBe(false);
    });
});

describe('SHORTCUTS', () => {
    it('gives every shortcut a key and no two the same keys', () => {
        const combos = SHORTCUTS.map((shortcut) => shortcut.keys.join('+'));

        expect(SHORTCUTS.every((shortcut) => shortcut.keys.length > 0)).toBe(true);
        expect(new Set(combos).size).toBe(combos.length);
        expect(shortcutKeys('bold')).toEqual(['meta', 'B']);
    });
});
