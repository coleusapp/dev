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

export type FormatCommand = 'bold' | 'italic' | 'code' | 'heading' | 'list' | 'checklist' | 'link';

const INLINE_MARKERS = { bold: '**', italic: '_', code: '`' } as const;

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

const toggleLinePrefix = ({ text, start, end }: Selection, prefix: string): TextEdit => {
    const from = text.lastIndexOf('\n', start - 1) + 1;
    const lineEnd = text.indexOf('\n', end > start && text[end - 1] === '\n' ? end - 1 : end);
    const to = lineEnd === -1 ? text.length : lineEnd;
    const lines = text.slice(from, to).split('\n');
    const prefixOf = (line: string): string => line.match(ANY_PREFIX)?.[0] ?? '';
    const removeAll = lines.every((line) => prefixOf(line) === prefix);

    const changed = lines.map((line) => {
        if (removeAll) {
            return line.slice(prefixOf(line).length);
        }
        if (line.trim() === '' && lines.length > 1) {
            return line;
        }
        return prefix + line.replace(ANY_PREFIX, '');
    });
    const insert = changed.join('\n');

    if (lines.length === 1) {
        const shift = insert.length - lines[0].length;
        const caret = Math.max(from, start + shift);
        return { from, to, insert, selectionStart: caret, selectionEnd: Math.max(caret, end + shift) };
    }

    return { from, to, insert, selectionStart: from, selectionEnd: from + insert.length };
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
            return toggleInline(selection, INLINE_MARKERS[command]);
        case 'code':
            return selection.text.slice(selection.start, selection.end).includes('\n')
                ? fenceCode(selection)
                : toggleInline(selection, INLINE_MARKERS.code);
        case 'heading':
        case 'list':
        case 'checklist':
            return toggleLinePrefix(selection, LINE_PREFIXES[command]);
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
    textarea.setSelectionRange(edit.from, edit.to);

    const inserted = typeof document.execCommand === 'function' && document.execCommand('insertText', false, edit.insert);

    if (!inserted) {
        textarea.setRangeText(edit.insert, edit.from, edit.to, 'end');
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    textarea.setSelectionRange(edit.selectionStart, edit.selectionEnd);
};
