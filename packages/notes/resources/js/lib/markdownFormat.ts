/**
 * Markdown formatting as pure text edits on a textarea selection.
 *
 * Each command returns the range to replace, the text to put there and the
 * selection to restore afterwards, so the caller can apply it in a way that
 * keeps the browser's undo history.
 */
export type Selection = {
    text: string;
    start: number;
    end: number;
};

export type TextEdit = {
    from: number;
    to: number;
    insert: string;
    selectionStart: number;
    selectionEnd: number;
};

export type FormatCommand = 'bold' | 'italic' | 'underline' | 'strike' | 'code' | 'heading' | 'list' | 'numbered' | 'checklist' | 'quote' | 'link';

const INLINE_MARKERS = { bold: '**', italic: '_', underline: '++', strike: '~~', code: '`' } as const;

const LINE_PREFIXES = { heading: '## ', list: '- ', checklist: '- [ ] ' } as const;

/**
 * Any existing block prefix: heading hashes, bullets, numbers, checklist boxes.
 */
const ANY_PREFIX = /^(#{1,6}\s+|[-*+]\s+\[[ xX]\]\s+|[-*+]\s+|\d+[.)]\s+)/;

const toggleInline = ({ text, start, end }: Selection, marker: string): TextEdit => {
    const selected = text.slice(start, end);
    const size = marker.length;

    if (text.slice(start - size, start) === marker && text.slice(end, end + size) === marker) {
        return { from: start - size, to: end + size, insert: selected, selectionStart: start - size, selectionEnd: end - size };
    }

    if (selected.length >= size * 2 && selected.startsWith(marker) && selected.endsWith(marker)) {
        const inner = selected.slice(size, -size);
        return { from: start, to: end, insert: inner, selectionStart: start, selectionEnd: start + inner.length };
    }

    return { from: start, to: end, insert: marker + selected + marker, selectionStart: start + size, selectionEnd: end + size };
};

const fenceCode = ({ text, start, end }: Selection): TextEdit => {
    const selected = text.slice(start, end);
    const before = start > 0 && text[start - 1] !== '\n' ? '\n' : '';
    const insert = `${before}\`\`\`\n${selected}\n\`\`\`\n`;
    const innerStart = start + before.length + 4;

    return { from: start, to: end, insert, selectionStart: innerStart, selectionEnd: innerStart + selected.length };
};

/**
 * The whole lines touched by the selection.
 */
const lineRange = ({ text, start, end }: Selection): { from: number; to: number; lines: string[] } => {
    const from = text.lastIndexOf('\n', start - 1) + 1;
    const lineEnd = text.indexOf('\n', end > start && text[end - 1] === '\n' ? end - 1 : end);
    const to = lineEnd === -1 ? text.length : lineEnd;

    return { from, to, lines: text.slice(from, to).split('\n') };
};

/**
 * Replaces the selected lines, keeping the caret on the same text for a
 * single line and selecting the whole result for several.
 */
const replaceLines = ({ start, end }: Selection, from: number, to: number, lines: string[], changed: string[]): TextEdit => {
    const insert = changed.join('\n');

    if (lines.length === 1) {
        const shift = insert.length - lines[0].length;
        const caret = Math.max(from, start + shift);
        return { from, to, insert, selectionStart: caret, selectionEnd: Math.max(caret, end + shift) };
    }

    return { from, to, insert, selectionStart: from, selectionEnd: from + insert.length };
};

const toggleLinePrefix = (selection: Selection, prefix: string): TextEdit => {
    const { from, to, lines } = lineRange(selection);
    const prefixOf = (line: string): string => line.match(ANY_PREFIX)?.[0] ?? '';
    const removeAll = lines.every((line) => prefixOf(line) === prefix || (line.trim() === '' && lines.length > 1));

    const changed = lines.map((line) => {
        if (removeAll) {
            return line.slice(prefixOf(line).length);
        }
        if (line.trim() === '' && lines.length > 1) {
            return line;
        }
        return prefix + line.replace(ANY_PREFIX, '');
    });

    return replaceLines(selection, from, to, lines, changed);
};

/**
 * Numbers the selected lines 1., 2., 3. … (blank lines keep their place
 * without a number), or removes the numbers when every line already has one.
 */
const toggleNumbered = (selection: Selection): TextEdit => {
    const { from, to, lines } = lineRange(selection);
    const numbered = /^\d+[.)]\s+/;
    const removeAll = lines.every((line) => numbered.test(line) || (line.trim() === '' && lines.length > 1));
    let next = 1;

    const changed = lines.map((line) => {
        if (removeAll) {
            return line.replace(numbered, '');
        }
        if (line.trim() === '' && lines.length > 1) {
            return line;
        }
        return `${next++}. ${line.replace(ANY_PREFIX, '')}`;
    });

    return replaceLines(selection, from, to, lines, changed);
};

