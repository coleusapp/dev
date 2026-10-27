import { joinFrontMatter, splitFrontMatter } from '@/editor/frontMatter';
import type { Editor } from '@tiptap/vue-3';

/**
 * Turns Markdown into a canonical form for comparison only, ignoring the
 * differences the visual editor introduces without changing meaning:
 * bullet characters, "1)" vs "1.", _emphasis_ vs *emphasis*, setext vs
 * "#" headings, harmless backslash escapes, blank-line runs, and table
 * cell padding. "\#" is deliberately NOT unescaped: it decides whether
 * something is a tag.
 */
export const normalizeMarkdown = (markdown: string): string =>
    markdown
        .replace(/\r\n?/g, '\n')
        .replace(/^(.+)\n=+[ \t]*$/gm, '# $1')
        .replace(/\\([[\]*_`~|!\-+.()<>{}\\])/g, '$1')
        .replace(/^(\s*)[*+](\s)/gm, '$1-$2')
        .replace(/^(\s*\d+)\)(\s)/gm, '$1.$2')
        .replace(/(^|[^\w*_])__([^_\n]+)__(?![\w_])/g, '$1**$2**')
        .replace(/(^|[^\w*_])_([^_\n]+)_(?![\w_])/g, '$1*$2*')
        .replace(/^\|(.*)\|[ \t]*$/gm, (row) =>
            row
                .split('|')
                .map((cell) => (/^\s*:?-+:?\s*$/.test(cell) ? cell.trim().replace(/-+/, '-') : cell.trim()))
                .join('|'),
        )
        .replace(/[ \t]+$/gm, (spaces) => (spaces.length >= 2 ? '  ' : ''))
        .replace(/\n{2,}/g, '\n')
        .trim();

/**
 * The note as Markdown, with its front matter put back in front.
 */
export const editorMarkdown = (editor: Editor, frontMatter: string): string => joinFrontMatter(frontMatter, editor.getMarkdown().replace(/\s+$/, ''));

/**
 * Loads a note into the editor and reports whether the visual editor can
 * keep its Markdown: true when writing it back would only change style,
 * false when something would be lost or changed (raw HTML, unusual syntax).
 */
export const loadMarkdown = (editor: Editor, markdown: string): { frontMatter: string; faithful: boolean } => {
    const { frontMatter, body } = splitFrontMatter(markdown);

    editor.commands.setContent(body, { contentType: 'markdown', emitUpdate: false });

    return { frontMatter, faithful: normalizeMarkdown(editor.getMarkdown()) === normalizeMarkdown(body) };
};
