import type { Editor } from '@tiptap/vue-3';

/**
 * The note as Markdown.
 */
export const editorMarkdown = (editor: Editor): string => editor.getMarkdown().replace(/\s+$/, '');

/**
 * Loads a note's Markdown into the editor without counting as an edit.
 */
export const loadMarkdown = (editor: Editor, markdown: string): void => {
    editor.commands.setContent(markdown, { contentType: 'markdown', emitUpdate: false });
};
