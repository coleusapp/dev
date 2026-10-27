import type { ParagraphStyle } from '@/lib/markdownFormat';
import type { Editor } from '@tiptap/vue-3';

const HEADING_LEVELS: Record<'title' | 'heading' | 'subheading', 1 | 2 | 3> = { title: 1, heading: 2, subheading: 3 };

/**
 * Applies an Apple Notes paragraph style to the selected blocks.
 */
export const applyParagraphStyle = (editor: Editor, style: ParagraphStyle): void => {
    const chain = editor.chain().focus();

    switch (style) {
        case 'title':
        case 'heading':
        case 'subheading':
            chain.setHeading({ level: HEADING_LEVELS[style] }).run();
            break;
        case 'body':
            chain.setParagraph().run();
            break;
        case 'monostyled':
            chain.toggleCodeBlock().run();
            break;
    }
};

const FIRST_LINE_LEVELS: Record<'title' | 'heading' | 'subheading', 1 | 2 | 3> = HEADING_LEVELS;

/**
 * Where the styled first line goes: over the whole document when it's
 * empty, over the first block when that's an empty paragraph (blank lines
 * at the top parse into one), otherwise in front of everything.
 */
const firstBlockRange = (editor: Editor, empty: boolean): number | { from: number; to: number } => {
    const first = editor.state.doc.firstChild;

    if (empty) {
        return { from: 0, to: editor.state.doc.content.size };
    }

    return first && first.isTextblock && first.content.size === 0 ? { from: 0, to: first.nodeSize } : 0;
};

/**
 * Gives a new note an empty, styled first line to type its title into
 * (Apple Notes' "new notes start with" setting). Applies when the note is
 * empty or its first line is blank (a note started from a tag is
 * "\n\n#tag"). Not saved by itself: the change doesn't fire an update, so
 * nothing is written until the user types.
 */
export const prepareFirstLine = (editor: Editor, markdownBody: string, style: Exclude<ParagraphStyle, 'monostyled'>): void => {
    const empty = markdownBody.trim() === '';

    if (!empty && !/^[ \t]*\r?\n/.test(markdownBody)) {
        return;
    }

    const block = style === 'body' ? { type: 'paragraph' } : { type: 'heading', attrs: { level: FIRST_LINE_LEVELS[style] } };

    editor
        .chain()
        .command(({ tr }) => {
            tr.setMeta('preventUpdate', true);
            return true;
        })
        .insertContentAt(firstBlockRange(editor, empty), block)
        .setTextSelection(1)
        .run();
};

/**
 * The paragraph style at the cursor, or null inside lists, quotes and other blocks.
 */
export const currentParagraphStyle = (editor: Editor): ParagraphStyle | null => {
    if (editor.isActive('codeBlock')) {
        return 'monostyled';
    }

    for (const [style, level] of Object.entries(HEADING_LEVELS)) {
        if (editor.isActive('heading', { level })) {
            return style as ParagraphStyle;
        }
    }

    const inListOrQuote = ['bulletList', 'orderedList', 'taskList', 'blockquote'].some((type) => editor.isActive(type));

    return editor.isActive('paragraph') && !inListOrQuote ? 'body' : null;
};
