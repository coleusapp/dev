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
