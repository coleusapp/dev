/**
 * The app's fixed keyboard shortcuts (O7.10), shown in the shortcuts sheet
 * and next to command palette entries. Keys are UiKbd values: "meta" is Cmd
 * on a Mac and Ctrl elsewhere, the last entry is the key itself.
 *
 * Browsers keep Cmd+N, Cmd+Shift+N and Cmd+Shift+T for themselves, so new
 * note / folder add Alt, and Title uses Apple's paragraph-style alternative
 * Cmd+Alt+1 (Cmd+Shift+H / J for heading / subheading still work).
 */
export type ShortcutId =
    | 'open-note'
    | 'commands'
    | 'new-note'
    | 'new-folder'
    | 'search'
    | 'next-note'
    | 'previous-note'
    | 'shortcuts'
    | 'save'
    | 'find'
    | 'replace'
    | 'bold'
    | 'italic'
    | 'underline'
    | 'strike'
    | 'link'
    | 'checklist'
    | 'title'
    | 'heading'
    | 'subheading'
    | 'body'
    | 'heading-apple'
    | 'subheading-apple'
    | 'comment'
    | 'move-lines'
    | 'insert-menu';

export type Shortcut = { id: ShortcutId; label: string; group: 'Notes' | 'Editor'; keys: string[] };

export const SHORTCUTS: Shortcut[] = [
    { id: 'open-note', label: 'Open note', group: 'Notes', keys: ['meta', 'O'] },
    { id: 'commands', label: 'Command palette', group: 'Notes', keys: ['meta', 'P'] },
    { id: 'new-note', label: 'New note', group: 'Notes', keys: ['meta', 'alt', 'N'] },
    { id: 'new-folder', label: 'New folder', group: 'Notes', keys: ['meta', 'alt', 'shift', 'N'] },
    { id: 'search', label: 'Search notes', group: 'Notes', keys: ['meta', 'shift', 'F'] },
    { id: 'next-note', label: 'Next note in the list', group: 'Notes', keys: ['meta', 'alt', 'ArrowDown'] },
    { id: 'previous-note', label: 'Previous note in the list', group: 'Notes', keys: ['meta', 'alt', 'ArrowUp'] },
    { id: 'shortcuts', label: 'Keyboard shortcuts', group: 'Notes', keys: ['meta', 'shift', '/'] },
    { id: 'save', label: 'Save now', group: 'Editor', keys: ['meta', 'S'] },
    { id: 'find', label: 'Find in note', group: 'Editor', keys: ['meta', 'F'] },
    { id: 'replace', label: 'Find and replace', group: 'Editor', keys: ['meta', 'alt', 'F'] },
    { id: 'bold', label: 'Bold', group: 'Editor', keys: ['meta', 'B'] },
    { id: 'italic', label: 'Italic', group: 'Editor', keys: ['meta', 'I'] },
    { id: 'underline', label: 'Underline', group: 'Editor', keys: ['meta', 'U'] },
    { id: 'strike', label: 'Strikethrough', group: 'Editor', keys: ['meta', 'shift', 'X'] },
    { id: 'link', label: 'Link', group: 'Editor', keys: ['meta', 'K'] },
    { id: 'checklist', label: 'Checklist', group: 'Editor', keys: ['meta', 'L'] },
    { id: 'title', label: 'Title', group: 'Editor', keys: ['meta', 'alt', '1'] },
    { id: 'heading', label: 'Heading', group: 'Editor', keys: ['meta', 'alt', '2'] },
    { id: 'subheading', label: 'Subheading', group: 'Editor', keys: ['meta', 'alt', '3'] },
    { id: 'body', label: 'Body', group: 'Editor', keys: ['meta', 'alt', '0'] },
    { id: 'heading-apple', label: 'Heading', group: 'Editor', keys: ['meta', 'shift', 'H'] },
    { id: 'subheading-apple', label: 'Subheading', group: 'Editor', keys: ['meta', 'shift', 'J'] },
    { id: 'comment', label: 'Toggle %%comment%% (source)', group: 'Editor', keys: ['meta', '/'] },
    { id: 'move-lines', label: 'Move lines up / down (source)', group: 'Editor', keys: ['alt', 'ArrowUp'] },
    { id: 'insert-menu', label: 'Insert menu', group: 'Editor', keys: ['/'] },
];

export const shortcutKeys = (id: ShortcutId): string[] => SHORTCUTS.find((shortcut) => shortcut.id === id)?.keys ?? [];

type KeyEventLike = Pick<KeyboardEvent, 'metaKey' | 'ctrlKey' | 'altKey' | 'shiftKey' | 'key' | 'code'>;

/**
 * The physical key code for a shortcut key, so Alt and Shift combinations
 * match even though they change event.key (Alt+1 is "¡" on a Mac).
 */
const keyCode = (key: string): string => {
    if (/^[a-z]$/i.test(key)) {
        return `Key${key.toUpperCase()}`;
    }

    if (/^\d$/.test(key)) {
        return `Digit${key}`;
    }

    return key === '/' ? 'Slash' : key;
};

/**
 * Whether a key event is exactly this shortcut: the same modifiers ("meta"
 * accepts Cmd or Ctrl) and the same key. A typed letter is compared as a
 * letter, so other keyboard layouts get the letter they see; anything else
 * (Alt+1 is "¡", Option+N is a dead key on a Mac) goes by the physical key.
 */
export const matchesShortcut = (event: KeyEventLike, id: ShortcutId): boolean => {
    const keys = shortcutKeys(id);
    const modifiers = new Set(keys.slice(0, -1));
    const key = keys[keys.length - 1];

    if (!key) {
        return false;
    }

    const meta = event.metaKey || event.ctrlKey;

    if (meta !== modifiers.has('meta') || event.altKey !== modifiers.has('alt') || event.shiftKey !== modifiers.has('shift')) {
        return false;
    }

    if (key.length > 1) {
        return event.key === key;
    }

    if (/^[a-z]$/i.test(event.key)) {
        return event.key.toLowerCase() === key.toLowerCase();
    }

    return event.code === keyCode(key);
};
