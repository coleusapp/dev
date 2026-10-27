import { editorExtensions } from '@/editor/extensions';
import { Editor } from '@tiptap/vue-3';

/**
 * Converts pasted HTML (from web pages, Word, Google Docs…) to Markdown
 * using a throwaway instance of the visual editor, so the result follows
 * exactly the same Markdown rules as everything the editor writes.
 */
export const htmlToMarkdown = (html: string): string => {
    const editor = new Editor({ extensions: editorExtensions() });

    try {
        editor.commands.setContent(html, { emitUpdate: false });

        return editor.getMarkdown().trim();
    } finally {
        editor.destroy();
    }
};
