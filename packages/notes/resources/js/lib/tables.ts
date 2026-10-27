import type { Selection, TextEdit } from '@/lib/markdownFormat';

const ROW = /^\s*\|.*\|\s*$/;
const DELIMITER_CELL = /^\s*:?-+:?\s*$/;

export const TABLE_TEMPLATE = '| Column 1 | Column 2 | Column 3 |\n| --- | --- | --- |\n|  |  |  |';

/**
 * Inserts an empty 3×3 table (header row + one row) on its own lines, with
 * the first header cell selected for typing.
 */
export const insertTable = ({ text, start, end }: Selection): TextEdit => {
    const before = start > 0 && text[start - 1] !== '\n' ? '\n\n' : start > 1 && text[start - 2] !== '\n' ? '\n' : '';
    const after = end < text.length && text[end] !== '\n' ? '\n\n' : '';
    const insert = `${before}${TABLE_TEMPLATE}${after}`;
    const firstCell = start + before.length + 2;

    return { from: start, to: end, insert, selectionStart: firstCell, selectionEnd: firstCell + 'Column 1'.length };
};

const splitRow = (row: string): string[] => {
    const cells = row
        .trim()
        .replace(/^\|/, '')
        .replace(/\|$/, '')
        .split(/(?<!\\)\|/);

    return cells.map((cell) => cell.trim());
};

/**
 * The 0-based line range [first, last] of the table around `line`, or null.
 */
const tableAround = (lines: string[], line: number): [number, number] | null => {
    if (!ROW.test(lines[line] ?? '')) {
        return null;
    }

    let first = line;
    let last = line;
    while (first > 0 && ROW.test(lines[first - 1])) {
        first--;
    }
    while (last < lines.length - 1 && ROW.test(lines[last + 1])) {
        last++;
    }

    const delimiter = lines[first + 1];

    return delimiter !== undefined && splitRow(delimiter).every((cell) => DELIMITER_CELL.test(cell)) ? [first, last] : null;
};

/**
 * Pads the cells of the table containing the cursor so its pipes line up
 * (keeping column alignment colons). The cursor stays in the same cell.
 */
export const formatTable = ({ text, start }: Selection): TextEdit | null => {
    const lines = text.split('\n');
    const cursorLine = text.slice(0, start).split('\n').length - 1;
    const range = tableAround(lines, cursorLine);

    if (!range) {
        return null;
    }

    const [first, last] = range;
    const rows = lines.slice(first, last + 1).map(splitRow);
    const columns = Math.max(...rows.map((row) => row.length));
    const widths = Array.from({ length: columns }, (_, column) =>
        Math.max(3, ...rows.filter((_, index) => index !== 1).map((row) => [...(row[column] ?? '')].length)),
    );

    const formatted = rows.map((row, index) => {
        const cells = Array.from({ length: columns }, (_, column) => {
            const cell = row[column] ?? '';

            if (index === 1) {
                const left = cell.startsWith(':') ? ':' : '-';
                const right = cell.endsWith(':') ? ':' : '-';
                return left + '-'.repeat(widths[column] - 2) + right;
            }

            return cell + ' '.repeat(widths[column] - [...cell].length);
        });

        return `| ${cells.join(' | ')} |`;
    });

    const from = lines.slice(0, first).join('\n').length + (first > 0 ? 1 : 0);
    const to = from + lines.slice(first, last + 1).join('\n').length;
    const insert = formatted.join('\n');
    const lineStart = text.lastIndexOf('\n', start - 1) + 1;
    const cellIndex = text.slice(lineStart, start).split(/(?<!\\)\|/).length - 2;
    const newLineStart = from + formatted.slice(0, cursorLine - first).join('\n').length + (cursorLine > first ? 1 : 0);
    const cursor = newLineStart + 2 + widths.slice(0, Math.max(0, cellIndex)).reduce((sum, width) => sum + width + 3, 0);

    return { from, to, insert, selectionStart: cursor, selectionEnd: cursor };
};

/**
 * Tab in a table row moves to the start of the next cell's text (Shift+Tab
 * to the previous one). Null outside tables.
 */
export const moveToCell = ({ text, start }: Selection, backwards: boolean): TextEdit | null => {
    const lineStart = text.lastIndexOf('\n', start - 1) + 1;
    const lineEndIndex = text.indexOf('\n', start);
    const lineEnd = lineEndIndex === -1 ? text.length : lineEndIndex;

    if (!ROW.test(text.slice(lineStart, lineEnd))) {
        return null;
    }

    const pipes: number[] = [];
    for (let index = lineStart; index < lineEnd; index++) {
        if (text[index] === '|' && text[index - 1] !== '\\') {
            pipes.push(index);
        }
    }

    const behind = pipes.filter((pipe) => pipe < start);
    const target = backwards ? behind[behind.length - 2] : pipes.find((pipe) => pipe >= start);

    if (target === undefined || target === pipes[pipes.length - 1]) {
        return null;
    }

    const cell = text[target + 1] === ' ' ? target + 2 : target + 1;

    return { from: cell, to: cell, insert: '', selectionStart: cell, selectionEnd: cell };
};
