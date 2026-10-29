/**
 * Copy a note as Markdown, plain text or HTML (A8.6).
 */
export type CopyFormat = 'markdown' | 'text' | 'html';

export const COPY_FORMATS: { format: CopyFormat; label: string; icon: string }[] = [
    { format: 'markdown', label: 'Copy as Markdown', icon: 'i-lucide-file-code' },
    { format: 'text', label: 'Copy as plain text', icon: 'i-lucide-file-text' },
    { format: 'html', label: 'Copy as HTML', icon: 'i-lucide-code-xml' },
];

const BLOCKS = new Set([
    'P',
    'DIV',
    'H1',
    'H2',
    'H3',
    'H4',
    'H5',
    'H6',
    'BLOCKQUOTE',
    'PRE',
    'UL',
    'OL',
    'LI',
    'TABLE',
    'TR',
    'HR',
    'DETAILS',
    'SUMMARY',
    'FIGURE',
    'SECTION',
    'DL',
    'DT',
    'DD',
]);

/**
 * Parts of the rendered note that aren't its text: heading anchors (¶),
 * footnote back-links (↩), a table of contents' "Contents" chrome stays.
 */
const NOT_TEXT = 'script, style, .heading-permalink, .footnote-backref, [aria-hidden="true"]';

/**
 * The rendered note as text people read: paragraphs and headings on their
 * own lines with a blank line between, list items as "- " or "1. ",
 * checklist items as "- [ ] " / "- [x] ", table cells split by tabs.
 */
export const htmlToPlainText = (html: string): string => {
    const doc = new DOMParser().parseFromString(`<body>${html}</body>`, 'text/html');
    doc.querySelectorAll(NOT_TEXT).forEach((element) => element.remove());

    const out: string[] = [];
    const newline = (count: number): void => {
        const text = out.join('');
        const trailing = text.length - text.replace(/\n+$/, '').length;

        if (text.length && trailing < count) {
            out.push('\n'.repeat(count - trailing));
        }
    };

    const walk = (node: Node): void => {
        if (node.nodeType === Node.TEXT_NODE) {
            if (isLayoutWhitespace(node)) {
                return;
            }

            const parentPre = (node.parentElement as HTMLElement | null)?.closest('pre');
            out.push(parentPre ? (node.textContent ?? '') : (node.textContent ?? '').replace(/\s+/g, ' '));

            return;
        }

        if (!(node instanceof HTMLElement)) {
            return;
        }

        const tag = node.tagName;

        if (tag === 'BR') {
            out.push('\n');

            return;
        }

        if (tag === 'INPUT' && (node as HTMLInputElement).type === 'checkbox') {
            out.push((node as HTMLInputElement).checked || node.hasAttribute('checked') ? '[x] ' : '[ ] ');

            return;
        }

        if (tag === 'HR') {
            newline(2);
            out.push('---');
            newline(2);

            return;
        }

        if (tag === 'IMG') {
            const alt = node.getAttribute('alt');
            out.push(alt ? `[${alt}]` : '');

            return;
        }

        const block = BLOCKS.has(tag);
        const tight = tag === 'LI' || tag === 'TR' || tag === 'DT' || tag === 'DD' || node.closest('li') !== null;

        if (block) {
            newline(tight ? 1 : 2);
        }

        if (tag === 'LI') {
            const list = node.parentElement;
            const depth = Math.max(0, node.parentElement ? countAncestors(node, 'LI') : 0);
            const marker = list?.tagName === 'OL' ? `${Array.from(list.children).indexOf(node) + Number(list.getAttribute('start') ?? 1)}. ` : '- ';
            out.push('  '.repeat(depth) + marker);
        }

        if (tag === 'TD' || tag === 'TH') {
            if (node.previousElementSibling) {
                out.push('\t');
            }
        }

        node.childNodes.forEach(walk);

        if (block) {
            newline(tight ? 1 : 2);
        }
    };

    doc.body.childNodes.forEach(walk);

    return out
        .join('')
        .split('\n')
        .map((line) => line.replace(/[ \t]+$/, '').replace(/^ (?=\S)/, ''))
        .join('\n')
        .replace(/(\[[ x]\]) {2,}/g, '$1 ')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
};

const CONTAINERS = new Set(['UL', 'OL', 'TABLE', 'THEAD', 'TBODY', 'TFOOT', 'TR', 'DL', 'BODY']);

/**
 * The line breaks between tags in the HTML source, which aren't text.
 */
const isLayoutWhitespace = (node: Node): boolean => {
    if (/\S/.test(node.textContent ?? '') || node.parentElement?.closest('pre')) {
        return false;
    }

    const isBlock = (sibling: Node | null): boolean => sibling instanceof HTMLElement && BLOCKS.has(sibling.tagName);

    return CONTAINERS.has(node.parentElement?.tagName ?? '') || isBlock(node.previousSibling) || isBlock(node.nextSibling);
};

const countAncestors = (element: HTMLElement, tag: string): number => {
    let count = 0;

    for (let parent = element.parentElement; parent; parent = parent.parentElement) {
        if (parent.tagName === tag) {
            count++;
        }
    }

    return count;
};

/**
 * What goes on the clipboard: text always, HTML too for "Copy as HTML",
 * so pasting into a rich editor keeps formatting and a plain one gets
 * the markup.
 */
export const clipboardContent = (format: CopyFormat, markdown: string, html: string): { text: string; html: string | null } => {
    switch (format) {
        case 'markdown':
            return { text: markdown, html: null };
        case 'text':
            return { text: htmlToPlainText(html), html: null };
        default:
            return { text: html, html };
    }
};

export const writeClipboard = async (content: { text: string; html: string | null }): Promise<void> => {
    if (content.html !== null && typeof ClipboardItem !== 'undefined' && navigator.clipboard.write) {
        await navigator.clipboard.write([
            new ClipboardItem({
                'text/html': new Blob([content.html], { type: 'text/html' }),
                'text/plain': new Blob([content.text], { type: 'text/plain' }),
            }),
        ]);

        return;
    }

    await navigator.clipboard.writeText(content.text);
};