/**
 * Quotes the selected lines, keeping their other markers (a quoted list
 * stays a list), or unquotes them when every line is already quoted.
 */
const toggleQuote = (selection: Selection): TextEdit => {
    const { from, to, lines } = lineRange(selection);
    const quoted = /^>\s?/;
    const removeAll = lines.every((line) => quoted.test(line));

    const changed = lines.map((line) => (removeAll ? line.replace(quoted, '') : `> ${line}`.replace(/^> $/, '>')));

    return replaceLines(selection, from, to, lines, changed);
};

/**
 * Wraps the selection in ==…== with an optional colour suffix ({pink}), or
 * removes the highlight (and its colour) when the selection is already
 * highlighted.
 */
export const toggleHighlight = ({ text, start, end }: Selection, color: string | null): TextEdit => {
    const selected = text.slice(start, end);
    const after = text.slice(end).match(/^==(\{[a-z]+\})?/);

    if (text.slice(start - 2, start) === '==' && after) {
        return { from: start - 2, to: end + after[0].length, insert: selected, selectionStart: start - 2, selectionEnd: end - 2 };
    }

    const suffix = color ? `{${color}}` : '';

    return { from: start, to: end, insert: `==${selected}==${suffix}`, selectionStart: start + 2, selectionEnd: end + 2 };
};

const LIST_ITEM = /^\s*(?:[-*+]|\d+[.)])\s/;

/**
 * Tab / Shift+Tab on list and checklist lines: indents the selected lines
 * by two spaces (making sub-items) or outdents them. Returns null when the
 * cursor isn't on a list line, so Tab keeps its normal meaning elsewhere.
 */
export const indentListLines = (selection: Selection, outdent: boolean): TextEdit | null => {
    const { from, to, lines } = lineRange(selection);

    if (!lines.some((line) => LIST_ITEM.test(line))) {
        return null;
    }

    const changed = lines.map((line) => {
        if (!LIST_ITEM.test(line)) {
            return line;
        }

        return outdent ? line.replace(/^( {1,2}|\t)/, '') : `  ${line}`;
    });

    return replaceLines(selection, from, to, lines, changed);
};

const CONTINUABLE = /^(\s*(?:>\s?)*)(?:([-*+])|(\d+)([.)]))(\s+)(\[[ xX]\]\s+)?/;
const QUOTE_ONLY = /^(\s*(?:>\s?)+)/;

/**
 * Enter in Markdown source: continues a bulleted, numbered or checklist
 * item (or a quote) on the next line, splitting the item at the cursor;
 * on an empty item it ends the list instead (the marker is removed).
 * Returns null when the cursor isn't in a list or quote, or text is selected.
 */
export const continueList = ({ text, start, end }: Selection): TextEdit | null => {
    if (start !== end) {
        return null;
    }

    const lineStart = text.lastIndexOf('\n', start - 1) + 1;
    const lineEndIndex = text.indexOf('\n', start);
    const lineEnd = lineEndIndex === -1 ? text.length : lineEndIndex;
    const line = text.slice(lineStart, lineEnd);
    const item = CONTINUABLE.exec(line);
    const quote = item ? null : QUOTE_ONLY.exec(line);
    const marker = item?.[0] ?? quote?.[0];

    if (!marker || start - lineStart < marker.length) {
        return null;
    }

    if (line.slice(marker.length).trim() === '') {
        const kept = item ? item[1] : '';

        return { from: lineStart, to: lineEnd, insert: kept, selectionStart: lineStart + kept.length, selectionEnd: lineStart + kept.length };
    }

    const next = item ? `${item[1]}${item[2] ?? `${Number(item[3]) + 1}${item[4]}`}${item[5]}${item[6] ? '[ ] ' : ''}` : (quote?.[1] ?? '');
    const insert = `\n${next}`;

    return { from: start, to: start, insert, selectionStart: start + insert.length, selectionEnd: start + insert.length };
};

/**
 * Alt+Up / Alt+Down: moves the selected lines past the line above or
 * below, keeping them selected. Null at the top or bottom.
 */
export const moveLines = (selection: Selection, direction: 'up' | 'down'): TextEdit | null => {
    const { text } = selection;
    const { from, to } = lineRange(selection);

    if (direction === 'up') {
        if (from === 0) {
            return null;
        }

        const aboveStart = text.lastIndexOf('\n', from - 2) + 1;
        const above = text.slice(aboveStart, from - 1);
        const moving = text.slice(from, to);
        const shift = above.length + 1;

        return {
            from: aboveStart,
            to,
            insert: `${moving}\n${above}`,
            selectionStart: selection.start - shift,
            selectionEnd: selection.end - shift,
        };
    }

    if (to >= text.length) {
        return null;
    }

    const belowEnd = text.indexOf('\n', to + 1) === -1 ? text.length : text.indexOf('\n', to + 1);
    const below = text.slice(to + 1, belowEnd);
    const moving = text.slice(from, to);
    const shift = below.length + 1;

    return { from, to: belowEnd, insert: `${below}\n${moving}`, selectionStart: selection.start + shift, selectionEnd: selection.end + shift };
};

