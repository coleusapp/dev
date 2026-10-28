import type { FormatCommand, ParagraphStyle } from '@/lib/markdownFormat';

/**
 * What the "/" menu can insert. Each command says what it does in the
 * visual editor and in Markdown source; NoteEditor carries them out.
 */
export type SlashCommand = {
    id: string;
    label: string;
    icon: string;
    keywords: string[];
    action:
        | { type: 'style'; style: ParagraphStyle }
        | { type: 'format'; command: FormatCommand }
        | { type: 'table' }
        | { type: 'divider' }
        | { type: 'tableOfContents' }
        | { type: 'callout' }
        | { type: 'wikiLink' }
        | { type: 'date' };
};

export const SLASH_COMMANDS: SlashCommand[] = [
    { id: 'title', label: 'Title', icon: 'i-lucide-heading-1', keywords: ['h1', 'heading'], action: { type: 'style', style: 'title' } },
    { id: 'heading', label: 'Heading', icon: 'i-lucide-heading-2', keywords: ['h2'], action: { type: 'style', style: 'heading' } },
    { id: 'subheading', label: 'Subheading', icon: 'i-lucide-heading-3', keywords: ['h3'], action: { type: 'style', style: 'subheading' } },
    {
        id: 'checklist',
        label: 'Checklist',
        icon: 'i-lucide-list-checks',
        keywords: ['todo', 'task'],
        action: { type: 'format', command: 'checklist' },
    },
    { id: 'bullets', label: 'Bulleted list', icon: 'i-lucide-list', keywords: ['ul', 'list'], action: { type: 'format', command: 'list' } },
    {
        id: 'numbers',
        label: 'Numbered list',
        icon: 'i-lucide-list-ordered',
        keywords: ['ol', 'list'],
        action: { type: 'format', command: 'numbered' },
    },
    { id: 'quote', label: 'Quote', icon: 'i-lucide-text-quote', keywords: ['blockquote'], action: { type: 'format', command: 'quote' } },
    {
        id: 'code',
        label: 'Code block',
        icon: 'i-lucide-square-code',
        keywords: ['monostyled', 'pre'],
        action: { type: 'style', style: 'monostyled' },
    },
    {
        id: 'callout',
        label: 'Callout',
        icon: 'i-lucide-message-square-warning',
        keywords: ['note', 'warning', 'tip', 'admonition'],
        action: { type: 'callout' },
    },
    { id: 'table', label: 'Table', icon: 'i-lucide-table', keywords: ['grid'], action: { type: 'table' } },
    { id: 'divider', label: 'Divider', icon: 'i-lucide-minus', keywords: ['hr', 'rule', 'separator'], action: { type: 'divider' } },
    {
        id: 'toc',
        label: 'Table of contents',
        icon: 'i-lucide-table-of-contents',
        keywords: ['toc', 'contents', 'outline'],
        action: { type: 'tableOfContents' },
    },
    { id: 'link', label: 'Link to note', icon: 'i-lucide-link-2', keywords: ['wiki', '[['], action: { type: 'wikiLink' } },
    { id: 'date', label: "Today's date", icon: 'i-lucide-calendar', keywords: ['today', 'now'], action: { type: 'date' } },
];

/**
 * Commands matching what was typed after "/": label or keyword prefix
 * first, then anything containing the query.
 */
export const filterSlashCommands = (query: string): SlashCommand[] => {
    const needle = query.toLowerCase();

    if (needle === '') {
        return SLASH_COMMANDS;
    }

    const words = (command: SlashCommand): string[] => [command.label.toLowerCase(), ...command.label.toLowerCase().split(' '), ...command.keywords];
    const prefix = SLASH_COMMANDS.filter((command) => words(command).some((word) => word.startsWith(needle)));
    const contains = SLASH_COMMANDS.filter((command) => !prefix.includes(command) && command.label.toLowerCase().includes(needle));

    return [...prefix, ...contains];
};

/**
 * The "/query" being typed at the end of `textBeforeCursor`: "/" at the
 * start of a line or after a space, followed by letters/digits only.
 * Returns the query and how many characters "/query" spans, or null.
 */
export const slashQuery = (textBeforeCursor: string): { query: string; length: number } | null => {
    const match = /(?:^|\s)\/([\p{L}\p{N}-]{0,20})$/u.exec(textBeforeCursor);

    return match ? { query: match[1], length: match[1].length + 1 } : null;
};

export const todayIso = (now: Date = new Date()): string =>
    `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