/**
 * Shift+Alt+Down: duplicates the selected lines below themselves and
 * selects the copy.
 */
export const duplicateLines = (selection: Selection): TextEdit => {
    const { from, to } = lineRange(selection);
    const lines = selection.text.slice(from, to);
    const shift = lines.length + 1;

    return { from: to, to, insert: `\n${lines}`, selectionStart: selection.start + shift, selectionEnd: selection.end + shift };
};

/**
 * Cmd/Ctrl+/: wraps the selection (or the current line) in a %%comment%%,
 * hidden from the rendered note, or removes the comment marks when it is one.
 */
export const toggleComment = (selection: Selection): TextEdit => {
    const { text } = selection;
    const { from: lineFrom, to: lineTo } = lineRange(selection);
    const [from, to] = selection.start === selection.end ? [lineFrom, lineTo] : [selection.start, selection.end];
    const target = text.slice(from, to);
    const commented = /^%%([\s\S]*)%%$/.exec(target);

    if (commented) {
        return { from, to, insert: commented[1], selectionStart: from, selectionEnd: to - 4 };
    }

    if (text.slice(from - 2, from) === '%%' && text.slice(to, to + 2) === '%%') {
        return { from: from - 2, to: to + 2, insert: target, selectionStart: from - 2, selectionEnd: to - 2 };
    }

    return { from, to, insert: `%%${target}%%`, selectionStart: from, selectionEnd: to + 4 };
};

export type ParagraphStyle = 'title' | 'heading' | 'subheading' | 'body' | 'monostyled';

const STYLE_PREFIXES = { title: '# ', heading: '## ', subheading: '### ', body: '' } as const;

/**
 * Apple Notes' paragraph styles in Markdown: Title/Heading/Subheading are
 * #/##/### headings and Body is plain text (each replaces a heading or list
 * marker on the line, as in Apple Notes), Monostyled fences a code block.
 */
export const applyParagraphStyle = (style: ParagraphStyle, selection: Selection): TextEdit => {
    if (style === 'monostyled') {
        return fenceCode(selection);
    }

    const { from, to, lines } = lineRange(selection);
    const prefix = STYLE_PREFIXES[style];
    const changed = lines.map((line) => (line.trim() === '' && lines.length > 1 ? line : prefix + line.replace(ANY_PREFIX, '')));

    return replaceLines(selection, from, to, lines, changed);
};

const link = ({ text, start, end }: Selection): TextEdit => {
    const selected = text.slice(start, end);

    if (/^https?:\/\/\S+$/.test(selected)) {
        return { from: start, to: end, insert: `[](${selected})`, selectionStart: start + 1, selectionEnd: start + 1 };
    }

    const label = selected || 'link';
    const urlStart = start + label.length + 3;

    return { from: start, to: end, insert: `[${label}](url)`, selectionStart: urlStart, selectionEnd: urlStart + 3 };
};

export const formatMarkdown = (command: FormatCommand, selection: Selection): TextEdit => {
    switch (command) {
        case 'bold':
        case 'italic':
        case 'underline':
        case 'strike':
            return toggleInline(selection, INLINE_MARKERS[command]);
        case 'code':
            return selection.text.slice(selection.start, selection.end).includes('\n')
                ? fenceCode(selection)
                : toggleInline(selection, INLINE_MARKERS.code);
        case 'heading':
        case 'list':
        case 'checklist':
            return toggleLinePrefix(selection, LINE_PREFIXES[command]);
        case 'numbered':
            return toggleNumbered(selection);
        case 'quote':
            return toggleQuote(selection);
        case 'link':
            return link(selection);
    }
};

/**
 * Applies an edit to a textarea through insertText where the browser
 * supports it, so Cmd/Ctrl+Z undoes the formatting like normal typing.
 */
export const applyEdit = (textarea: HTMLTextAreaElement, edit: TextEdit): void => {
    textarea.focus();

    // Only moving the cursor (e.g. stepping over an auto-paired closer).
    if (edit.from === edit.to && edit.insert === '') {
        textarea.setSelectionRange(edit.selectionStart, edit.selectionEnd);
        return;
    }

    textarea.setSelectionRange(edit.from, edit.to);

    const inserted = typeof document.execCommand === 'function' && document.execCommand('insertText', false, edit.insert);

    if (!inserted) {
        textarea.setRangeText(edit.insert, edit.from, edit.to, 'end');
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    textarea.setSelectionRange(edit.selectionStart, edit.selectionEnd);
};
